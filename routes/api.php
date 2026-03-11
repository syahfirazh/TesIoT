<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SensorController; // Pastikan baris ini ada!

// Rute ini yang akan dipanggil oleh ESP32 (POST) dan Dashboard (GET)
Route::get('/data-sensor', [SensorController::class, 'index']);
Route::post('/data-sensor', [SensorController::class, 'store']);