<?php

namespace App\Http\Controllers;

use App\Jobs\SendNotificationJob;
use App\Jobs\SendReadingJob;
use App\Models\Reading;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SensorReadingController extends Controller
{

    public function index(): JsonResponse
    {
        $latestReadings = Reading::latest()->take(30)->get()->reverse()->values();
        return $this->success($latestReadings, '');
    }
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
            SendNotificationJob::dispatch($temperature, $ph, $request->temp_alert, $request->ph_alert);
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

            SendReadingJob::dispatch($reading);

            return response()->json(['status' => 'new reading saved']);
        }

        return response()->json(['status' => 'duplicate ignored']);
    }




    public function weeklyHistory(): JsonResponse
    {
        $readings = DB::table('readings')
            ->selectRaw("
            DATE(DATE_SUB(created_at, INTERVAL (WEEKDAY(created_at) + 1) % 7 DAY)) as week_start,
            AVG(temperature) as average_temperature,
            AVG(ph_value) as average_ph
        ")
            ->groupBy('week_start')
            ->orderByDesc('week_start')
            ->get();

        $formatted = $readings->map(function ($item) {
            $start = Carbon::parse($item->week_start)->startOfDay();
            $end = $start->copy()->addDays(6);

            return [
                'week' => $start->format('d F') . ' to ' . $end->format('d F') . ' of ' . $start->format('Y'),
                'average_temperature' => round($item->average_temperature, 2),
                'average_ph' => round($item->average_ph, 2),
            ];
        });

        return $this->success($formatted, '');
    }


}
