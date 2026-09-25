<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Full guide profiles (experience, certifications, specialties, pricing, contact channels),
 * their tours linked to catalogue trails, application fields that map onto a guide, and
 * admin users for the Filament panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guides', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('kind');
            $table->unsignedSmallInteger('experience_years')->nullable()->after('bio');
            $table->json('certifications')->nullable()->after('experience_years');
            $table->json('specialties')->nullable()->after('certifications');
            $table->unsignedInteger('price_from_azn')->nullable()->after('regions');
            $table->string('price_unit')->nullable()->after('price_from_azn');
            $table->string('whatsapp')->nullable()->after('phone');
            $table->string('instagram')->nullable()->after('website');
            $table->string('telegram')->nullable()->after('instagram');
            $table->boolean('is_published')->default(true)->after('is_sample');
            $table->unsignedInteger('contact_reveals')->default(0)->after('is_published');
        });

        Schema::create('guide_tours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->json('title');
            $table->json('summary')->nullable();
            $table->json('description')->nullable();
            $table->unsignedSmallInteger('duration_value');
            $table->string('duration_unit')->default('hours'); // hours | days
            $table->string('difficulty');
            $table->unsignedInteger('price_azn')->nullable();
            $table->string('price_unit')->nullable();
            $table->unsignedSmallInteger('group_size_max')->nullable();
            $table->json('months')->nullable();
            $table->json('includes')->nullable();
            $table->json('meeting_point')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['guide_id', 'slug']);
        });

        Schema::create('guide_tour_trail', function (Blueprint $table) {
            $table->foreignId('guide_tour_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trail_id')->constrained()->cascadeOnDelete();
            $table->primary(['guide_tour_id', 'trail_id']);
        });

        Schema::table('guide_applications', function (Blueprint $table) {
            $table->unsignedSmallInteger('experience_years')->nullable()->after('languages');
            $table->json('specialties')->nullable()->after('experience_years');
            $table->text('certifications')->nullable()->after('specialties');
            $table->string('whatsapp')->nullable()->after('phone');
            $table->string('instagram')->nullable()->after('website');
            $table->foreignId('guide_id')->nullable()->after('status')->constrained()->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_admin'));
        Schema::table('guide_applications', function (Blueprint $t) {
            $t->dropConstrainedForeignId('guide_id');
            $t->dropColumn(['experience_years', 'specialties', 'certifications', 'whatsapp', 'instagram']);
        });
        Schema::dropIfExists('guide_tour_trail');
        Schema::dropIfExists('guide_tours');
        Schema::table('guides', fn (Blueprint $t) => $t->dropColumn([
            'photo_path', 'experience_years', 'certifications', 'specialties', 'price_from_azn', 'price_unit',
            'whatsapp', 'instagram', 'telegram', 'is_published', 'contact_reveals',
        ]));
    }
};
