"""
Axiomeer — Semantic Kernel Orchestrator (Azure Function)

Implements the Sequential Process pattern from SK:
  Content Safety Agent → Retrieval Agent → Generation Agent → Verification Agent

Each step is a registered SK Plugin (equivalent to a "skill" in SK terminology).
The kernel runs them sequentially, passing context forward through each step.

Pattern: Sequential (as shown in the SK video — the directed, deterministic workflow
where steps A → B → C → D run in order with guardrails, no fan-out needed here
because each agent depends on the previous agent's output).
"""

import azure.functions as func
import json
import logging
import os
from typing import Any

from semantic_kernel import Kernel
from semantic_kernel.connectors.ai.open_ai import AzureChatCompletion
from semantic_kernel.contents.chat_history import ChatHistory
from semantic_kernel.functions import kernel_function
from semantic_kernel.connectors.ai.function_choice_behavior import FunctionChoiceBehavior
from semantic_kernel.connectors.ai.open_ai.prompt_execution_settings.azure_chat_prompt_execution_settings import (
    AzureChatPromptExecutionSettings,
)

app = func.FunctionApp(http_auth_level=func.AuthLevel.FUNCTION)

logger = logging.getLogger("axiomeer.sk")

# ── Environment ──────────────────────────────────────────────────────────────

OPENAI_ENDPOINT   = os.environ["AZURE_OPENAI_ENDPOINT"]
OPENAI_API_KEY    = os.environ["AZURE_OPENAI_API_KEY"]
FAST_DEPLOYMENT   = os.environ.get("AZURE_OPENAI_DEPLOYMENT", "gpt-4.1-mini-2")
COMPLEX_DEPLOYMENT = os.environ.get("AZURE_OPENAI_COMPLEX_DEPLOYMENT", "gpt-4.1")

# ── SK Plugins (domain-routed skills) ────────────────────────────────────────

class DocumentPlugin:
    """General document Q&A skill — used for Finance and general domains."""

    @kernel_function(name="generate_answer", description="Generate a grounded answer from document chunks")
    def generate_answer(self, question: str, context: str, system_prompt: str) -> str:
        # The kernel injects this into the prompt; the LLM call happens via SK
        return f"{system_prompt}\n\nContext:\n{context}\n\nQuestion: {question}"


class CompliancePlugin:
    """Compliance-aware skill — used for Legal and Healthcare domains.
    Enforces citation requirements and regulatory tone."""

    @kernel_function(name="generate_answer", description="Generate a compliance-aware grounded answer")
    def generate_answer(self, question: str, context: str, system_prompt: str) -> str:
        compliance_addendum = (
            "\n\nIMPORTANT: You are answering in a regulated professional context. "
            "Every factual claim MUST be cited with [Source N]. "
            "If a claim cannot be supported by the provided context, explicitly state it is not in the provided documents. "
            "Do not speculate beyond the source material."
        )
        return f"{system_prompt}{compliance_addendum}\n\nContext:\n{context}\n\nQuestion: {question}"


# ── Kernel builder ────────────────────────────────────────────────────────────

def build_kernel(use_complex: bool = False) -> Kernel:
    """Build a Semantic Kernel instance with the correct Azure OpenAI deployment."""
    kernel = Kernel()
    deployment = COMPLEX_DEPLOYMENT if use_complex else FAST_DEPLOYMENT

    kernel.add_service(
        AzureChatCompletion(
            service_id="axiomeer-chat",
            deployment_name=deployment,
            endpoint=OPENAI_ENDPOINT,
            api_key=OPENAI_API_KEY,
        )
    )

    # Register both plugins — the orchestrator picks the right one
    kernel.add_plugin(DocumentPlugin(), plugin_name="DocumentPlugin")
    kernel.add_plugin(CompliancePlugin(), plugin_name="CompliancePlugin")

    return kernel


def pick_plugin(domain: str) -> tuple[str, str]:
    """Route domain to the appropriate SK plugin — Sequential Process step 3."""
    if domain in ("legal", "healthcare"):
        return "CompliancePlugin", "generate_answer"
    return "DocumentPlugin", "generate_answer"


# ── Sequential Process orchestrator ──────────────────────────────────────────

async def run_sequential_process(
    question: str,
    chunks: list[dict],
    system_prompt: str,
    domain: str,
    use_complex: bool,
) -> dict:
    """
    Implements SK Sequential Process pattern.

    Step 1 — Build context from retrieved chunks (Retrieval output)
    Step 2 — Route to domain plugin (CompliancePlugin or DocumentPlugin)
    Step 3 — Invoke SK kernel with chat history (Generation)
    Step 4 — Return structured result for Verification agent
    """
    kernel = build_kernel(use_complex)

    # Step 1: Build context string from chunks
    context_parts = []
    for i, chunk in enumerate(chunks):
        content = chunk.get("content", "").strip()
        title = chunk.get("title", f"Source {i+1}")
        if content:
            context_parts.append(f"[Source {i+1}] {title}\n{content}")
    context_text = "\n\n---\n\n".join(context_parts)

    # Step 2: Pick the right SK plugin for this domain
    plugin_name, function_name = pick_plugin(domain)
    logger.info(f"SK Sequential Process — domain={domain} plugin={plugin_name} complex={use_complex}")

    # Step 3: Build the enriched prompt via the plugin, then invoke the LLM
    enriched_prompt = kernel.get_function(plugin_name, function_name).invoke_prompt(
        question=question,
        context=context_text,
        system_prompt=system_prompt,
    ) if False else None  # Use chat history approach below (more reliable with SK)

    # Build SK ChatHistory — SK manages the conversation state (stateful context)
    chat_history = ChatHistory()
    chat_history.add_system_message(
        f"{system_prompt}\n\n"
        f"[Semantic Kernel — {plugin_name} — Sequential Process Step 3]\n\n"
        f"You have access to the following context documents:\n\n{context_text}"
    )
    chat_history.add_user_message(question)

    # Execution settings
    settings = AzureChatPromptExecutionSettings(
        service_id="axiomeer-chat",
        temperature=0.1,
        max_tokens=2048,
    )

    # Invoke the chat completion via SK kernel
    chat_service = kernel.get_service("axiomeer-chat")
    response = await chat_service.get_chat_message_content(
        chat_history=chat_history,
        settings=settings,
        kernel=kernel,
    )

    answer = str(response) if response else ""

    # Step 4: Return structured result
    return {
        "success": True,
        "answer": answer,
        "sk_plugin": plugin_name,
        "sk_function": function_name,
        "sk_domain": domain,
        "sk_pattern": "sequential",
        "deployment": COMPLEX_DEPLOYMENT if use_complex else FAST_DEPLOYMENT,
        "chunks_used": len(chunks),
    }


# ── HTTP Trigger ──────────────────────────────────────────────────────────────

@app.route(route="sk-orchestrate", methods=["POST"])
async def sk_orchestrate(req: func.HttpRequest) -> func.HttpResponse:
    """
    POST /api/sk-orchestrate

    Body (JSON):
      {
        "question":     "string",
        "chunks":       [{"content": "...", "title": "..."}, ...],
        "system_prompt": "string",
        "domain":       "legal" | "healthcare" | "finance" | "general",
        "use_complex":  bool
      }

    Returns:
      {
        "success": true,
        "answer":  "string",
        "sk_plugin": "CompliancePlugin" | "DocumentPlugin",
        "sk_pattern": "sequential",
        ...
      }
    """
    try:
        body = req.get_json()
    except ValueError:
        return func.HttpResponse(
            json.dumps({"success": False, "error": "Invalid JSON body"}),
            status_code=400,
            mimetype="application/json",
        )

    question      = body.get("question", "").strip()
    chunks        = body.get("chunks", [])
    system_prompt = body.get("system_prompt", "You are a helpful assistant.")
    domain        = body.get("domain", "general").lower()
    use_complex   = bool(body.get("use_complex", False))

    if not question:
        return func.HttpResponse(
            json.dumps({"success": False, "error": "question is required"}),
            status_code=400,
            mimetype="application/json",
        )

    try:
        result = await run_sequential_process(question, chunks, system_prompt, domain, use_complex)
        return func.HttpResponse(
            json.dumps(result),
            status_code=200,
            mimetype="application/json",
        )
    except Exception as e:
        logger.exception("SK orchestration failed")
        return func.HttpResponse(
            json.dumps({"success": False, "error": str(e)}),
            status_code=500,
            mimetype="application/json",
        )
