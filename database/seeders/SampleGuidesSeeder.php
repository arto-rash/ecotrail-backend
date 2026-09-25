<?php

namespace Database\Seeders;

use App\Models\Guide;
use Illuminate\Database\Seeder;

/**
 * Fictional sample listings that show what the partner directory will look like.
 * They carry no contact details, are never verified, and are labelled on the site.
 * Remove them with: php artisan guides:create --remove-samples
 */
class SampleGuidesSeeder extends Seeder
{
    private const GUIDES = [
        [
            'slug' => 'sample-shahdag-mountain-guide', 'name' => 'Elvin M.', 'kind' => 'guide',
            'regions' => ['guba-khachmaz'], 'languages' => ['Azərbaycan', 'English', 'Русский'],
            'headline' => ['az' => 'Şahdağ və Laza ətrafında yüksək dağ yürüşləri', 'en' => 'High-mountain treks around Shahdag and Laza'],
            'bio' => [
                'az' => 'Qusarda böyüyüb, Şahdağ Milli Parkında çoxgünlük yürüşlər və zirvə səfərləri aparır. Kiçik qruplar, erkən başlanğıc, təhlükəsizlik birinci.',
                'en' => 'Grew up in Qusar and leads multi-day treks and summit days in Shahdag National Park. Small groups, early starts, safety first.',
            ],
        ],
        [
            'slug' => 'sample-guba-khachmaz-tours', 'name' => 'Quba Highland Tours', 'kind' => 'agency',
            'regions' => ['guba-khachmaz', 'absheron-khizi'], 'languages' => ['Azərbaycan', 'English', 'Русский', 'Türkçe'],
            'headline' => ['az' => 'Bakıdan Quba və Xınalığa həftəsonu səfərləri', 'en' => 'Weekend trips from Baku to Quba and Khinalug'],
            'bio' => [
                'az' => 'Nəqliyyat, yerli bələdçi və kənd evlərində qonaqlama daxil olmaqla Quba, Qusar və Xınalığa kiçik qrup turları.',
                'en' => 'Small-group departures to Quba, Qusar and Khinalug with transport, a local guide and village homestays included.',
            ],
        ],
        [
            'slug' => 'sample-ismayilli-forest-walks', 'name' => 'Nigar A.', 'kind' => 'guide',
            'regions' => ['mountainous-shirvan'], 'languages' => ['Azərbaycan', 'English'],
            'headline' => ['az' => 'İsmayıllıda meşə yürüşləri və Lahıc sənətkarları', 'en' => 'Forest walks and Lahij craftspeople in Ismayilli'],
            'bio' => [
                'az' => 'Qaranohur gölünə və Lahıc ətrafındakı cığırlara yüngül yürüşlər təşkil edir, yolüstü misgər emalatxanalarına baş çəkir.',
                'en' => 'Runs gentle walks to Qaranohur lake and the paths around Lahij, stopping at coppersmith workshops along the way.',
            ],
        ],
        [
            'slug' => 'sample-goygol-lakes-guide', 'name' => 'Kamran H.', 'kind' => 'guide',
            'regions' => ['ganja-dashkasan'], 'languages' => ['Azərbaycan', 'English', 'Deutsch'],
            'headline' => ['az' => 'Göygöl Milli Parkı və Maralgöl marşrutları', 'en' => 'Göygöl National Park and the Maralgöl routes'],
            'bio' => [
                'az' => 'Göygöl Milli Parkının göl marşrutlarında və Kəpəz yamaclarında bələdçilik edir, park qaydaları və icazələr barədə məsləhət verir.',
                'en' => 'Guides the lake routes of Göygöl National Park and the slopes of Kəpəz, and helps with park rules and permits.',
            ],
        ],
        [
            'slug' => 'sample-shaki-zagatala-outdoor', 'name' => 'Şəki Outdoor', 'kind' => 'agency',
            'regions' => ['shaki-zagatala'], 'languages' => ['Azərbaycan', 'English', 'Русский'],
            'headline' => ['az' => 'Şəki, Qəbələ və İlisudan Transqafqaz cığırı hissələri', 'en' => 'Transcaucasian Trail sections from Shaki, Gabala and Ilisu'],
            'bio' => [
                'az' => 'Transqafqaz cığırının Şəki–Zaqatala hissələrində günlük və çoxgünlük yürüşlər, yük daşınması və düşərgə təşkili.',
                'en' => 'Day hikes and multi-day stages on the Shaki–Zagatala sections of the Transcaucasian Trail, with luggage transfers and camping.',
            ],
        ],
        [
            'slug' => 'sample-talysh-village-walks', 'name' => 'Talış Kənd Yürüşləri', 'kind' => 'agency',
            'regions' => ['lankaran-astara'], 'languages' => ['Azərbaycan', 'Русский'],
            'headline' => ['az' => 'Lerik və Yardımlıda kənddən-kəndə yürüşlər', 'en' => 'Village-to-village walks around Lerik and Yardimli'],
            'bio' => [
                'az' => 'Talış dağlarında yerli ailələrin evlərində qalmaqla bir neçə günlük kənd yürüşləri.',
                'en' => 'Multi-day walks through the Talysh mountains, staying with local families along the way.',
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::GUIDES as $g) {
            Guide::updateOrCreate(['slug' => $g['slug']], [
                ...$g,
                'email' => null, 'phone' => null, 'website' => null,
                'is_verified' => false,
                'is_sample' => true,
            ]);
        }
    }
}
