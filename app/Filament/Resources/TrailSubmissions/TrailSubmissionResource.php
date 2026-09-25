<?php

namespace App\Filament\Resources\TrailSubmissions;

use App\Enums\Difficulty;
use App\Enums\RouteType;
use App\Enums\TrailStatus;
use App\Filament\Support\Translatable;
use App\Models\Trail;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Trails shared by the community through the "Share a trail" form. */
class TrailSubmissionResource extends Resource
{
    protected static ?string $model = Trail::class;

    protected static ?string $slug = 'trail-submissions';

    protected static ?string $modelLabel = 'trail submission';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|\UnitEnum|null $navigationGroup = 'Moderation';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('source', 'community');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) (Trail::where('source', 'community')->where('status', TrailStatus::Pending)->count() ?: '') ?: null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Trail')->columns(2)->schema([
                Translatable::field('name', 'Name', fn ($p, $l) => TextInput::make($p)->label($l)->maxLength(120), requireOne: true),
                Translatable::field('description', 'Description', fn ($p, $l) => Textarea::make($p)->label($l)->rows(5)),
                TextInput::make('village'),
                Select::make('status')->options(TrailStatus::class)->required(),
            ]),
            Section::make('Stats (as submitted)')->columns(4)->schema([
                Select::make('difficulty')->options(Difficulty::class)->required(),
                Select::make('route_type')->options(RouteType::class)->required(),
                TextInput::make('distance_km')->numeric()->required(),
                TextInput::make('duration_minutes')->numeric()->required(),
                TextInput::make('ascent_m')->numeric(),
                TextInput::make('max_ele_m')->numeric(),
                TextInput::make('lat')->numeric()->required(),
                TextInput::make('lng')->numeric()->required(),
            ]),
            Section::make('Submitted by')->columns(2)->schema([
                TextInput::make('submitter_name')->disabled(),
                TextInput::make('submitter_email')->disabled(),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        $setStatus = fn (TrailStatus $status, string $label, string $color) => Action::make(strtolower($label))
            ->label($label)->color($color)->requiresConfirmation()
            ->visible(fn (Trail $record) => $record->status !== $status)
            ->action(fn (Trail $record) => $record->update(['status' => $status]));

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('image_url')->label('')->state(fn (Trail $record) => $record->image_url ? url($record->image_url) : null),
                TextColumn::make('name')->formatStateUsing(fn (Trail $record) => $record->localized('name', true))
                    ->description(fn (Trail $record) => $record->district?->localized('name', true)),
                TextColumn::make('distance_km')->label('km'),
                TextColumn::make('difficulty')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('submitter_name')->description(fn (Trail $record) => $record->submitter_email),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(TrailStatus::class)])
            ->recordActions([
                $setStatus(TrailStatus::Published, 'Approve', 'success'),
                $setStatus(TrailStatus::Rejected, 'Reject', 'danger'),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrailSubmissions::route('/'),
            'edit' => Pages\EditTrailSubmission::route('/{record}/edit'),
        ];
    }
}
