<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $temperature;
    public $ph;
    public $temperatureAlert;
    public $phAlert;

    /**
     * Create a new job instance.
     */
    public function __construct($temperature, $ph, $temperatureAlert, $phAlert)
    {
        $this->temperature = $temperature;
        $this->ph = $ph;
        $this->temperatureAlert = $temperatureAlert;
        $this->phAlert = $phAlert;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->sendAlertNotification(
            $this->temperature,
            $this->ph,
            $this->temperatureAlert,
            $this->phAlert
        );
    }

    private function sendAlertNotification($temperature, $ph, $tempAlert, $phAlert): void
    {
        $users = User::whereNotNull('fcm_token')->get();


        foreach ($users as $user) {
            $title = '';
            $body = '';

            if ($tempAlert && $phAlert) {
                $title = 'Temperature & pH Alert';

                $tempMessage = $temperature > 33
                    ? "Temperature rose to {$temperature}°C"
                    : "Temperature dropped to {$temperature}°C";

                $phMessage = $ph > 9.3
                    ? "pH rose to {$ph}"
                    : "pH dropped to {$ph}";

                $body = "{$tempMessage}. {$phMessage}.";
            } elseif ($tempAlert) {
                $title = 'Temperature Alert';
                $body = $temperature > 33
                    ? "Temperature rose to {$temperature}°C"
                    : "Temperature dropped to {$temperature}°C";
            } elseif ($phAlert) {
                $title = 'pH Alert';
                $body = $ph > 9.3
                    ? "pH rose to {$ph}"
                    : "pH dropped to {$ph}";
            }

            Notification::create([
                'title' => $title,
                'message' => $body,
            ]);


            $response = Http::withToken($this->getFirebaseAccessToken())
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post("https://fcm.googleapis.com/v1/projects/fish-pond-dd2d4/messages:send", [
                    'message' => [
                        'token' => $user->fcm_token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'android' => [
                            'priority' => 'high',
                            'notification' => [
                                'sound' => 'default'
                            ]
                        ],
                        'apns' => [
                            'payload' => [
                                'aps' => [
                                    'sound' => 'default'
                                ]
                            ]
                        ]
                    ]
                ]);
        }

    }

    function getFirebaseAccessToken(): string
    {
        $credentials = json_decode(file_get_contents(storage_path('app/firebase/firebase_credentials.json')), true);

        $now = time();
        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = base64_encode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $credentials['token_uri'],
            'exp' => $now + 3600,
            'iat' => $now
        ]));

        $data = $header . '.' . $claim;
        openssl_sign($data, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
        $jwt = $data . '.' . base64_encode($signature);

        // Get the access token
        $response = Http::asForm()->post($credentials['token_uri'], [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        return $response->json()['access_token'];
    }
}
