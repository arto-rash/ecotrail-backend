<?php

namespace App\Filament\Resources\Guides\Pages;

use App\Filament\Resources\Guides\GuideResource;
use App\Filament\Support\Translatable;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditGuide extends EditRecord
{
    protected static string $resource = GuideResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return Translatable::fill($this->record, $data);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return Translatable::save($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_public')->label('View public profile')->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => rtrim(config('app.frontend_url'), '/')."/en/guides/{$this->record->slug}", shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }
}
