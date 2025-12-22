<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MatchOrders implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public \App\Models\Order $order)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\MatchingService $service): void
    {
        $service->match($this->order);
    }
}
