<?php

namespace App\Observers;

use App\Models\DesignState;
use Illuminate\Support\Facades\Auth;

class DesignStateObserver
{
    /**
     * Handle the DesignState "created" event.
     */
    public function creating(DesignState $designState): void
    {
        if (Auth::check()) {
            $designState->created_id = Auth::id();
            $designState->updated_id = Auth::id();
        }
    }

    /**
     * Handle the DesignState "updated" event.
     */
    public function updated(DesignState $designState): void
    {
        $designState->created_id = Auth::id();
    }
}
