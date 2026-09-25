<?php

namespace App\Filament\Resources\Guides\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class GuidesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('activeTours')->withAvg('reviews', 'rating'))
            ->columns([
                ImageColumn::make('photo_path')->label('')->disk('public')->circular(),
                TextColumn::make('name')->searchable()->sortable()
                    ->description(fn ($record) => $record->getTranslation('headline', 'en', false) ?: $record->getTranslation('headline', 'az', false)),
                TextColumn::make('kind')->badge(),
                IconColumn::make('is_published')->label('Published')->boolean(),
                IconColumn::make('is_verified')->label('Verified')->boolean(),
                IconColumn::make('is_sample')->label('Sample')->boolean(),
                TextColumn::make('active_tours_count')->label('Tours')->sortable(),
                TextColumn::make('contact_reveals')->label('Contact reveals')->sortable(),
                TextColumn::make('reviews_avg_rating')->label('Rating')->numeric(1),
                TextColumn::make('updated_at')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('kind')->options(['guide' => 'Independent guide', 'agency' => 'Tour agency']),
                TernaryFilter::make('is_published')->label('Published'),
                TernaryFilter::make('is_verified')->label('Verified'),
                TernaryFilter::make('is_sample')->label('Sample'),
            ])
            ->recordActions([
                Action::make('view_public')->label('View profile')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => rtrim(config('app.frontend_url'), '/')."/en/guides/{$record->slug}", shouldOpenInNewTab: true),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
