<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('query_citations', function (Blueprint $table) {
            $table->text('cited_text')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('query_citations', function (Blueprint $table) {
            $table->text('cited_text')->nullable(false)->change();
        });
    }
};
