<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SensorData; 
use Illuminate\Support\Facades\Log; // Namespace Log yang benar

class SensorController extends Controller
{
    /**
     * 1. Fungsi untuk menerima data dari ESP32 (Metode POST)
     */
    public function store(Request $request)
{
    try {
        $data = SensorData::create([
            'gyro_x'        => $request->gyro_x ?? 0,
            'gyro_y'        => $request->gyro_y ?? 0,
            'gyro_z'        => $request->gyro_z ?? 0,
            'soil_moisture' => $request->soil ?? 0,
            'rainfall'      => $request->rain ?? 0,
            'suhu'          => $request->suhu ?? 0, // Default 0 jika sensor mati
            'latitude'      => $request->lat ?? 0,
            'longitude'     => $request->lng ?? 0,
        ]);

        return response()->json(['status' => 'success'], 201);
    } catch (\Exception $e) {
        Log::error("IoT Error: " . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    /**
     * 2. Fungsi untuk mengirim data ke Dashboard/Grafik (Metode GET)
     */
    public function index()
    {
        // Ambil 15 data terakhir untuk grafik
        $data = SensorData::orderBy('id', 'desc')
                ->take(15)
                ->get()
                ->reverse()
                ->values();

        return response()->json($data);
    }
}