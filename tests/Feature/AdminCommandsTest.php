<?php

namespace Tests\Feature;

use App\Enums\TrailStatus;
use App\Models\Trail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCommandsTest extends TestCase
{
    use RefreshDatabase;

    private function submission(): Trail
    {
        return Trail::create([
            'slug' => 'lahic-yolu-abc123', 'source' => 'community', 'name' => ['az' => 'Lahıc yolu'],
            'difficulty' => 'easy', 'route_type' => 'loop', 'distance_km' => 4, 'duration_minutes' => 90,
            'lat' => 40.84, 'lng' => 48.38, 'status' => TrailStatus::Pending,
            'submitter_name' => 'Nara', 'submitter_email' => 'nara@example.com',
        ]);
    }

    public function test_list_and_approve_submissions(): void
    {
        $trail = $this->submission();

        $this->artisan('submissions:list')->expectsOutputToContain('lahic-yolu-abc123')->assertSuccessful();
        $this->artisan('submissions:approve', ['slug' => $trail->slug])->assertSuccessful();
        $this->assertSame(TrailStatus::Published, $trail->fresh()->status);
        $this->getJson("/api/trails/{$trail->slug}")->assertOk();

        $this->artisan('submissions:list')->expectsOutput('No submissions.');
        $this->artisan('submissions:approve', ['slug' => 'missing'])->assertFailed();
    }

    public function test_reject_submission(): void
    {
        $trail = $this->submission();
        $this->artisan('submissions:approve', ['slug' => $trail->slug, '--reject' => true])->assertSuccessful();
        $this->assertSame(TrailStatus::Rejected, $trail->fresh()->status);
    }

    public function test_delete_reviews(): void
    {
        $trail = $this->submission();
        $trail->update(['status' => TrailStatus::Published]);
        $review = $trail->reviews()->create(['author_name' => 'Spam', 'rating' => 5, 'body' => 'Buy cheap things now!!', 'locale' => 'en']);

        $this->artisan('reviews:delete', ['--latest' => 5])->expectsOutputToContain('Spam')->assertSuccessful();
        $this->artisan('reviews:delete', ['id' => [$review->id]])->expectsOutput('Deleted 1 review(s).');
        $this->assertDatabaseCount('reviews', 0);
    }
}
