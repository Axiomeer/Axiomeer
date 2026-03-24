<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('name');             // legal, healthcare, finance
            $table->string('slug')->unique();   // legal, healthcare, finance
            $table->string('display_name');     // Legal, Healthcare, Finance
            $table->string('icon')->nullable(); // bx icon class
            $table->string('color')->nullable();// Bootstrap color name
            $table->text('system_prompt')->nullable(); // Domain-specific AI system prompt
            $table->string('citation_format')->nullable(); // e.g. "case_law", "clinical", "financial"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
