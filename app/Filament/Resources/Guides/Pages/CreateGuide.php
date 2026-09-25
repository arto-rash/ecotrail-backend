<?php

namespace App\Filament\Resources\Guides\Pages;

use App\Filament\Resources\Guides\GuideResource;
use App\Filament\Support\Translatable;
use App\Models\GuideApplication;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateGuide extends CreateRecord
{
    protected static string $resource = GuideResource::class;

    /** Set when the guide is being created from a partner application (?application=ID). */
    public ?int $applicationId = null;

    public function mount(): void
    {
        parent::mount();

        $application = ($id = request()->integer('application')) ? GuideApplication::find($id) : null;
        if (! $application) {
            return;
        }

        $this->applicationId = $application->id;
        // Application fields map 1:1 onto the guide profile.
        $this->form->fill([
            'name' => $application->name,
            'slug' => Str::slug(Str::ascii($application->name)),
            'kind' => $application->kind,
            'headline' => ['az' => null, 'en' => null],
            'bio' => ['az' => $application->message, 'en' => null],
            'experience_years' => $application->experience_years,
            'specialties' => $application->specialties ?? [],
            'certifications' => collect(preg_split('/\R/', (string) $application->certifications))
                ->map(fn ($line) => trim($line))->filter()->map(fn ($name) => ['name' => $name, 'issuer' => null, 'year' => null])->values()->all(),
            'regions' => $application->regions ?? [],
            'languages' => $application->languages ?? [],
            'phone' => $application->phone,
            'whatsapp' => $application->whatsapp,
            'email' => $application->email,
            'website' => $application->website,
            'instagram' => $application->instagram,
            'is_published' => false,
            'is_verified' => false,
            'is_sample' => false,
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return Translatable::clean($data, ['headline', 'bio']);
    }

    protected function afterCreate(): void
    {
        if ($this->applicationId) {
            GuideApplication::whereKey($this->applicationId)->update(['status' => 'accepted', 'guide_id' => $this->record->id]);
        }
    }
}
