<?php

namespace Tests\Feature;

use App\Enums\TrailStatus;
use App\Models\Region;
use App\Models\Trail;
use Database\Seeders\CatalogueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrailApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogueSeeder::class);
    }

    public function test_lists_published_trails_with_rating(): void
    {
        $this->getJson('/api/trails')
            ->assertOk()
            ->assertJsonPath('meta.total', 13)
            ->assertJsonStructure(['data' => [['slug', 'name', 'region' => ['slug'], 'difficulty', 'rating', 'reviews_count', 'location' => ['lat', 'lng']]]]);
    }

    public function test_filters_by_difficulty_feature_and_region(): void
    {
        $slugs = $this->getJson('/api/trails?difficulty[]=easy&feature[]=waterfall')->json('data.*.slug');
        $this->assertEqualsCanonicalizing(['laza-waterfalls', 'afurja-waterfall', 'yeddi-gozel-waterfall'], $slugs);

        $this->getJson('/api/trails?region=goygol')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/trails?q=qaranohur')->assertJsonPath('data.0.slug', 'qaranohur-lake');
        $this->getJson('/api/trails?difficulty[]=impossible')->assertUnprocessable();
    }

    public function test_hides_pending_trails(): void
    {
        Trail::where('slug', 'qaranohur-lake')->update(['status' => TrailStatus::Pending]);

        $this->getJson('/api/trails/qaranohur-lake')->assertNotFound();
        $this->getJson('/api/trails')->assertJsonPath('meta.total', 12);
    }

    public function test_shows_trail_detail_and_nearby(): void
    {
        $this->getJson('/api/trails/qaranohur-lake')
            ->assertOk()
            ->assertJsonPath('data.name', 'Qaranohur Lake')
            ->assertJsonStructure(['data' => ['description', 'gallery', 'reviews' => [['author_name', 'rating', 'body']]]]);

        $this->getJson('/api/trails/qaranohur-lake/nearby')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonMissing(['slug' => 'qaranohur-lake']);
    }

    public function test_accepts_trail_submission_as_pending(): void
    {
        Storage::fake('public');

        $response = $this->post('/api/trails', [
            'name' => 'Lahij Copper Path',
            'region_id' => Region::where('slug', 'ismayilli')->value('id'),
            'village' => 'Lahij',
            'description' => 'A cobbled lane through the coppersmith village, then a forest path up to a viewpoint.',
            'difficulty' => 'easy',
            'route_type' => 'loop',
            'distance_km' => 4.5,
            'duration_minutes' => 120,
            'lat' => 40.85,
            'lng' => 48.39,
            'features' => ['village', 'historic'],
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
            'submitter_name' => 'Nara',
            'submitter_email' => 'nara@example.com',
        ], ['Accept' => 'application/json']);

        $response->assertCreated()->assertJsonPath('data.status', 'pending');

        $trail = Trail::where('name', 'Lahij Copper Path')->firstOrFail();
        $this->assertSame(TrailStatus::Pending, $trail->status);
        $this->assertCount(2, $trail->gallery);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $trail->cover_image));
    }

    public function test_rejects_submission_outside_azerbaijan(): void
    {
        $this->postJson('/api/trails', ['name' => 'Elsewhere', 'lat' => 51.5, 'lng' => -0.1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat', 'lng', 'region_id', 'description']);
    }

    public function test_meta_endpoints(): void
    {
        $this->getJson('/api/stats')->assertOk()->assertJsonPath('data.trails', 13);
        $this->getJson('/api/regions')->assertOk()->assertJsonCount(8, 'data');
        $this->getJson('/api/guides')->assertOk()->assertJsonCount(6, 'data');
        $this->getJson('/api/options')->assertOk()->assertJsonCount(4, 'data.difficulties');
    }
}
