<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendReadingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $reading;

    /**
     * Create a new job instance.
     */
    public function __construct($reading)
    {
        $this->reading = $reading;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        event(new \App\Events\SensorReadingUpdated($this->reading));
    }
}
