<?php

namespace App\Observers;

use App\Models\Report;
use Illuminate\Support\Facades\Auth;

class ReportObserver
{
    /**
     * Handle the Report "created" event.
     */
    public function created(Report $report): void
    {
        $report->created_id = Auth::id();
        $report->updated_id = Auth::id();
    }

    /**
     * Handle the Report "updated" event.
     */
    public function updated(Report $report): void
    {
        $report->created_id = Auth::id();
        $report->updated_id = Auth::id();
    }
}
