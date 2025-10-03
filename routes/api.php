<?php

use App\Http\Controllers\MonitoringController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:api'])->group(function (): void {
    Route::get('/monitoring/metrics', MonitoringController::class)
        ->name('monitoring.metrics');
});
