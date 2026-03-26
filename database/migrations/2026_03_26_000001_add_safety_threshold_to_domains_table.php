<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->float('safety_threshold')->default(0.75)->after('citation_format');
            $table->string('groundedness_level')->default('green')->after('safety_threshold'); // min required level
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn(['safety_threshold', 'groundedness_level']);
        });
    }
};
