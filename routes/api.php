<?php

use App\Http\Controllers\Api\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/health', function (){
    return response()->json(['status' => 'ok','service' => 'OrderFlow API'],200);
});
Route::group(['prefix' => 'orders'],function(){
    Route::get('', [OrderController::class,'index']);
    Route::get('/{order}', [OrderController::class,'show']);
    Route::post('', [OrderController::class,'store']);
    Route::patch('/{order}', [OrderController::class,'update']);
    Route::delete('/{order}', [OrderController::class,'destroy']);
});