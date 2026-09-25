<?php

namespace Database\Seeders;

use App\Models\Guide;
use App\Models\Trail;
use Illuminate\Database\Seeder;

/**
 * Fictional sample listings that show what the partner directory will look like.
 * Contacts are example.com placeholders, nothing is verified, and the site labels them.
 * Tours link to real catalogue trails. Remove with: php artisan guides:remove-samples
 */
class SampleGuidesSeeder extends Seeder
{
    private const GUIDES = [
        [
            'slug' => 'sample-shahdag-mountain-guide', 'name' => 'Elvin M.', 'kind' => 'guide',
            'regions' => ['guba-khachmaz'], 'languages' => ['az', 'en', 'ru'], 'experience_years' => 11,
            'specialties' => ['multi_day', 'summits', 'camping'], 'price_from_azn' => 90, 'price_unit' => 'per_day',
            'certifications' => [['name' => 'Wilderness First Aid (sample)', 'issuer' => 'Sample Training Centre', 'year' => 2022]],
            'headline' => ['az' => 'Şahdağ və Laza ətrafında yüksək dağ yürüşləri', 'en' => 'High-mountain treks around Shahdag and Laza'],
            'bio' => [
                'az' => "Qusarda böyüyüb, Şahdağ Milli Parkında çoxgünlük yürüşlər və zirvə səfərləri aparır.\n\nKiçik qruplar, erkən başlanğıc, təhlükəsizlik birinci. Hava pisləşərsə marşrut dəyişdirilir.",
                'en' => "Grew up in Qusar and leads multi-day treks and summit days in Shahdag National Park.\n\nSmall groups, early starts, safety first. Routes change when the weather does.",
            ],
            'tours' => [
                [
                    'title' => ['az' => 'Transqafqaz cığırı: Ger – Ərçan', 'en' => 'Transcaucasian Trail: Ger to Archan'],
                    'summary' => ['az' => 'Qusar dağlarında iki günlük keçid, çoban düşərgəsində gecələmə.', 'en' => 'Two days across the Qusar highlands with a night at a shepherd camp.'],
                    'description' => ['az' => 'Laza yaxınlığından başlayıb yaylaq çəmənlikləri ilə Ərçana enirik. Yükü atlarla daşımaq mümkündür.', 'en' => 'We start near Laza and cross high summer pastures down to Archan. Luggage can go by horse.'],
                    'duration_value' => 2, 'duration_unit' => 'days', 'difficulty' => 'expert', 'price_azn' => 180, 'price_unit' => 'per_person',
                    'group_size_max' => 8, 'months' => [6, 7, 8, 9],
                    'includes' => ['az' => "Bələdçi\nÇadır və düşərgə avadanlığı\nİki gecəlik yemək", 'en' => "Guide\nTents and camp kit\nMeals for the night"],
                    'meeting_point' => ['az' => 'Laza kəndi, Qusar', 'en' => 'Laza village, Qusar'],
                    'trails' => ['transcaucasian-trail-ger-archan'],
                ],
                [
                    'title' => ['az' => 'Talış kəndi ətrafında günlük yürüş', 'en' => 'Day hike around Talış village'],
                    'summary' => ['az' => 'Transqafqaz cığırının qısa, orta çətinlikli hissəsi.', 'en' => 'A short, moderate section of the Transcaucasian Trail.'],
                    'description' => null,
                    'duration_value' => 5, 'duration_unit' => 'hours', 'difficulty' => 'moderate', 'price_azn' => 60, 'price_unit' => 'per_person',
                    'group_size_max' => 10, 'months' => [5, 6, 7, 8, 9, 10],
                    'includes' => ['az' => "Bələdçi\nNahar", 'en' => "Guide\nPacked lunch"],
                    'meeting_point' => ['az' => 'Talış kəndi', 'en' => 'Talış village'],
                    'trails' => ['transcaucasian-trail-talis-talis'],
                ],
            ],
        ],
        [
            'slug' => 'sample-guba-khachmaz-tours', 'name' => 'Quba Highland Tours', 'kind' => 'agency',
            'regions' => ['guba-khachmaz', 'absheron-khizi'], 'languages' => ['az', 'en', 'ru', 'tr'], 'experience_years' => 8,
            'specialties' => ['day_hikes', 'culture', 'families'], 'price_from_azn' => 75, 'price_unit' => 'per_person',
            'certifications' => [['name' => 'Licensed tour operator (sample)', 'issuer' => 'Sample licensing body', 'year' => 2019]],
            'headline' => ['az' => 'Bakıdan Quba və Xınalığa həftəsonu səfərləri', 'en' => 'Weekend trips from Baku to Quba and Khinalug'],
            'bio' => [
                'az' => 'Nəqliyyat, yerli bələdçi və kənd evlərində qonaqlama daxil olmaqla Quba, Qusar və Xınalığa kiçik qrup turları.',
                'en' => 'Small-group departures to Quba, Qusar and Khinalug with transport, a local guide and village homestays included.',
            ],
            'tours' => [
                [
                    'title' => ['az' => 'Qəçrəşdən Talışa: meşə və yaylaq', 'en' => 'Qəçrəş to Talış: forest and pasture'],
                    'summary' => ['az' => 'Bakıdan nəqliyyatla birgünlük yürüş.', 'en' => 'A full day out from Baku, transport included.'],
                    'description' => ['az' => 'Səhər Bakıdan çıxırıq, Qəçrəş meşələrindən keçib Talış kəndinə çatırıq. Axşam qayıdış.', 'en' => 'We leave Baku in the morning, walk through the Qəçrəş forests to Talış village, and drive back in the evening.'],
                    'duration_value' => 12, 'duration_unit' => 'hours', 'difficulty' => 'hard', 'price_azn' => 75, 'price_unit' => 'per_person',
                    'group_size_max' => 14, 'months' => [5, 6, 7, 8, 9, 10],
                    'includes' => ['az' => "Bakıdan nəqliyyat\nBələdçi\nNahar", 'en' => "Transport from Baku\nGuide\nLunch"],
                    'meeting_point' => ['az' => 'Bakı, 28 May metrosu', 'en' => 'Baku, 28 May metro station'],
                    'trails' => ['transcaucasian-trail-qecres-talis'],
                ],
            ],
        ],
        [
            'slug' => 'sample-ismayilli-forest-walks', 'name' => 'Nigar A.', 'kind' => 'guide',
            'regions' => ['mountainous-shirvan'], 'languages' => ['az', 'en'], 'experience_years' => 5,
            'specialties' => ['day_hikes', 'families', 'birdwatching', 'culture'], 'price_from_azn' => 40, 'price_unit' => 'per_person',
            'certifications' => [],
            'headline' => ['az' => 'İsmayıllıda meşə yürüşləri və Lahıc sənətkarları', 'en' => 'Forest walks and Lahij craftspeople in Ismayilli'],
            'bio' => [
                'az' => 'Qaranohur gölünə və Lahıc ətrafındakı cığırlara yüngül yürüşlər təşkil edir, yolüstü misgər emalatxanalarına baş çəkir.',
                'en' => 'Runs gentle walks to Qaranohur lake and the paths around Lahij, stopping at coppersmith workshops along the way.',
            ],
            'tours' => [
                [
                    'title' => ['az' => 'Qaranohur gölünə meşə yürüşü', 'en' => 'Forest walk to Qaranohur lake'],
                    'summary' => ['az' => 'Ailələr üçün uyğun, kölgəli meşə yolu.', 'en' => 'A shaded forest path, good for families.'],
                    'description' => null,
                    'duration_value' => 4, 'duration_unit' => 'hours', 'difficulty' => 'easy', 'price_azn' => 40, 'price_unit' => 'per_person',
                    'group_size_max' => 12, 'months' => [4, 5, 6, 7, 8, 9, 10],
                    'includes' => ['az' => "Bələdçi\nÇay və yüngül qəlyanaltı", 'en' => "Guide\nTea and snacks"],
                    'meeting_point' => ['az' => 'İsmayıllı şəhəri', 'en' => 'Ismayilli town'],
                    'trails' => [],
                ],
            ],
        ],
        [
            'slug' => 'sample-goygol-lakes-guide', 'name' => 'Kamran H.', 'kind' => 'guide',
            'regions' => ['ganja-dashkasan'], 'languages' => ['az', 'en', 'de'], 'experience_years' => 14,
            'specialties' => ['day_hikes', 'summits', 'photography'], 'price_from_azn' => 50, 'price_unit' => 'per_person',
            'certifications' => [['name' => 'National park guide licence (sample)', 'issuer' => 'Sample park administration', 'year' => 2016]],
            'headline' => ['az' => 'Göygöl Milli Parkı və Maralgöl marşrutları', 'en' => 'Göygöl National Park and the Maralgöl routes'],
            'bio' => [
                'az' => 'Göygöl Milli Parkının göl marşrutlarında və Kəpəz yamaclarında bələdçilik edir, park qaydaları və icazələr barədə məsləhət verir.',
                'en' => 'Guides the lake routes of Göygöl National Park and the slopes of Kəpəz, and helps with park rules and permits.',
            ],
            'tours' => [
                [
                    'title' => ['az' => 'Göygöldən Maralgölə', 'en' => 'Göygöl to Maralgöl'],
                    'summary' => ['az' => 'Parkın ən məşhur göl marşrutu.', 'en' => "The park's classic lake route."],
                    'description' => ['az' => 'Göygöldən başlayıb Zarıgölün yanından keçərək Maralgölə qalxırıq.', 'en' => 'From Göygöl we climb past Zarıgöl to Maralgöl.'],
                    'duration_value' => 7, 'duration_unit' => 'hours', 'difficulty' => 'hard', 'price_azn' => 70, 'price_unit' => 'per_person',
                    'group_size_max' => 10, 'months' => [6, 7, 8, 9, 10],
                    'includes' => ['az' => "Bələdçi\nPark girişi\nNahar", 'en' => "Guide\nPark entry\nLunch"],
                    'meeting_point' => ['az' => 'Göygöl Milli Parkının girişi', 'en' => 'Göygöl National Park entrance'],
                    'trails' => ['goygol-maralgol'],
                ],
                [
                    'title' => ['az' => 'Qaragöl gəzintisi', 'en' => 'Qaragöl stroll'],
                    'summary' => ['az' => 'Qısa və asan göl gəzintisi.', 'en' => 'A short, easy lakeside walk.'],
                    'description' => null,
                    'duration_value' => 2, 'duration_unit' => 'hours', 'difficulty' => 'easy', 'price_azn' => 50, 'price_unit' => 'per_group',
                    'group_size_max' => 8, 'months' => [5, 6, 7, 8, 9, 10],
                    'includes' => ['az' => 'Bələdçi', 'en' => 'Guide'],
                    'meeting_point' => ['az' => 'Göygöl Milli Parkının girişi', 'en' => 'Göygöl National Park entrance'],
                    'trails' => ['qaragol'],
                ],
                [
                    'title' => ['az' => 'Kəpəz yamaclarına tam gün', 'en' => 'Full day on the Kəpəz slopes'],
                    'summary' => ['az' => 'Yaxşı fiziki hazırlıq tələb edir.', 'en' => 'Needs good fitness.'],
                    'description' => null,
                    'duration_value' => 9, 'duration_unit' => 'hours', 'difficulty' => 'hard', 'price_azn' => 90, 'price_unit' => 'per_person',
                    'group_size_max' => 6, 'months' => [7, 8, 9],
                    'includes' => ['az' => "Bələdçi\nNahar", 'en' => "Guide\nLunch"],
                    'meeting_point' => ['az' => 'Göygöl şəhəri', 'en' => 'Goygol town'],
                    'trails' => ['kepez-dagi'],
                ],
            ],
        ],
        [
            'slug' => 'sample-shaki-zagatala-outdoor', 'name' => 'Şəki Outdoor', 'kind' => 'agency',
            'regions' => ['shaki-zagatala'], 'languages' => ['az', 'en', 'ru'], 'experience_years' => 6,
            'specialties' => ['multi_day', 'day_hikes', 'camping'], 'price_from_azn' => 35, 'price_unit' => 'per_person',
            'certifications' => [],
            'headline' => ['az' => 'Şəki, Qəbələ və Zaqatalada Transqafqaz cığırı hissələri', 'en' => 'Transcaucasian Trail sections from Shaki, Gabala and Zagatala'],
            'bio' => [
                'az' => 'Transqafqaz cığırının Şəki–Zaqatala hissələrində günlük və çoxgünlük yürüşlər, yük daşınması və düşərgə təşkili.',
                'en' => 'Day hikes and multi-day stages on the Shaki–Zagatala sections of the Transcaucasian Trail, with luggage transfers and camping.',
            ],
            'tours' => [
                [
                    'title' => ['az' => 'Qəbələdə iki qısa cığır: Qəmərvan və Abrix', 'en' => 'Two short Gabala trails: Qamarvan and Abrix'],
                    'summary' => ['az' => 'Bir gündə iki asan marşrut.', 'en' => 'Two easy routes in one day.'],
                    'description' => null,
                    'duration_value' => 6, 'duration_unit' => 'hours', 'difficulty' => 'easy', 'price_azn' => 35, 'price_unit' => 'per_person',
                    'group_size_max' => 15, 'months' => [4, 5, 6, 7, 8, 9, 10],
                    'includes' => ['az' => "Bələdçi\nMarşrutlar arası nəqliyyat", 'en' => "Guide\nTransport between trailheads"],
                    'meeting_point' => ['az' => 'Qəbələ şəhəri', 'en' => 'Gabala town'],
                    'trails' => ['qamarvan', 'abrix'],
                ],
                [
                    'title' => ['az' => 'Oğuzdan Şəkiyə: üç günlük keçid', 'en' => 'Oghuz to Shaki: three-day crossing'],
                    'summary' => ['az' => 'Transqafqaz cığırının iki hissəsi birlikdə.', 'en' => 'Two Transcaucasian Trail sections back to back.'],
                    'description' => null,
                    'duration_value' => 3, 'duration_unit' => 'days', 'difficulty' => 'expert', 'price_azn' => 260, 'price_unit' => 'per_person',
                    'group_size_max' => 8, 'months' => [6, 7, 8, 9],
                    'includes' => ['az' => "Bələdçi\nYük daşınması\nDüşərgə və yemək", 'en' => "Guide\nLuggage transfer\nCamping and meals"],
                    'meeting_point' => ['az' => 'Oğuz şəhəri', 'en' => 'Oghuz town'],
                    'trails' => ['transcaucasian-trail-oraban-bas-keldek', 'transcaucasian-trail-bas-keldek-sheki'],
                ],
            ],
        ],
        [
            'slug' => 'sample-talysh-village-walks', 'name' => 'Talış Kənd Yürüşləri', 'kind' => 'agency',
            'regions' => ['lankaran-astara'], 'languages' => ['az', 'ru'], 'experience_years' => 4,
            'specialties' => ['culture', 'multi_day'], 'price_from_azn' => 55, 'price_unit' => 'per_day',
            'certifications' => [],
            'headline' => ['az' => 'Lerik və Yardımlıda kənddən-kəndə yürüşlər', 'en' => 'Village-to-village walks around Lerik and Yardimli'],
            'bio' => [
                'az' => 'Talış dağlarında yerli ailələrin evlərində qalmaqla bir neçə günlük kənd yürüşləri.',
                'en' => 'Multi-day walks through the Talysh mountains, staying with local families along the way.',
            ],
            'tours' => [
                [
                    'title' => ['az' => 'Lerik kəndləri: iki gün', 'en' => 'Lerik villages: two days'],
                    'summary' => ['az' => 'Kənd evlərində gecələmə ilə yüngül yürüş.', 'en' => 'Gentle walking with a village homestay.'],
                    'description' => null,
                    'duration_value' => 2, 'duration_unit' => 'days', 'difficulty' => 'moderate', 'price_azn' => 110, 'price_unit' => 'per_person',
                    'group_size_max' => 8, 'months' => [5, 6, 7, 8, 9, 10],
                    'includes' => ['az' => "Bələdçi\nKənd evində gecələmə\nYemək", 'en' => "Guide\nHomestay\nMeals"],
                    'meeting_point' => ['az' => 'Lerik şəhəri', 'en' => 'Lerik town'],
                    'trails' => [],
                ],
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::GUIDES as $data) {
            $slug = $data['slug'];
            $guide = Guide::updateOrCreate(['slug' => $slug], [
                ...collect($data)->except('tours')->all(),
                'email' => "{$slug}@example.com",
                'website' => 'https://example.com',
                'phone' => null, 'whatsapp' => null, 'instagram' => null, 'telegram' => null,
                'is_verified' => false,
                'is_sample' => true,
                'is_published' => true,
            ]);

            $guide->tours()->delete();
            foreach ($data['tours'] as $i => $t) {
                $tour = $guide->tours()->create([...collect($t)->except('trails')->reject(fn ($v) => $v === null)->all(), 'sort' => $i, 'is_active' => true]);
                $tour->trails()->sync(Trail::whereIn('slug', $t['trails'])->pluck('id'));
            }
        }
    }
}
