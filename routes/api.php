<?php

use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\InboxController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\ProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('me', MeController::class);

    Route::apiResource('projects', ProjectController::class);

    Route::get('projects/{project}/items', [ItemController::class, 'index']);
    Route::post('projects/{project}/items', [ItemController::class, 'store']);

    Route::get('items/{item}', [ItemController::class, 'show']);
    Route::patch('items/{item}', [ItemController::class, 'update']);
    Route::delete('items/{item}', [ItemController::class, 'destroy']);
    Route::post('items/{item}/transition', [ItemController::class, 'transition']);

    Route::get('items/{item}/comments', [CommentController::class, 'index']);
    Route::post('items/{item}/comments', [CommentController::class, 'store']);

    Route::post('inbox', [InboxController::class, 'store']);
    Route::get('inbox/{inboxMessage}', [InboxController::class, 'show']);
});
