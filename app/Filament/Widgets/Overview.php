<?php

namespace App\Filament\Widgets;

use App\Enums\TrailStatus;
use App\Models\Guide;
use App\Models\GuideApplication;
use App\Models\Review;
use App\Models\Trail;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class Overview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Pending trail submissions', Trail::where('source', 'community')->where('status', TrailStatus::Pending)->count()),
            Stat::make('New partner applications', GuideApplication::where('status', 'new')->count()),
            Stat::make('Contact reveals', Guide::sum('contact_reveals'))->description('Across all guide profiles'),
            Stat::make('Reviews this week', Review::where('created_at', '>=', now()->subWeek())->count()),
        ];
    }
}
