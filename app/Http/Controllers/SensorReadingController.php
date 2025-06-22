<?php

namespace App\Http\Controllers;

use App\Models\Reading;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SensorReadingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'temperature' => 'required|numeric',
            'ph_value' => 'required|numeric',
        ]);

        $temperature = round($request->input('temperature'), 2);
        $ph = round($request->input('ph_value'), 2);

        $last = Cache::get('last_reading');

        if ($request->temp_alert || $request->ph_alert) {
            $this->sendAlertNotification($temperature, $ph, $request->temp_alert, $request->ph_alert);
        }


        if (!$last || $last['temperature'] != $temperature || $last['ph_value'] != $ph) {
            $reading = Reading::create([
                'temperature' => $temperature,
                'ph_value' => $ph,
            ]);

            Cache::put('last_reading', [
                'temperature' => $temperature,
                'ph_value' => $ph
            ]);

            event(new \App\Events\SensorReadingUpdated($reading));

            return response()->json(['status' => 'new reading saved']);
        }

        return response()->json(['status' => 'duplicate ignored']);
    }

    private function sendAlertNotification($temperature, $ph, $tempAlert, $phAlert)
    {
        $users = User::whereNotNull('fcm_token')->get();

        Log::info($users);

        foreach ($users as $user) {
            $body = '';

            if ($tempAlert) $body = "Temperature Alert: $temperature °C";
            if ($phAlert) $body = "pH Alert: $ph";

            $response = Http::withToken($this->getFirebaseAccessToken())
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post("https://fcm.googleapis.com/v1/projects/fish-pond-dd2d4/messages:send", [
                    'message' => [
                        'token' => $user->fcm_token,
                        'notification' => [
                            'title' => 'Hi there',
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
