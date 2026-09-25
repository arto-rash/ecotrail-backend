<?php

namespace App\Filament\Resources\GuideApplications\Pages;

use App\Filament\Resources\GuideApplications\GuideApplicationResource;
use App\Filament\Resources\Guides\GuideResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewGuideApplication extends ViewRecord
{
    protected static string $resource = GuideApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GuideApplicationResource::createGuideAction()->label('Create guide from this application')->button(),
            Action::make('open_guide')->label('Open created guide')
                ->visible(fn () => $this->record->guide_id !== null)
                ->url(fn () => GuideResource::getUrl('edit', ['record' => $this->record->guide_id])),
        ];
    }
}
