<?php

namespace App\Filament\Resources\GuideApplications;

use App\Enums\GuideLanguage;
use App\Enums\GuideSpecialty;
use App\Filament\Resources\Guides\GuideResource;
use App\Models\EconomicRegion;
use App\Models\GuideApplication;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GuideApplicationResource extends Resource
{
    protected static ?string $model = GuideApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Directory';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Partner applications';

    public const STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'accepted' => 'Accepted', 'rejected' => 'Rejected'];

    public static function getNavigationBadge(): ?string
    {
        return (string) (GuideApplication::where('status', 'new')->count() ?: '') ?: null;
    }

    public static function infolist(Schema $schema): Schema
    {
        $regions = fn () => EconomicRegion::pluck('name', 'slug')->map(fn ($n) => is_array($n) ? ($n['en'] ?? reset($n)) : $n);

        return $schema->components([
            Section::make('Applicant')->columns(3)->schema([
                TextEntry::make('name'),
                TextEntry::make('kind')->badge(),
                TextEntry::make('status')->badge()->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state),
                TextEntry::make('email')->copyable(),
                TextEntry::make('phone')->placeholder('—'),
                TextEntry::make('whatsapp')->placeholder('—'),
                TextEntry::make('website')->placeholder('—')->url(fn ($state) => $state, shouldOpenInNewTab: true),
                TextEntry::make('instagram')->placeholder('—'),
                TextEntry::make('created_at')->label('Received')->since(),
            ]),
            Section::make('Experience')->columns(2)->schema([
                TextEntry::make('experience_years')->label('Years guiding')->placeholder('—'),
                TextEntry::make('specialties')->badge()->placeholder('—')
                    ->formatStateUsing(fn ($state) => GuideSpecialty::tryFrom($state)?->label() ?? $state),
                TextEntry::make('regions')->badge()->placeholder('—')
                    ->formatStateUsing(fn ($state) => $regions()[$state] ?? $state),
                TextEntry::make('languages')->badge()->placeholder('—')
                    ->formatStateUsing(fn ($state) => GuideLanguage::tryFrom($state)?->label() ?? $state),
                TextEntry::make('certifications')->placeholder('—')->columnSpanFull(),
                TextEntry::make('message')->columnSpanFull(),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->searchable()->description(fn ($record) => $record->email),
                TextColumn::make('kind')->badge(),
                TextColumn::make('experience_years')->label('Years'),
                TextColumn::make('regions')->badge(),
                SelectColumn::make('status')->options(self::STATUSES),
                TextColumn::make('created_at')->label('Received')->since()->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(self::STATUSES)])
            ->recordActions([
                ViewAction::make(),
                self::createGuideAction(),
                DeleteAction::make(),
            ]);
    }

    public static function createGuideAction(): Action
    {
        return Action::make('create_guide')
            ->label('Create guide')
            ->icon(Heroicon::OutlinedUserPlus)
            ->visible(fn (GuideApplication $record) => $record->guide_id === null)
            ->url(fn (GuideApplication $record) => GuideResource::getUrl('create', ['application' => $record->id]));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuideApplications::route('/'),
            'view' => Pages\ViewGuideApplication::route('/{record}'),
        ];
    }
}
