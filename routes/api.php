<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SensorController;

/*
|--------------------------------------------------------------------------
| API Routes - SAWARGI SAJAGA
|--------------------------------------------------------------------------
*/

// 1. Endpoint untuk ESP32 mengambil data curah hujan resmi dari BMKG
// ESP32 memanggil rute ini sebelum mengirim data sensor ke database
Route::get('/get-bmkg-rain', [SensorController::class, 'getRainFromBMKG']);

// 2. Endpoint untuk menerima data sensor dari ESP32 (Metode POST)
// Dipanggil oleh ESP32 setiap interval pengiriman (misal 1-2 detik)
Route::post('/data-sensor', [SensorController::class, 'store']);

// 3. Endpoint untuk Dashboard mengambil data terbaru (Metode GET)
// Dipanggil oleh JavaScript di website (AJAX/Fetch) untuk update grafik & widget
Route::get('/data-sensor', [SensorController::class, 'index']);

// 4. (Opsional) Rute untuk mengecek status API
Route::get('/status', function () {
    return response()->json(['status' => 'Sistem SAWARGI SAJAGA Aktif', 'time' => now()]);
});