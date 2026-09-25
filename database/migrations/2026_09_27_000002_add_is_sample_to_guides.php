<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Marks placeholder listings so the site can label them and they can be removed in one go.
    public function up(): void
    {
        Schema::table('guides', function (Blueprint $table) {
            $table->boolean('is_sample')->default(false)->after('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('guides', function (Blueprint $table) {
            $table->dropColumn('is_sample');
        });
    }
};
