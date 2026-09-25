<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guides', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('kind')->default('guide'); // guide | agency
            $table->string('headline');
            $table->text('bio');
            $table->json('languages');
            $table->json('specialties')->nullable();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('rating', 2, 1)->default(0);
            $table->unsignedInteger('trips_count')->default(0);
            $table->unsignedInteger('price_from_azn')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guides');
    }
};
