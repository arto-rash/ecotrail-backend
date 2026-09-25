<?php

namespace App\Filament\Resources\TrailSubmissions\Pages;

use App\Filament\Resources\TrailSubmissions\TrailSubmissionResource;
use App\Filament\Support\Translatable;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTrailSubmission extends EditRecord
{
    protected static string $resource = TrailSubmissionResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return Translatable::fill($this->record, $data);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return Translatable::save($record, $data);
    }
}
