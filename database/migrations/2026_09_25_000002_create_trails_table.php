<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trails', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->string('village')->nullable();
            $table->string('summary', 280);
            $table->text('description');
            $table->string('difficulty')->index();
            $table->string('route_type')->default('out_and_back');
            $table->decimal('distance_km', 6, 2);
            $table->unsignedInteger('duration_minutes');
            $table->unsignedInteger('elevation_gain_m')->nullable();
            $table->unsignedInteger('max_altitude_m')->nullable();
            $table->decimal('lat', 9, 6);
            $table->decimal('lng', 9, 6);
            $table->string('cover_image')->nullable();
            $table->json('gallery')->nullable();
            $table->json('features')->nullable();
            $table->json('best_months')->nullable();
            $table->boolean('offline_map')->default(false);
            $table->json('waypoints')->nullable();
            $table->string('status')->default('pending')->index();
            $table->boolean('is_featured')->default(false);
            $table->string('submitter_name')->nullable();
            $table->string('submitter_email')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trails');
    }
};
