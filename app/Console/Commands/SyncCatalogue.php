<?php

namespace App\Console\Commands;

use App\Catalogue\CatalogueSync;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('catalogue:sync')]
#[Description('Load database/data/catalogue.json, regions and districts into the database')]
class SyncCatalogue extends Command
{
    public function handle(): int
    {
        $stats = CatalogueSync::fromDefaults()->run();
        $this->info(collect($stats)->map(fn ($n, $k) => "$k: $n")->implode(', '));

        return self::SUCCESS;
    }
}
