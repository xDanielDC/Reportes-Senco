<?php

namespace App\Observers;

use App\Models\DesignPriority;
use Illuminate\Support\Facades\Auth;

class DesignPriorityObserver
{
    /**
     * Handle the DesignPriority "created" event.
     */
    public function creating(DesignPriority $designPriority): void
    {
        if (Auth::check()) {
            $designPriority->created_id = Auth::id();
            $designPriority->updated_id = Auth::id();
        }
    }

    /**
     * Handle the DesignPriority "updated" event.
     */
    public function update(DesignPriority $designPriority): void
    {
        $designPriority->updated_id = Auth::id();
    }
}
