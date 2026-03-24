<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('query_id')->nullable()->constrained()->nullOnDelete();
            $table->string('agent_type');         // supervisor, retrieval, generation, verification
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->json('input')->nullable();     // what was sent to this agent
            $table->json('output')->nullable();    // what the agent returned
            $table->unsignedInteger('token_count')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('trace_id')->nullable(); // OpenTelemetry trace ID
            $table->string('span_id')->nullable();  // OpenTelemetry span ID
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->index(['query_id', 'agent_type']);
            $table->index('trace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_runs');
    }
};
