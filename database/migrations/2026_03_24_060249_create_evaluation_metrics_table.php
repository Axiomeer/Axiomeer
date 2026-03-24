<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('query_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('domain_id')->nullable()->constrained()->nullOnDelete();
            $table->string('run_type')->default('single'); // single, batch, benchmark
            // RAGAS metrics
            $table->float('faithfulness')->nullable();
            $table->float('answer_relevancy')->nullable();
            $table->float('context_precision')->nullable();
            $table->float('context_recall')->nullable();
            // Hallucination metrics
            $table->float('groundedness_pct')->nullable();    // % grounded by Azure API
            $table->float('unsupported_token_pct')->nullable(); // % unsupported by LettuceDetect
            $table->unsignedInteger('total_claims')->nullable();
            $table->unsignedInteger('supported_claims')->nullable();
            $table->unsignedInteger('unsupported_claims')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['domain_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_metrics');
    }
};
