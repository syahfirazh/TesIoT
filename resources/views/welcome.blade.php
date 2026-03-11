<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAWARGI SAJAGA - Final Real-time Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
    
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .dashboard-container { max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: 15px; }
        .card { background: #fff; border: 2px solid #2c3e50; border-radius: 8px; box-shadow: 4px 4px 0px rgba(44, 62, 80, 0.2); padding: 15px; }
        
        /* Header styling */
        .header-top { display: flex; justify-content: space-between; align-items: flex-start; }
        .header-info h3 { margin: 0; color: #2c3e50; }
        
        /* Kotak Derajat Roll & Pitch */
        .degree-container { display: flex; gap: 10px; margin-top: 10px; }
        .degree-box { 
            padding: 8px 15px; 
            border-radius: 6px; 
            border: 1px solid #ddd; 
            display: flex; 
            flex-direction: column; 
            min-width: 100px; 
        }
        .degree-label { font-size: 0.75rem; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
        .degree-value { font-size: 1.4rem; font-weight: bold; color: #2c3e50; }

        .status-badge { padding: 10px 30px; border-radius: 25px; font-weight: bold; color: white; text-transform: uppercase; transition: 0.3s; font-size: 1.2rem; margin-top: 5px; }
        
        /* Status Colors */
        .status-aman { background-color: #2ecc71; box-shadow: 0 0 10px rgba(46, 204, 113, 0.4); }
        .status-waspada { background-color: #f1c40f; color: #2c3e50; box-shadow: 0 0 10px rgba(241, 196, 15, 0.4); }
        .status-bahaya { background-color: #e74c3c; animation: blinker 0.8s linear infinite; }
        @keyframes blinker { 50% { opacity: 0.6; } }
        
        /* Panel layout */
        .top-panel { height: 600px; display: flex; flex-direction: column; }
        .chart-wrapper { flex-grow: 1; min-height: 0; position: relative; margin-top: 20px; }
        
        .bottom-panels { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .value-card { display: flex; flex-direction: column; justify-content: center; align-items: center; height: 160px; }
        .value-number { font-size: 2.8rem; font-weight: bold; color: #2c3e50; line-height: 1; }
        .value-label { color: #7f8c8d; font-weight: bold; text-transform: uppercase; font-size: 0.8rem; margin-top: 10px; }
        
        #map { height: 160px; width: 100%; border-radius: 6px; border: 1px solid #ddd; transition: all 0.5s ease; }
        
        @media (max-width: 900px) { .bottom-panels { grid-template-columns: repeat(2, 1fr); } }
    </style>
</head>
<body>

    <div class="dashboard-container">
        <div class="card top-panel">
            <div class="header-top">
                <div class="header-info">
                    <h3>Monitoring Pergeseran Tanah (Real-time)</h3>
                    <div class="degree-container">
                        <div class="degree-box" style="border-left: 5px solid #e74c3c; background: #fff1f0;">
                            <span class="degree-label" style="color: #e74c3c;">Roll (X)</span>
                            <span class="degree-value" id="rollVal">0°</span>
                        </div>
                        <div class="degree-box" style="border-left: 5px solid #2ecc71; background: #f6ffed;">
                            <span class="degree-label" style="color: #2ecc71;">Pitch (Y)</span>
                            <span class="degree-value" id="pitchVal">0°</span>
                        </div>
                    </div>
                </div>
                <div id="statusTanah" class="status-badge status-aman">AMAN</div>
            </div>
            
            <div class="chart-wrapper">
                <canvas id="gyroChart"></canvas>
            </div>
        </div>

        <div class="bottom-panels">
            <div class="card value-card">
                <div class="value-number" id="rainValue">0<span style="font-size:1.2rem">mm</span></div>
                <div class="value-label">Curah Hujan</div>
            </div>
            
            <div class="card value-card">
                <div class="value-number" id="soilValue">0<span style="font-size:1.2rem">%</span></div>
                <div class="value-label">Kelembapan Tanah</div>
            </div>

            <div class="card value-card" style="border-left: 5px solid #e67e22;">
                <div class="value-number" id="tempValue" style="color: #e67e22;">0<span style="font-size:1.2rem">°C</span></div>
                <div class="value-label">Suhu Lingkungan</div>
            </div>

            <div class="card" style="padding:0; overflow:hidden;">
                <div id="map"></div>
            </div>
        </div>
    </div>

    

    <script>
        // --- 1. Konfigurasi Peta ---
        const myCurrentPos = [-6.920421, 106.926643]; // UMMI Sukabumi
        const map = L.map('map', { zoomControl: false }).setView(myCurrentPos, 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OSM'
        }).addTo(map);
        let marker = L.marker(myCurrentPos).addTo(map);

        // --- 2. Konfigurasi Grafik (Simetris -360 to 360) ---
        const gyroCtx = document.getElementById('gyroChart').getContext('2d');
        const gyroChart = new Chart(gyroCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    { label: 'Roll (X)', borderColor: '#e74c3c', backgroundColor: 'rgba(231, 76, 60, 0.1)', data: [], tension: 0.4, fill: true, pointRadius: 0 },
                    { label: 'Pitch (Y)', borderColor: '#2ecc71', backgroundColor: 'rgba(46, 204, 113, 0.1)', data: [], tension: 0.4, fill: true, pointRadius: 0 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { 
                        min: -360, 
                        max: 360, 
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: {
                            stepSize: 20,
                            callback: (value) => value + '°'
                        },
                        title: { display: true, text: 'Kemiringan (Derajat)' }
                    },
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } }
                },
                plugins: { legend: { position: 'top' } }
            }
        });

        // --- 3. Fungsi Utama ---
        async function updateDashboard() {
            try {
                const response = await fetch('http://192.168.1.3:8080/api/data-sensor?t=' + Date.now());
                const data = await response.json();

                if (!data || data.length === 0) return;

                const limitedData = data.slice(-15);
                let latest = limitedData[limitedData.length - 1];

                // Update Grafik
                gyroChart.data.labels = limitedData.map(row => new Date(row.created_at).toLocaleTimeString('id-ID', {second:'2-digit'}));
                gyroChart.data.datasets[0].data = limitedData.map(row => row.gyro_x);
                gyroChart.data.datasets[1].data = limitedData.map(row => row.gyro_y);
                gyroChart.update('none'); 

                // Update Angka di Widget & Kotak Derajat
                document.getElementById('rollVal').innerText = Math.round(latest.gyro_x || 0) + "°";
                document.getElementById('pitchVal').innerText = Math.round(latest.gyro_y || 0) + "°";
                document.getElementById('rainValue').innerHTML = `${Math.round(latest.rainfall || 0)}<span style="font-size:1.2rem">mm</span>`;
                document.getElementById('soilValue').innerHTML = `${Math.round(latest.soil_moisture || 0)}<span style="font-size:1.2rem">%</span>`;
                document.getElementById('tempValue').innerHTML = `${parseFloat(latest.suhu || 0).toFixed(1)}<span style="font-size:1.2rem">°C</span>`;

                // --- SINKRONISASI STATUS ---
                let statusEl = document.getElementById('statusTanah');
                let rollAbs = Math.abs(parseFloat(latest.gyro_x || 0));
                let pitchAbs = Math.abs(parseFloat(latest.gyro_y || 0));
                let persenSoil = parseInt(latest.soil_moisture || 0);

                let kemiringan = Math.max(rollAbs, pitchAbs);
                let statusLevel = 0;

                if (kemiringan < 15) statusLevel = 0; 
                else if (kemiringan < 30) statusLevel = 1; 
                else if (kemiringan < 45) statusLevel = 2; 
                else statusLevel = 3;

                if (persenSoil > 80 && statusLevel < 3) statusLevel += 1;

                if (statusLevel === 0) {
                    statusEl.innerText = "AMAN";
                    statusEl.className = "status-badge status-aman";
                } else if (statusLevel === 1) {
                    statusEl.innerText = "WASPADA";
                    statusEl.className = "status-badge status-waspada";
                } else {
                    statusEl.innerText = statusLevel === 3 ? "SANGAT BAHAYA" : "BAHAYA";
                    statusEl.className = "status-badge status-bahaya";
                }

                // --- UPDATE GPS ---
                let lat = parseFloat(latest.latitude);
                let lng = parseFloat(latest.longitude);

                if (lat !== 0 && !isNaN(lat) && lng !== 0) {
                    const devicePos = [lat, lng];
                    marker.setLatLng(devicePos);
                    if (map.getCenter().distanceTo(devicePos) > 5) { 
                        map.panTo(devicePos, { animate: true, duration: 0.5 });
                    }
                } else {
                    marker.setLatLng(myCurrentPos);
                    if (map.getCenter().distanceTo(myCurrentPos) > 10) { map.setView(myCurrentPos, 15); }
                }

            } catch (error) { console.error("Sync Error."); }
        }

        setInterval(updateDashboard, 500); 
        updateDashboard(); 
    </script>
</body>
</html>