<?php

namespace Tests\Feature;

use App\Catalogue\CatalogueSync;
use App\Models\GuideApplication;
use App\Models\Place;
use App\Models\Trail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CatalogueSync::fromDefaults()->run();
    }

    public function test_locale_comes_from_query_or_header(): void
    {
        $this->getJson('/api/regions?lang=en')
            ->assertHeader('Content-Language', 'en')
            ->assertJsonFragment(['slug' => 'guba-khachmaz', 'name' => 'Guba-Khachmaz']);

        $this->getJson('/api/regions', ['Accept-Language' => 'az-AZ,az;q=0.9'])
            ->assertHeader('Content-Language', 'az')
            ->assertJsonFragment(['slug' => 'guba-khachmaz', 'name' => 'Quba-Xaçmaz']);

        $this->getJson('/api/options?lang=az')->assertJsonFragment(['value' => 'easy', 'label' => 'Asan']);
        $this->getJson('/api/options?lang=en')->assertJsonFragment(['value' => 'easy', 'label' => 'Easy']);
    }

    public function test_trail_list_filters_and_detail(): void
    {
        $this->getJson('/api/trails')
            ->assertOk()
            ->assertJsonPath('meta.total', Trail::count())
            ->assertJsonStructure(['data' => [['slug', 'name', 'difficulty', 'distance_km', 'duration_minutes', 'district' => ['slug', 'name'], 'region' => ['slug', 'name'], 'location']]]);

        $goygol = $this->getJson('/api/trails?region=ganja-dashkasan')->json('data.*.slug');
        $this->assertContains('goygol-maralgol', $goygol);
        $this->assertNotContains('qamarvan', $goygol);

        $this->getJson('/api/trails?q=maralg')->assertJsonPath('data.0.slug', 'goygol-maralgol');
        $this->getJson('/api/trails?difficulty[]=impossible')->assertUnprocessable();

        $this->getJson('/api/trails/goygol-maralgol?lang=en')
            ->assertOk()
            ->assertJsonPath('data.route.type', 'MultiLineString')
            ->assertJsonPath('data.stats_estimated', true)
            ->assertJsonStructure(['data' => ['elevation_profile' => [[0, 1]], 'sources' => [['label', 'url', 'license']], 'places']]);

        $this->getJson('/api/trails/goygol-maralgol/nearby')->assertOk()->assertJsonCount(3, 'data');

        $gpx = $this->get('/api/trails/goygol-maralgol/gpx')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/gpx+xml')
            ->assertHeader('Content-Disposition', 'attachment; filename="goygol-maralgol.gpx"')
            ->getContent();
        $xml = simplexml_load_string($gpx);
        $this->assertSame('1.1', (string) $xml['version']);
        $this->assertGreaterThan(20, count($xml->trk->trkseg->trkpt));
        $this->get('/api/trails/nope/gpx')->assertNotFound();
    }

    public function test_places_filters_and_detail(): void
    {
        $waterfalls = $this->getJson('/api/places?type[]=waterfall&per_page=200')->json('data');
        $this->assertNotEmpty($waterfalls);
        $this->assertSame(['waterfall'], array_values(array_unique(array_column($waterfalls, 'type'))));

        // Bounding box around Göygöl National Park.
        $slugs = $this->getJson('/api/places?bbox=46.2,40.3,46.45,40.45&per_page=200')->json('data.*.slug');
        $this->assertContains(Place::where('wikidata_id', 'Q2471263')->value('slug'), $slugs);

        $slug = Place::where('wikidata_id', 'Q2395313')->value('slug'); // Xınalıq
        $this->getJson("/api/places/$slug?lang=az")
            ->assertOk()
            ->assertJsonPath('data.name', 'Xınalıq')
            ->assertJsonPath('data.region.slug', 'guba-khachmaz')
            ->assertJsonStructure(['data' => ['description', 'sources', 'image' => ['url', 'author', 'license']]]);
        $this->getJson("/api/places/$slug/nearby")->assertOk();
    }

    public function test_map_feed(): void
    {
        $this->getJson('/api/map')
            ->assertOk()
            ->assertJsonPath('trails.type', 'FeatureCollection')
            ->assertJsonPath('trails.features.0.geometry.type', 'MultiLineString')
            ->assertJsonPath('places.features.0.geometry.type', 'Point')
            ->assertJsonCount(Place::count(), 'places.features');
    }

    public function test_reviews_are_real_and_protected(): void
    {
        $this->getJson('/api/trails/qamarvan')->assertJsonPath('data.reviews_count', 0)->assertJsonPath('data.rating', null);

        $this->postJson('/api/trails/qamarvan/reviews?lang=en', ['author_name' => 'Nara', 'rating' => 4.5, 'body' => 'Lovely short walk above the village.'])
            ->assertCreated()->assertJsonPath('data.locale', 'en')->assertJsonPath('data.rating', 4.5);

        $this->postJson('/api/trails/qamarvan/reviews', ['author_name' => 'Nara', 'rating' => 3.3, 'body' => 'Quarter stars are not a thing.'])
            ->assertUnprocessable()->assertJsonValidationErrors('rating');

        $this->postJson('/api/trails/qamarvan/reviews', ['author_name' => 'Bot', 'rating' => 5, 'body' => 'Buy cheap stuff here now', 'website' => 'http://spam'])
            ->assertUnprocessable()->assertJsonValidationErrors('website');

        $this->postJson('/api/trails/qamarvan/reviews?lang=az', ['author_name' => 'A', 'rating' => 9, 'body' => 'x'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.rating.0', 'reytinq 0.5 ilə 5 arasında olmalıdır.');

        $this->getJson('/api/trails/qamarvan')->assertJsonPath('data.reviews_count', 1)->assertJsonPath('data.rating', 4.5);

        $place = Place::first()->slug;
        $this->postJson("/api/places/$place/reviews", ['author_name' => 'Leyla', 'rating' => 4, 'body' => 'Worth the detour in spring.'])->assertCreated();
    }

    public function test_community_submission_gets_district_from_coordinates(): void
    {
        Storage::fake('public');

        $this->post('/api/trails?lang=az', [
            'name' => 'Lahıc mis yolu',
            'description' => 'Misgərlər küçəsindən başlayıb meşə yolu ilə seyr nöqtəsinə qalxan marşrut.',
            'difficulty' => 'easy', 'route_type' => 'loop', 'distance_km' => 4.5, 'duration_minutes' => 120,
            'lat' => 40.8449, 'lng' => 48.3830,
            'photos' => [UploadedFile::fake()->image('a.jpg')],
            'submitter_name' => 'Nara', 'submitter_email' => 'nara@example.com',
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.status', 'pending');

        $trail = Trail::where('source', 'community')->firstOrFail();
        $this->assertSame('İsmayıllı', $trail->district->getTranslation('name', 'az'));
        $this->assertSame('mountainous-shirvan', $trail->region->slug);
        $this->assertSame('Lahıc mis yolu', $trail->getTranslation('name', 'az'));
        $this->getJson("/api/trails/{$trail->slug}")->assertNotFound();

        $this->postJson('/api/trails', ['name' => 'Elsewhere', 'lat' => 51.5, 'lng' => -0.1])
            ->assertUnprocessable()->assertJsonValidationErrors(['lat', 'lng', 'description']);
    }

    public function test_guide_applications_and_stats(): void
    {
        \App\Http\Resources\GuideResource::flushCache();
        $this->getJson('/api/guides')->assertOk()->assertJsonCount(0, 'data');

        $this->seed(\Database\Seeders\SampleGuidesSeeder::class);
        $this->getJson('/api/guides?lang=az')->assertJsonCount(6, 'data')
            ->assertJsonPath('data.0.is_sample', true)->assertJsonPath('data.0.email', null)->assertJsonPath('data.0.is_verified', false);
        $this->getJson('/api/stats')->assertJsonPath('data.guides', 0);
        $this->artisan('guides:remove-samples')->expectsOutput('Removed 6 sample listing(s).');

        $this->postJson('/api/guide-applications', [
            'name' => 'Elvin', 'kind' => 'guide', 'email' => 'elvin@example.com',
            'regions' => ['guba-khachmaz'], 'languages' => ['az', 'en'], 'experience_years' => 9, 'specialties' => ['summits'],
            'message' => 'Licensed mountain guide working around Shahdag for years.',
        ])->assertCreated();
        $this->assertSame(1, GuideApplication::count());

        $this->postJson('/api/guide-applications', ['name' => 'X', 'kind' => 'guide', 'email' => 'x@example.com', 'message' => str_repeat('a', 30), 'regions' => ['atlantis']])
            ->assertUnprocessable()->assertJsonValidationErrors('regions.0');

        $this->getJson('/api/stats')->assertOk()
            ->assertJsonPath('data.trails', Trail::count())
            ->assertJsonPath('data.places', Place::count())
            ->assertJsonPath('data.reviews', 0);
    }
}
