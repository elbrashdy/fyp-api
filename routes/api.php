<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SensorReadingController;
use App\Models\Reading;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Test;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('test', function (Request $request) {
    Test::create([
        'data' => $request
    ]);
    return response()->json([
        'message' => 'Data Saved!'
    ], 201);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(AuthController::class)
    ->prefix('auth')->group(function(){
        Route::post('/register', 'register');
        Route::post('/login', 'login');
    });


Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::controller(NotificationController::class)
        ->prefix('notifications')
        ->group(function () {
        Route::get('get-recent', 'getRecentNotifications');
        Route::post('save-token', 'saveNotificationToken');
    });

    Route::controller(SensorReadingController::class)->prefix('readings')->group(function () {
        Route::get('', 'index');
        Route::get('history', 'weeklyHistory');
    });



});

Route::controller(SensorReadingController::class)->group(function () {
    Route::post('/sensor-listener', 'store');
});



