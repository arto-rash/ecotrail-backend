<?php

namespace Database\Seeders;

use App\Catalogue\CatalogueSync;
use Illuminate\Database\Seeder;

class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        CatalogueSync::fromDefaults()->run();
    }
}
