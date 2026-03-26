const { app } = require('@azure/functions');
const crypto = require('crypto');

/**
 * VeriTrail Provenance DAG Verification — Azure Function (HTTP Trigger)
 *
 * Validates the structural integrity and provenance chain of a VeriTrail DAG.
 * Called by the Laravel pipeline after DAG construction to produce a tamper-evident
 * integrity hash and verify the DAG satisfies provenance requirements.
 *
 * Checks performed:
 * 1. Structural validity — DAG has required nodes (input, safety_gate, retrieval, generation, output)
 * 2. Acyclicity — topological sort succeeds (no circular dependencies)
 * 3. Completeness — every claim node has a backward edge to either a source or generation
 * 4. Integrity hash — SHA-256 over canonical node/edge representation for tamper detection
 * 5. Safety gate — verify safety_gate node shows "passed: true"
 * 6. Three-ring coverage — all three verification ring nodes are present
 */
app.http('verifyDAG', {
    methods: ['POST'],
    authLevel: 'anonymous',
    handler: async (request, context) => {
        try {
            const dag = await request.json();

            if (!dag || !dag.nodes || !dag.edges) {
                return {
                    status: 400,
                    jsonBody: { verified: false, error: 'Invalid DAG: missing nodes or edges' }
                };
            }

            const nodes = dag.nodes;
            const edges = dag.edges;
            const traceId = dag.trace_id || 'unknown';

            const results = {
                trace_id: traceId,
                version: dag.version || '1.0',
                verified: true,
                checks: {},
                integrity_hash: null,
                timestamp: new Date().toISOString(),
            };

            // Check 1: Required pipeline nodes
            const requiredNodes = ['input', 'safety_gate', 'retrieval', 'generation', 'output'];
            const nodeIds = new Set(nodes.map(n => n.id));
            const missingNodes = requiredNodes.filter(id => !nodeIds.has(id));
            results.checks.required_nodes = {
                passed: missingNodes.length === 0,
                missing: missingNodes,
                total: nodeIds.size,
            };
            if (missingNodes.length > 0) results.verified = false;

            // Check 2: Acyclicity (Kahn's algorithm for topological sort)
            const adjList = {};
            const inDegree = {};
            nodeIds.forEach(id => { adjList[id] = []; inDegree[id] = 0; });
            edges.forEach(e => {
                if (adjList[e.from]) {
                    adjList[e.from].push(e.to);
                    inDegree[e.to] = (inDegree[e.to] || 0) + 1;
                }
            });

            const queue = Object.keys(inDegree).filter(id => inDegree[id] === 0);
            let sortedCount = 0;
            while (queue.length > 0) {
                const node = queue.shift();
                sortedCount++;
                for (const neighbor of (adjList[node] || [])) {
                    inDegree[neighbor]--;
                    if (inDegree[neighbor] === 0) queue.push(neighbor);
                }
            }
            const isAcyclic = sortedCount === nodeIds.size;
            results.checks.acyclicity = { passed: isAcyclic, sorted_nodes: sortedCount };
            if (!isAcyclic) results.verified = false;

            // Check 3: Claim backward trace completeness
            const claimNodes = nodes.filter(n => n.type === 'claim');
            const claimsWithTrace = claimNodes.filter(claim => {
                return edges.some(e => e.to === claim.id && (e.label === 'supports' || e.label === 'produces'));
            });
            results.checks.claim_trace = {
                passed: claimNodes.length === 0 || claimsWithTrace.length === claimNodes.length,
                total_claims: claimNodes.length,
                traced_claims: claimsWithTrace.length,
                untraced: claimNodes.length - claimsWithTrace.length,
            };

            // Check 4: Safety gate verification
            const safetyGate = nodes.find(n => n.id === 'safety_gate');
            results.checks.safety_gate = {
                passed: safetyGate ? safetyGate.passed === true : false,
                checks: safetyGate ? safetyGate.checks || [] : [],
            };
            if (safetyGate && !safetyGate.passed) results.verified = false;

            // Check 5: Three-ring coverage
            const ringIds = ['ring1', 'ring2', 'ring3'];
            const presentRings = ringIds.filter(id => nodeIds.has(id));
            const ringScores = {};
            presentRings.forEach(id => {
                const node = nodes.find(n => n.id === id);
                ringScores[id] = node ? node.score : null;
            });
            results.checks.three_rings = {
                passed: presentRings.length === 3,
                present: presentRings,
                missing: ringIds.filter(id => !nodeIds.has(id)),
                scores: ringScores,
            };

            // Check 6: Source node coverage
            const sourceNodes = nodes.filter(n => n.type === 'source');
            const supportedClaims = claimNodes.filter(c => c.verdict === 'supported');
            results.checks.source_coverage = {
                total_sources: sourceNodes.length,
                supported_claims: supportedClaims.length,
                total_claims: claimNodes.length,
            };

            // Integrity hash: SHA-256 over canonical JSON representation
            const canonical = JSON.stringify({
                trace_id: traceId,
                nodes: nodes.map(n => ({ id: n.id, type: n.type, score: n.score || null }))
                    .sort((a, b) => a.id.localeCompare(b.id)),
                edges: edges.map(e => ({ from: e.from, to: e.to, label: e.label || '' }))
                    .sort((a, b) => `${a.from}-${a.to}`.localeCompare(`${b.from}-${b.to}`)),
            });
            results.integrity_hash = crypto.createHash('sha256').update(canonical).digest('hex');

            // Summary
            const allChecks = Object.values(results.checks);
            results.checks_passed = allChecks.filter(c => c.passed).length;
            results.checks_total = allChecks.length;

            context.log(`VeriTrail verification: trace=${traceId} verified=${results.verified} hash=${results.integrity_hash.substring(0, 12)}...`);

            return { status: 200, jsonBody: results };
        } catch (err) {
            context.error('VeriTrail verification error:', err);
            return {
                status: 500,
                jsonBody: { verified: false, error: err.message }
            };
        }
    }
});
