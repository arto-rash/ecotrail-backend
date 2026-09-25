<?php

namespace App\Console\Commands;

use App\Models\Review;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reviews:delete {id?* : Review ids to delete} {--latest=0 : Show the latest N reviews instead}')]
#[Description('Show recent reviews or delete reviews by id')]
class DeleteReview extends Command
{
    public function handle(): int
    {
        if ($n = (int) $this->option('latest')) {
            $this->table(['Id', 'On', 'Author', 'Rating', 'Text'], Review::with('reviewable')->latest()->limit($n)->get()->map(fn (Review $r) => [
                $r->id, $r->reviewable?->slug ?? '—', $r->author_name, $r->rating, mb_strimwidth($r->body, 0, 70, '…'),
            ]));

            return self::SUCCESS;
        }

        $ids = $this->argument('id');
        if (! $ids) {
            $this->error('Pass review ids, or use --latest=20 to find them.');

            return self::FAILURE;
        }
        $this->info('Deleted '.Review::whereKey($ids)->delete().' review(s).');

        return self::SUCCESS;
    }
}
