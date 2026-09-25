<?php

namespace Tests\Feature;

use App\Catalogue\CatalogueSync;
use App\Http\Resources\GuideResource;
use App\Models\Guide;
use Database\Seeders\SampleGuidesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuideProfilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        GuideResource::flushCache();
        CatalogueSync::fromDefaults()->run();
        $this->seed(SampleGuidesSeeder::class);
    }

    public function test_list_filters_and_never_exposes_contacts(): void
    {
        $res = $this->getJson('/api/guides?lang=en')->assertOk()->assertJsonCount(6, 'data');
        $json = $res->getContent();
        $this->assertStringNotContainsString('@example.com', $json);
        $res->assertJsonPath('data.0.contact_channels', ['email', 'website'])
            ->assertJsonStructure(['data' => [['slug', 'name', 'photo', 'headline', 'experience_years', 'specialties' => [['value', 'label']], 'languages' => [['code', 'label']], 'regions' => [['slug', 'name']], 'price_from_azn', 'price_unit', 'tours_count', 'is_sample']]]);

        $this->getJson('/api/guides?region=ganja-dashkasan')->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'sample-goygol-lakes-guide');
        $this->getJson('/api/guides?language=de')->assertJsonCount(1, 'data');
        $this->getJson('/api/guides?kind=agency')->assertJsonCount(3, 'data');
        $this->getJson('/api/guides?specialty=birdwatching')->assertJsonCount(1, 'data');
        $this->getJson('/api/guides?language=xx')->assertUnprocessable();
    }

    public function test_profile_has_tours_linked_to_trails_but_no_contacts(): void
    {
        $res = $this->getJson('/api/guides/sample-goygol-lakes-guide?lang=az')->assertOk()
            ->assertJsonPath('data.tours.0.title', 'Göygöldən Maralgölə')
            ->assertJsonPath('data.tours.0.trails.0.slug', 'goygol-maralgol')
            ->assertJsonPath('data.tours.0.includes', ['Bələdçi', 'Park girişi', 'Nahar'])
            ->assertJsonPath('data.certifications.0.year', 2016)
            ->assertJsonPath('data.tours_count', 3);
        $this->assertStringNotContainsString('@example.com', $res->getContent());

        Guide::where('slug', 'sample-goygol-lakes-guide')->update(['is_published' => false]);
        $this->getJson('/api/guides/sample-goygol-lakes-guide')->assertNotFound();
    }

    public function test_contact_reveal_returns_channels_and_counts(): void
    {
        $guide = Guide::where('slug', 'sample-goygol-lakes-guide')->first();
        $guide->update(['phone' => '+994 50 123 45 67', 'whatsapp' => '+994 (50) 123-45-67', 'instagram' => 'https://instagram.com/kamran.trails', 'telegram' => '@kamran']);

        $this->postJson("/api/guides/{$guide->slug}/contact")->assertOk()
            ->assertJsonPath('data.phone', '+994 50 123 45 67')
            ->assertJsonPath('data.whatsapp_url', 'https://wa.me/994501234567')
            ->assertJsonPath('data.instagram', '@kamran.trails')
            ->assertJsonPath('data.instagram_url', 'https://instagram.com/kamran.trails')
            ->assertJsonPath('data.telegram_url', 'https://t.me/kamran')
            ->assertJsonPath('data.is_sample', true);
        $this->postJson("/api/guides/{$guide->slug}/contact");

        $this->assertSame(2, $guide->fresh()->contact_reveals);
    }

    public function test_guide_reviews_and_trail_page_lists_guides(): void
    {
        $this->postJson('/api/guides/sample-goygol-lakes-guide/reviews', ['author_name' => 'Leyla', 'rating' => 4.5, 'body' => 'Knew every turn of the Maralgöl path.'])->assertCreated();
        $this->getJson('/api/guides/sample-goygol-lakes-guide')->assertJsonPath('data.rating', 4.5)->assertJsonPath('data.reviews_count', 1);

        $this->getJson('/api/trails/goygol-maralgol')->assertJsonPath('data.guides.0.slug', 'sample-goygol-lakes-guide');
        $this->getJson('/api/trails/albanyolu-trail')->assertJsonCount(0, 'data.guides');
    }

    public function test_remove_samples(): void
    {
        $this->artisan('guides:remove-samples')->expectsOutput('Removed 6 sample listing(s).');
        $this->assertDatabaseCount('guide_tours', 0);
    }
}
