<?php

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
