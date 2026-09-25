<?php

namespace App\Filament\Resources\Reviews;

use App\Models\Guide;
use App\Models\Place;
use App\Models\Review;
use App\Models\Trail;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|\UnitEnum|null $navigationGroup = 'Moderation';

    private const TYPES = [Trail::class => 'Trail', Place::class => 'Place', Guide::class => 'Guide'];

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('reviewable'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reviewable_type')->label('On')->badge()->formatStateUsing(fn ($state) => self::TYPES[$state] ?? class_basename($state))
                    ->description(fn (Review $record) => $record->reviewable instanceof Guide ? $record->reviewable->name : $record->reviewable?->localized('name', true)),
                TextColumn::make('author_name')->searchable(),
                TextColumn::make('rating')->numeric(1)->sortable(),
                TextColumn::make('body')->limit(80)->wrap()->searchable(),
                TextColumn::make('locale')->badge(),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([SelectFilter::make('reviewable_type')->label('On')->options(self::TYPES)])
            ->recordActions([DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListReviews::route('/')];
    }
}
