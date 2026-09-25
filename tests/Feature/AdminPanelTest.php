<?php

namespace Tests\Feature;

use App\Catalogue\CatalogueSync;
use App\Filament\Resources\Guides\Pages\CreateGuide;
use App\Filament\Resources\Guides\Pages\EditGuide;
use App\Filament\Resources\Guides\RelationManagers\ToursRelationManager;
use App\Http\Resources\GuideResource;
use App\Models\Guide;
use App\Models\GuideApplication;
use App\Models\Trail;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        GuideResource::flushCache();
        CatalogueSync::fromDefaults()->run();
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_only_admins_can_open_the_panel(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin')->assertOk();
        foreach (['guides', 'guide-applications', 'trail-submissions', 'reviews'] as $page) {
            $this->get("/admin/{$page}")->assertOk();
        }
    }

    /** What an admin enters is exactly what the public profile API returns. */
    public function test_admin_input_matches_public_profile(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateGuide::class)
            ->fillForm([
                'name' => 'Aysel Q.',
                'slug' => 'aysel-q',
                'kind' => 'guide',
                'headline' => ['az' => 'Qusar dağlarında yürüşlər', 'en' => 'Hikes in the Qusar mountains'],
                'bio' => ['az' => 'On il təcrübə.', 'en' => 'Ten years of experience.'],
                'experience_years' => 10,
                'specialties' => ['summits', 'photography'],
                'certifications' => [['name' => 'Wilderness First Responder', 'issuer' => 'WMA', 'year' => 2021]],
                'regions' => ['guba-khachmaz'],
                'languages' => ['az', 'en', 'ru'],
                'price_from_azn' => 80,
                'price_unit' => 'per_day',
                'phone' => '+994 55 000 11 22',
                'whatsapp' => '+994 55 000 11 22',
                'email' => 'aysel@example.org',
                'website' => 'https://aysel.example.org',
                'instagram' => 'aysel.hikes',
                'telegram' => 'aysel_hikes',
                'is_published' => true,
                'is_verified' => true,
                'is_sample' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $guide = Guide::firstWhere('slug', 'aysel-q');
        $trail = Trail::firstWhere('slug', 'transcaucasian-trail-ger-archan');

        Livewire::test(ToursRelationManager::class, ['ownerRecord' => $guide, 'pageClass' => EditGuide::class])
            ->callAction(TestAction::make('create')->table(), data: [
                'title' => ['az' => 'Ger – Ərçan keçidi', 'en' => 'Ger to Archan crossing'],
                'summary' => ['az' => 'İki günlük keçid.', 'en' => 'A two-day crossing.'],
                'description' => ['az' => 'Yaylaqlardan keçirik.', 'en' => 'Over the summer pastures.'],
                'duration_value' => 2,
                'duration_unit' => 'days',
                'difficulty' => 'expert',
                'price_azn' => 200,
                'price_unit' => 'per_person',
                'group_size_max' => 6,
                'months' => [7, 8],
                'includes' => ['az' => "Bələdçi\nÇadır", 'en' => "Guide\nTent"],
                'meeting_point' => ['az' => 'Laza kəndi', 'en' => 'Laza village'],
                'trails' => [$trail->id],
                'is_active' => true,
                'sort' => 0,
            ])
            ->assertHasNoFormErrors();

        foreach (['az', 'en'] as $lang) {
            $p = $this->getJson("/api/guides/aysel-q?lang={$lang}")->assertOk()->json('data');
            $this->assertSame($lang === 'az' ? 'Qusar dağlarında yürüşlər' : 'Hikes in the Qusar mountains', $p['headline']);
            $this->assertSame($lang === 'az' ? 'On il təcrübə.' : 'Ten years of experience.', $p['bio']);
            $this->assertSame(10, $p['experience_years']);
            $this->assertSame(['summits', 'photography'], array_column($p['specialties'], 'value'));
            $this->assertSame([['name' => 'Wilderness First Responder', 'issuer' => 'WMA', 'year' => 2021]], $p['certifications']);
            $this->assertSame(['guba-khachmaz'], array_column($p['regions'], 'slug'));
            $this->assertSame(['az', 'en', 'ru'], array_column($p['languages'], 'code'));
            $this->assertSame(80, $p['price_from_azn']);
            $this->assertSame('per_day', $p['price_unit']['value']);
            $this->assertTrue($p['is_verified']);
            $this->assertSame(['phone', 'whatsapp', 'email', 'website', 'instagram', 'telegram'], $p['contact_channels']);

            $tour = $p['tours'][0];
            $this->assertSame($lang === 'az' ? 'Ger – Ərçan keçidi' : 'Ger to Archan crossing', $tour['title']);
            $this->assertSame($lang === 'az' ? ['Bələdçi', 'Çadır'] : ['Guide', 'Tent'], $tour['includes']);
            $this->assertSame($lang === 'az' ? 'Laza kəndi' : 'Laza village', $tour['meeting_point']);
            $this->assertSame([2, 'days', 'expert', 200, 6, [7, 8]], [$tour['duration_value'], $tour['duration_unit'], $tour['difficulty'], $tour['price_azn'], $tour['group_size_max'], $tour['months']]);
            $this->assertSame(['transcaucasian-trail-ger-archan'], array_column($tour['trails'], 'slug'));
        }

        $this->postJson('/api/guides/aysel-q/contact')->assertJsonPath('data.email', 'aysel@example.org')
            ->assertJsonPath('data.whatsapp_url', 'https://wa.me/994550001122')
            ->assertJsonPath('data.instagram_url', 'https://instagram.com/aysel.hikes')
            ->assertJsonPath('data.telegram_url', 'https://t.me/aysel_hikes');
    }

    public function test_edit_form_loads_both_languages(): void
    {
        $this->actingAs($this->admin());
        $guide = Guide::create(['slug' => 'x', 'name' => 'X', 'kind' => 'guide', 'headline' => ['az' => 'AZ başlıq', 'en' => 'EN headline'], 'bio' => ['az' => 'bio'], 'languages' => ['az'], 'regions' => ['baku']]);

        Livewire::test(EditGuide::class, ['record' => $guide->getRouteKey()])
            ->assertSchemaStateSet(['headline' => ['az' => 'AZ başlıq', 'en' => 'EN headline']])
            ->fillForm(['headline' => ['az' => 'Yeni', 'en' => '']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['az' => 'Yeni'], $guide->fresh()->getTranslations('headline'));
    }

    public function test_application_prefills_new_guide(): void
    {
        $this->actingAs($this->admin());
        $app = GuideApplication::create([
            'name' => 'Rashad H.', 'kind' => 'guide', 'email' => 'rashad@example.org', 'phone' => '+994 70 111 22 33',
            'whatsapp' => '+994 70 111 22 33', 'instagram' => 'rashad', 'regions' => ['shaki-zagatala'], 'languages' => ['az', 'en'],
            'experience_years' => 7, 'specialties' => ['multi_day'], 'certifications' => "Mountain leader\nFirst aid",
            'message' => 'I guide on the Transcaucasian Trail around Shaki.',
        ]);

        Livewire::withQueryParams(['application' => $app->id])->test(CreateGuide::class)
            ->assertSchemaStateSet([
                'name' => 'Rashad H.', 'email' => 'rashad@example.org', 'experience_years' => 7,
                'specialties' => ['multi_day'], 'regions' => ['shaki-zagatala'], 'languages' => ['az', 'en'],
                'certifications' => [['name' => 'Mountain leader', 'issuer' => null, 'year' => null], ['name' => 'First aid', 'issuer' => null, 'year' => null]],
            ])
            ->fillForm(['headline' => ['az' => 'Şəkidə yürüşlər', 'en' => null]])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('accepted', $app->fresh()->status);
        $this->assertNotNull($app->fresh()->guide_id);
    }
}
