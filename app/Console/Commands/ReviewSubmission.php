<?php

namespace App\Console\Commands;

use App\Enums\TrailStatus;
use App\Models\Trail;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('submissions:approve {slug} {--reject : Reject instead of publishing}')]
#[Description('Publish (or reject) a community trail submission')]
class ReviewSubmission extends Command
{
    public function handle(): int
    {
        $trail = Trail::where('source', 'community')->where('slug', $this->argument('slug'))->first();
        if (! $trail) {
            $this->error('No community submission with that slug. See submissions:list.');

            return self::FAILURE;
        }

        $trail->update(['status' => $this->option('reject') ? TrailStatus::Rejected : TrailStatus::Published]);
        $this->info("{$trail->slug} is now {$trail->status->value}.");

        return self::SUCCESS;
    }
}
