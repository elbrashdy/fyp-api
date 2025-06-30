<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SensorReadingUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $reading;

    public function __construct($reading)
    {
        $this->reading = $reading;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('sensor-channel');
    }

    public function broadcastWith(): array
    {
        return [
            'reading' => [
                'temperature' => $this->reading['temperature'],
                'ph_value' => $this->reading['ph_value']
            ]
        ];
    }



    public function broadcastAs(): string
    {
        return 'reading-updated';
    }
}
