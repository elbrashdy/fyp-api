<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{

    public function getRecentNotifications(Request $request): JsonResponse
    {
        $notification = Notification::where('created_at', '>=', Carbon::now()->subDays(7))->get();
        return $this->success($notification, 'Recently notifications');
    }
    public function saveNotificationToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        Log::info($request);

        $user = $request->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json(['message' => 'Token saved']);
    }

//    public function saveToken(Request $request) {
//        Cache::put('fcm', [
//            'user_id' => '1',
//            'fcm_token' => $request->fcm_token
//        ]);
//
//        return response()->json(['success' => true]);
//    }

}
