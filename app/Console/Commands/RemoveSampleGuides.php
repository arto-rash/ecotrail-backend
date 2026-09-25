<?php

namespace App\Console\Commands;

use App\Models\Guide;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('guides:remove-samples')]
#[Description('Delete the placeholder sample guides (and their tours)')]
class RemoveSampleGuides extends Command
{
    public function handle(): int
    {
        $this->info('Removed '.Guide::where('is_sample', true)->delete().' sample listing(s).');

        return self::SUCCESS;
    }
}
