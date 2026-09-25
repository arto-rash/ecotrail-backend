<?php

namespace App\Console\Commands;

use App\Models\EconomicRegion;
use App\Models\Guide;
use App\Models\GuideApplication;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;

#[Signature('guides:create {--from-application= : Prefill from a guide application id} {--list-applications : Show received applications}')]
#[Description('List a verified partner guide on the site')]
class CreateGuide extends Command
{
    public function handle(): int
    {
        if ($this->option('list-applications')) {
            $this->table(['Id', 'Name', 'Kind', 'Email', 'Regions', 'Status', 'Date'], GuideApplication::latest()->get()->map(fn ($a) => [
                $a->id, $a->name, $a->kind, $a->email, implode(', ', $a->regions ?? []), $a->status, $a->created_at->toDateString(),
            ]));

            return self::SUCCESS;
        }

        $app = ($id = $this->option('from-application')) ? GuideApplication::find($id) : null;

        $name = text('Name', default: $app->name ?? '', required: true);
        $kind = select('Kind', ['guide' => 'Independent guide', 'agency' => 'Tour agency'], default: $app->kind ?? 'guide');
        $headlineAz = text('Headline (Azerbaijani)', required: true);
        $headlineEn = text('Headline (English)', required: true);
        $bioAz = textarea('Bio (Azerbaijani)', default: $app->message ?? '');
        $bioEn = textarea('Bio (English)');
        $regions = multiselect('Regions', EconomicRegion::orderBy('sort')->get()->mapWithKeys(fn ($r) => [$r->slug => $r->getTranslation('name', 'en')])->all(), default: $app->regions ?? []);
        $languages = array_values(array_filter(array_map('trim', explode(',', text('Languages (comma-separated)', default: implode(', ', $app->languages ?? []))))));
        $email = text('Public email', default: $app->email ?? '');
        $phone = text('Public phone', default: $app->phone ?? '');
        $website = text('Website', default: $app->website ?? '');
        $verified = confirm('Mark as verified (licence and references checked)?', default: false);

        $guide = Guide::create([
            'slug' => Str::slug(Str::ascii($name)).'-'.Str::lower(Str::random(4)),
            'name' => $name,
            'kind' => $kind,
            'headline' => ['az' => $headlineAz, 'en' => $headlineEn],
            'bio' => array_filter(['az' => $bioAz, 'en' => $bioEn]),
            'languages' => $languages,
            'regions' => $regions,
            'email' => $email ?: null,
            'phone' => $phone ?: null,
            'website' => $website ?: null,
            'is_verified' => $verified,
        ]);
        $app?->update(['status' => 'accepted']);

        $this->info("Listed {$guide->name} ({$guide->slug}).");

        return self::SUCCESS;
    }
}
