<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'last_activity_at']);
        });

        Schema::table('queries', function (Blueprint $table) {
            $table->foreignId('conversation_id')->nullable()->after('domain_id')
                  ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('queries', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropColumn('conversation_id');
        });
        Schema::dropIfExists('conversations');
    }
};
