<?php

namespace App\Filament\Resources\Guides\RelationManagers;

use App\Enums\Difficulty;
use App\Enums\PriceUnit;
use App\Filament\Support\Translatable;
use App\Models\GuideTour;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Tours on a guide's profile; each field maps to one line of the public tour card. */
class ToursRelationManager extends RelationManager
{
    protected static string $relationship = 'tours';

    private const MONTHS = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];

    public function form(Schema $schema): Schema
    {
        $translatable = (new GuideTour)->translatable;

        return $schema->components([
            Section::make('Tour')->columns(2)->schema([
                Translatable::field('title', 'Title', fn ($p, $l) => TextInput::make($p)->label($l)->maxLength(160), requireOne: true),
                Translatable::field('summary', 'One-line summary', fn ($p, $l) => TextInput::make($p)->label($l)->maxLength(240)),
                Translatable::field('description', 'Description', fn ($p, $l) => Textarea::make($p)->label($l)->rows(4)->maxLength(4000)),
            ]),
            Section::make('Details')->columns(4)->schema([
                TextInput::make('duration_value')->label('Duration')->numeric()->minValue(1)->required(),
                Select::make('duration_unit')->options(['hours' => 'hours', 'days' => 'days'])->default('hours')->required(),
                Select::make('difficulty')->options(Difficulty::class)->required(),
                TextInput::make('group_size_max')->label('Max group size')->numeric()->minValue(1),
                TextInput::make('price_azn')->label('Price (AZN)')->numeric()->minValue(0),
                Select::make('price_unit')->options(PriceUnit::class)->requiredWith('price_azn'),
                TextInput::make('sort')->numeric()->default(0),
                Toggle::make('is_active')->label('Shown on profile')->default(true)->inline(false),
                CheckboxList::make('months')->label('Season')->options(self::MONTHS)->columns(6)->columnSpanFull(),
            ]),
            Section::make('Practical')->schema([
                Translatable::field('includes', "What's included (one per line)", fn ($p, $l) => Textarea::make($p)->label($l)->rows(4)),
                Translatable::field('meeting_point', 'Meeting point', fn ($p, $l) => TextInput::make($p)->label($l)->maxLength(200)),
                Select::make('trails')->label('Catalogue trails on this tour')
                    ->relationship('trails', 'slug', fn ($query) => $query->where('status', 'published'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->localized('name', true).' ('.$record->distance_km.' km)')
                    ->multiple()->preload()->searchable(),
            ]),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        $translations = fn (array $data, GuideTour $record) => Translatable::fill($record, $data);
        $clean = fn (array $data) => Translatable::clean($data, (new GuideTour)->translatable);

        return $table
            ->recordTitle(fn (GuideTour $record) => $record->localized('title', true))
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('title')->formatStateUsing(fn ($record) => $record->localized('title', true)),
                TextColumn::make('duration_value')->label('Duration')->formatStateUsing(fn ($record) => "{$record->duration_value} {$record->duration_unit}"),
                TextColumn::make('difficulty')->badge(),
                TextColumn::make('price_azn')->label('Price')->formatStateUsing(fn ($record) => $record->price_azn ? "{$record->price_azn} ₼ ".$record->price_unit?->label() : '—'),
                TextColumn::make('trails_count')->counts('trails')->label('Trails'),
                IconColumn::make('is_active')->label('Shown')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->mutateDataUsing($clean),
            ])
            ->recordActions([
                EditAction::make()->mutateRecordDataUsing($translations)->using(fn (GuideTour $record, array $data) => Translatable::save($record, $data)),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
