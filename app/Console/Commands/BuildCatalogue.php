<?php

namespace App\Console\Commands;

use App\Catalogue\CatalogueBuilder;
use App\Catalogue\DistrictLocator;
use App\Catalogue\OpenData;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('catalogue:build {--output= : Where to write the JSON (defaults to database/data/catalogue.json)}')]
#[Description('Rebuild the trail and place catalogue from OpenStreetMap, Wikidata, Wikipedia and SRTM')]
class BuildCatalogue extends Command
{
    public function handle(OpenData $data): int
    {
        $builder = new CatalogueBuilder(
            $data,
            new DistrictLocator(),
            json_decode(file_get_contents(database_path('data/local-photos.json')), true),
        );

        $catalogue = $builder->build(fn (string $line) => $this->line($line));

        $path = $this->option('output') ?: database_path('data/catalogue.json');
        file_put_contents($path, json_encode($catalogue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");

        $this->info(sprintf('Wrote %d trails and %d places to %s', count($catalogue['trails']), count($catalogue['places']), $path));

        return self::SUCCESS;
    }
}
