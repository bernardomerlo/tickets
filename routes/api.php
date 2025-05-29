<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\TicketController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureUserHasRole;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::prefix('tickets')->group(function () {
            Route::get('/', [TicketController::class, 'listTickets']);
            Route::post('/', [TicketController::class, 'createTicket'])->middleware(EnsureUserHasRole::class . ':cliente');
            Route::patch('/{id}/assign', [TicketController::class, 'assignTicket'])->middleware(EnsureUserHasRole::class . ':admin');
            Route::patch('/{id}/close', [TicketController::class, 'closeTicket'])->middleware(EnsureUserHasRole::class . ':admin|atendente');
            Route::post('/{id}/addMessage', [TicketController::class, 'addMessage']);
        });
    });
});
