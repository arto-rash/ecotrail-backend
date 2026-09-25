<?php

namespace App\Filament\Resources\Guides\Schemas;

use App\Enums\GuideLanguage;
use App\Enums\GuideSpecialty;
use App\Enums\PriceUnit;
use App\Filament\Support\Translatable;
use App\Models\EconomicRegion;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * The guide form. Its sections mirror the public profile page one to one:
 * profile header → about → experience → coverage → pricing → contact card → status.
 */
class GuideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Profile')
                ->description('Header of the public profile.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug(Str::ascii((string) $state))) : null),
                    TextInput::make('slug')->required()->maxLength(120)->alphaDash()->unique(ignoreRecord: true)
                        ->helperText('Profile URL: /guides/{slug}'),
                    Select::make('kind')->required()->default('guide')
                        ->options(['guide' => 'Independent guide', 'agency' => 'Tour agency']),
                    FileUpload::make('photo_path')->label('Photo')
                        ->image()->imageEditor()->disk('public')->directory('guides')->visibility('public')->maxSize(4096),
                    Translatable::field('headline', 'Headline', fn ($path, $label) => TextInput::make($path)->label($label)->maxLength(160), requireOne: true),
                    Translatable::field('bio', 'About', fn ($path, $label) => Textarea::make($path)->label($label)->rows(6)->maxLength(5000), requireOne: true),
                ]),

            Section::make('Experience')
                ->columns(2)
                ->schema([
                    TextInput::make('experience_years')->label('Years guiding')->numeric()->minValue(0)->maxValue(70),
                    CheckboxList::make('specialties')->options(GuideSpecialty::options())->columns(3)->columnSpanFull(),
                    Repeater::make('certifications')
                        ->schema([
                            TextInput::make('name')->required()->maxLength(160),
                            TextInput::make('issuer')->maxLength(160),
                            TextInput::make('year')->numeric()->minValue(1950)->maxValue((int) date('Y')),
                        ])
                        ->columns(3)->defaultItems(0)->addActionLabel('Add certification')->columnSpanFull(),
                ]),

            Section::make('Coverage')
                ->columns(2)
                ->schema([
                    Select::make('regions')->multiple()->required()
                        ->options(fn () => EconomicRegion::where('hiking_advised', true)->orderBy('sort')->get()
                            ->mapWithKeys(fn ($r) => [$r->slug => $r->getTranslation('name', 'en')])),
                    Select::make('languages')->multiple()->required()->options(GuideLanguage::options()),
                ]),

            Section::make('Pricing')
                ->description('Shown as "from X ₼" on the card and profile.')
                ->columns(2)
                ->schema([
                    TextInput::make('price_from_azn')->label('Price from (AZN)')->numeric()->minValue(0),
                    Select::make('price_unit')->options(PriceUnit::class)->requiredWith('price_from_azn'),
                ]),

            Section::make('Contacts')
                ->description('Hidden on the site until a visitor clicks "Show contact". Fill in at least one.')
                ->columns(3)
                ->schema([
                    TextInput::make('phone')->tel()->maxLength(40),
                    TextInput::make('whatsapp')->label('WhatsApp number')->tel()->maxLength(40),
                    TextInput::make('email')->email()->maxLength(190),
                    TextInput::make('website')->url()->maxLength(190),
                    TextInput::make('instagram')->prefix('@')->maxLength(100),
                    TextInput::make('telegram')->prefix('@')->maxLength(100),
                ]),

            Section::make('Status')
                ->columns(3)
                ->schema([
                    Toggle::make('is_published')->label('Published')->default(true),
                    Toggle::make('is_verified')->label('Verified (licence and references checked)'),
                    Toggle::make('is_sample')->label('Sample listing'),
                ]),
        ])->columns(1);
    }
}
