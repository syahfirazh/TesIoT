<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SensorData; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SensorController extends Controller
{
    /**
     * 1. Fungsi untuk menarik data Cuaca dari BMKG (Parsing XML)
     * Digunakan oleh ESP32 untuk mendapatkan data curah hujan real-time.
     */
    public function getRainFromBMKG()
    {
        try {
            // Mengambil data XML Jawa Barat dari BMKG
            $response = Http::get('https://data.bmkg.go.id/DataMKG/MEWS/DigitalForecast/DigitalForecast-JawaBarat.xml');
            
            if ($response->failed()) return response()->json(['rain' => 0.0]);

            $xml = simplexml_load_string($response->body());
            $rainAmount = 0.0;

            // Cari Area spesifik "Kota Sukabumi"
            foreach ($xml->forecast->area as $area) {
                if ((string)$area['description'] == "Kota Sukabumi") {
                    foreach ($area->parameter as $param) {
                        // Mencari parameter cuaca (id="weather")
                        if ((string)$param['id'] == "weather") {
                            // Kode cuaca terbaru (index 0)
                            $weatherCode = (int)$param->timerange[0]->value;

                            // Konversi Kode BMKG ke Estimasi mm Intensitas
                            // 60: Hujan Ringan, 61: Hujan Sedang, 63: Hujan Lebat, 95: Hujan Petir
                            switch ($weatherCode) {
                                case 60: $rainAmount = 5.0; break; 
                                case 61: $rainAmount = 20.0; break;
                                case 63: $rainAmount = 50.0; break;
                                case 95: 
                                case 97: $rainAmount = 100.0; break;
                                default: $rainAmount = 0.0; break; // Cerah atau Berawan
                            }
                            break 2;
                        }
                    }
                }
            }
            return response()->json(['rain' => $rainAmount]);

        } catch (\Exception $e) {
            Log::error("BMKG Sync Error: " . $e->getMessage());
            return response()->json(['rain' => 0.0]);
        }
    }

    /**
     * 2. Fungsi untuk menerima data dari ESP32 (Metode POST)
     * Menyimpan data gabungan sensor fisik & data ramalan cuaca ke database.
     */
    public function store(Request $request)
    {
        try {
            $data = SensorData::create([
                'gyro_x'        => $request->gyro_x ?? 0,
                'gyro_y'        => $request->gyro_y ?? 0,
                'gyro_z'        => $request->gyro_z ?? 0,
                'soil_moisture' => $request->soil ?? 0,
                'rainfall'      => $request->rain ?? 0, // Data BMKG yang dikirim ulang oleh ESP32
                'suhu'          => $request->suhu ?? 0,
                'status'        => $request->status ?? 0, // 0: AMAN, 1: WASPADA, 2: BAHAYA, 3: S.BAHAYA
                'latitude'      => $request->lat ?? 0,
                'longitude'     => $request->lng ?? 0,
            ]);

            return response()->json(['status' => 'success', 'data' => $data], 201);
        } catch (\Exception $e) {
            Log::error("IoT Store Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 3. Fungsi untuk mengirim data ke Dashboard/Grafik (Metode GET)
     * Digunakan oleh JavaScript di Frontend untuk update chart real-time.
     */
    public function index()
    {
        // Ambil 15 data terakhir untuk grafik real-time
        $data = SensorData::orderBy('id', 'desc')
                ->take(15)
                ->get()
                ->reverse()
                ->values();

        return response()->json($data);
    }
}