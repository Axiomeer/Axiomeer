<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0); // bytes
            $table->string('storage_path');       // local or blob path
            $table->string('status')->default('pending'); // pending, indexing, indexed, failed
            $table->string('index_name')->nullable();     // Azure AI Search index
            $table->unsignedInteger('chunk_count')->default(0);
            $table->json('metadata')->nullable(); // extra doc metadata
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();

            $table->index(['domain_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
