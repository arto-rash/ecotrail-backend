<?php

namespace App\Console\Commands;

use App\Enums\TrailStatus;
use App\Models\Trail;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('submissions:list {--all : Include approved and rejected submissions}')]
#[Description('List trails shared by the community, pending ones by default')]
class ListSubmissions extends Command
{
    public function handle(): int
    {
        $trails = Trail::query()->where('source', 'community')
            ->when(! $this->option('all'), fn ($q) => $q->where('status', TrailStatus::Pending))
            ->with('district')->latest()->get();

        if ($trails->isEmpty()) {
            $this->info('No submissions.');

            return self::SUCCESS;
        }

        $this->table(['Slug', 'Name', 'District', 'km', 'Status', 'Submitted by', 'Date'], $trails->map(fn (Trail $t) => [
            $t->slug,
            $t->localized('name', true),
            $t->district?->localized('name', true) ?? '—',
            $t->distance_km,
            $t->status->value,
            "{$t->submitter_name} <{$t->submitter_email}>",
            $t->created_at->toDateString(),
        ]));

        return self::SUCCESS;
    }
}
