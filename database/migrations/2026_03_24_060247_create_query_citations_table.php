<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('query_citations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('query_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->text('source_snippet');       // exact text from source
            $table->text('cited_text');            // text in answer that cites this
            $table->string('document_title')->nullable();
            $table->unsignedInteger('page_number')->nullable();
            $table->unsignedInteger('chunk_index')->nullable();
            $table->float('relevance_score')->nullable();
            $table->string('verdict')->default('supported'); // supported, not_supported, inconclusive
            $table->timestamps();

            $table->index('query_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('query_citations');
    }
};
