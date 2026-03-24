<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('query_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');              // query, upload, safety_block, correction, login, etc.
            $table->string('entity_type')->nullable(); // query, document, user, agent
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('description')->nullable();
            $table->json('details')->nullable();   // structured payload
            $table->string('ip_address')->nullable();
            $table->string('severity')->default('info'); // info, warning, critical
            $table->timestamps();

            $table->index(['action', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
