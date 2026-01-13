<?php

namespace App\Observers;

use App\Models\DesignRequest;
use Illuminate\Support\Facades\Auth;

class DesignRequestObserver
{
    /**
     * Handle the DesignRequest "created" event.
     */
    public function creating(DesignRequest $designRequest): void
    {
        $designRequest->created_id = Auth::id();
        $designRequest->updated_id = Auth::id();
    }

    /**
     * Handle the DesignRequest "updated" event.
     */
    public function updated(DesignRequest $designRequest): void
    {
        $designRequest->created_id = Auth::id();
        $designRequest->updated_id = Auth::id();
    }
}
