<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->longText('answer')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed, review
            // Safety scores (three-ring defense)
            $table->float('groundedness_score')->nullable();  // Ring 1: Azure Groundedness API
            $table->float('lettuce_score')->nullable();       // Ring 2: LettuceDetect
            $table->float('confidence_score')->nullable();    // Ring 3: H-Neuron proxy
            $table->float('composite_safety_score')->nullable();
            $table->string('safety_level')->nullable(); // green, amber, red
            // Metadata
            $table->json('retrieved_chunks')->nullable();    // raw chunks from retrieval
            $table->json('provenance_dag')->nullable();      // VeriTrail DAG summary
            $table->unsignedInteger('token_count')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamps();

            $table->index(['domain_id', 'status']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queries');
    }
};
