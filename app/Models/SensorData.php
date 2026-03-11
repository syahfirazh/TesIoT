<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorData extends Model
{
    protected $table = 'sensor_data'; // Pastikan nama tabel benar

    // WAJIB ADA: Daftarkan semua kolom agar Laravel mau menyimpannya
    protected $fillable = [
    'gyro_x', 
    'gyro_y', 
    'gyro_z', 
    'soil_moisture', 
    'rainfall', 
    'suhu',
    'latitude', 
    'longitude'
];
}