<?php

namespace App\Observers;

use App\Models\DesignTimeState;
use Illuminate\Support\Facades\Auth;

class DesignTimeStateObserver
{
    /**
     * Handle the DesignTimeState "created" event.
     */
    public function creating(DesignTimeState $designTimeState): void
    {
        if (Auth::check()) {
            $designTimeState->created_id = Auth::id();
            $designTimeState->updated_id = Auth::id();
        }
    }

    /**
     * Handle the DesignTimeState "updated" event.
     */
    public function updated(DesignTimeState $designTimeState): void
    {
        $designTimeState->updated_id = Auth::id();
    }
}
