<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the first hand-written catalogue (estimated trail stats, sample reviews and
 * guides) with a schema for open-data trails and places, bilingual text and real
 * community content. The old tables held no user-generated data worth keeping.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('guides');
        Schema::dropIfExists('trails');
        Schema::dropIfExists('regions');

        Schema::create('economic_regions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('hiking_advised')->default(true);
            $table->timestamps();
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('pcode')->unique();
            $table->string('slug')->unique();
            $table->json('name');
            $table->string('type')->default('district');
            $table->foreignId('economic_region_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('trails', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('source')->default('osm')->index(); // osm | community
            $table->string('source_key')->nullable()->unique();
            $table->json('name');
            $table->json('summary')->nullable();
            $table->json('description')->nullable();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('economic_region_id')->nullable()->constrained()->nullOnDelete();
            $table->string('village')->nullable();
            $table->string('difficulty')->index();
            $table->string('difficulty_basis')->nullable(); // sac_scale | effort | submitter
            $table->string('sac_scale')->nullable();
            $table->string('route_type')->default('point_to_point');
            $table->decimal('distance_km', 7, 2);
            $table->unsignedSmallInteger('segments')->default(1);
            $table->unsignedInteger('duration_minutes');
            $table->unsignedInteger('ascent_m')->nullable();
            $table->unsignedInteger('descent_m')->nullable();
            $table->integer('min_ele_m')->nullable();
            $table->integer('max_ele_m')->nullable();
            $table->boolean('stats_estimated')->default(true);
            $table->decimal('lat', 9, 6);
            $table->decimal('lng', 9, 6);
            $table->json('route')->nullable();
            $table->json('elevation_profile')->nullable();
            $table->string('network')->nullable();
            $table->unsignedBigInteger('osm_relation_id')->nullable();
            $table->string('wikidata_id')->nullable();
            $table->text('image_url')->nullable();
            $table->json('image_credit')->nullable();
            $table->json('gallery')->nullable();
            $table->json('features')->nullable();
            $table->json('waypoints')->nullable();
            $table->json('sources')->nullable();
            $table->string('status')->default('published')->index();
            $table->boolean('is_featured')->default(false);
            $table->string('submitter_name')->nullable();
            $table->string('submitter_email')->nullable();
            $table->timestamps();
        });

        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('source_key')->unique();
            $table->string('type')->index();
            $table->json('name');
            $table->json('summary')->nullable();
            $table->json('description')->nullable();
            $table->decimal('lat', 9, 6);
            $table->decimal('lng', 9, 6);
            $table->integer('ele_m')->nullable();
            $table->string('ele_source')->nullable(); // mapped | srtm
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('economic_region_id')->nullable()->constrained()->nullOnDelete();
            $table->string('osm_id')->nullable();
            $table->string('wikidata_id')->nullable();
            $table->text('image_url')->nullable();
            $table->json('image_credit')->nullable();
            $table->json('sources')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        Schema::create('place_trail', function (Blueprint $table) {
            $table->foreignId('place_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trail_id')->constrained()->cascadeOnDelete();
            $table->primary(['place_id', 'trail_id']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->morphs('reviewable');
            $table->string('author_name', 80);
            $table->unsignedTinyInteger('rating');
            $table->text('body');
            $table->string('locale', 5)->default('az');
            $table->string('status')->default('published')->index();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('guides', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('kind')->default('guide'); // guide | agency
            $table->json('headline');
            $table->json('bio');
            $table->json('languages');
            $table->json('regions')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        Schema::create('guide_applications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('kind')->default('guide');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->json('regions')->nullable();
            $table->json('languages')->nullable();
            $table->text('message');
            $table->string('status')->default('new')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['guide_applications', 'guides', 'reviews', 'place_trail', 'places', 'trails', 'districts', 'economic_regions'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
