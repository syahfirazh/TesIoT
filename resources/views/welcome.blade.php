<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAWARGI SAJAGA - Final Real-time Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <style>
        :root {
            --primary-blue: #2c3e50;
            --bg-body: #f4f7f6;
            --card-white: #ffffff;
        }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f4f7f6; 
            margin: 0; 
            padding: 20px; 
        }

        .dashboard-container { max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: 15px; }
        
        .card { 
            background: var(--card-white); 
            border: 1px solid #2c3e50; 
            border-radius: 8px; 
            box-shadow: 4px 4px 0px rgba(44, 62, 80, 0.2); 
            padding: 15px; 
        }

        .header-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .header-info h3 { margin: 0 0 15px 0; color: #2c3e50; font-size: 1.2rem; }

        .degree-container { display: flex; gap: 10px; }
        .degree-box { 
            padding: 10px 20px; 
            border-radius: 8px; 
            min-width: 120px;
            border: 1px solid #ddd;
        }
        .box-roll { background-color: #fff1f0; border-left: 5px solid #e74c3c; }
        .box-pitch { background-color: #f6ffed; border-left: 5px solid #2ecc71; }

        .degree-label { font-size: 0.75rem; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; display: block; }
        .degree-label.roll { color: #e74c3c; }
        .degree-label.pitch { color: #2ecc71; }
        .degree-value { font-size: 1.8rem; font-weight: bold; color: #2c3e50; }

        .status-badge { 
            padding: 12px 40px; 
            border-radius: 50px; 
            font-weight: bold; 
            color: white; 
            text-transform: uppercase; 
            font-size: 1.1rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .status-aman { background-color: #2ecc71; }
        .status-waspada { background-color: #f1c40f; color: #2c3e50; }
        .status-bahaya { background-color: #ef4444; animation: pulse 1s infinite; }
        
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.8; } 100% { opacity: 1; } }

        .top-panel { height: 600px; display: flex; flex-direction: column; }
        .chart-wrapper { flex-grow: 1; min-height: 0; margin-top: 20px; }

        .bottom-panels { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .value-card { display: flex; flex-direction: column; justify-content: center; align-items: center; height: 180px; position: relative; }
        .value-number { font-size: 3rem; font-weight: bold; color: #2c3e50; line-height: 1; }
        .value-label { color: #7f8c8d; font-weight: bold; text-transform: uppercase; font-size: 0.8rem; margin-top: 10px; }
        
        /* Keterangan Cuaca Real-time */
        .weather-desc {
            font-size: 0.7rem;
            font-weight: 800;
            margin-top: 5px;
            padding: 2px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        #map { height: 180px; width: 100%; border-radius: 8px; border: 1px solid #ddd; }

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
                        <div class="degree-box box-roll">
                            <span class="degree-label roll">Roll (X)</span>
                            <span class="degree-value" id="rollVal">0°</span>
                        </div>
                        <div class="degree-box box-pitch">
                            <span class="degree-label pitch">Pitch (Y)</span>
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
                <div id="weatherDesc" class="weather-desc" style="color: #0ea5e9; background: #e0f2fe;">CERAH</div>
                <div class="value-label">CURAH HUJAN</div>
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
        const myCurrentPos = [-6.920421, 106.926643]; 
        const map = L.map('map', { zoomControl: false }).setView(myCurrentPos, 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        let marker = L.marker(myCurrentPos).addTo(map);

        const gyroCtx = document.getElementById('gyroChart').getContext('2d');
        const gyroChart = new Chart(gyroCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    { label: 'Roll (X)', borderColor: '#e74c3c', backgroundColor: 'rgba(231, 76, 60, 0.05)', data: [], tension: 0.4, fill: true, pointRadius: 0, borderWidth: 3 },
                    { label: 'Pitch (Y)', borderColor: '#2ecc71', backgroundColor: 'rgba(46, 204, 113, 0.05)', data: [], tension: 0.4, fill: true, pointRadius: 0, borderWidth: 3 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { 
                        min: -360, max: 360,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { stepSize: 40, callback: (v) => v + '°' }
                    },
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } }
                },
                plugins: { legend: { position: 'top' } }
            }
        });

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

                // Update Angka Dasar
                document.getElementById('rollVal').innerText = Math.round(latest.gyro_x || 0) + "°";
                document.getElementById('pitchVal').innerText = Math.round(latest.gyro_y || 0) + "°";
                document.getElementById('soilValue').innerHTML = `${Math.round(latest.soil_moisture || 0)}<span style="font-size:1.2rem">%</span>`;
                document.getElementById('tempValue').innerHTML = `${parseFloat(latest.suhu || 0).toFixed(1)}<span style="font-size:1.2rem">°C</span>`;

                // --- LOGIKA KETERANGAN CUACA REAL-TIME ---
                const rainVal = parseFloat(latest.rainfall || 0);
                const rainEl = document.getElementById('rainValue');
                const descEl = document.getElementById('weatherDesc');
                
                rainEl.innerHTML = `${rainVal}<span style="font-size:1.2rem">mm</span>`;

                if (rainVal === 0) {
                    descEl.innerText = "CERAH";
                    descEl.style.color = "#0ea5e9";
                    descEl.style.background = "#e0f2fe";
                } else if (rainVal <= 5) {
                    descEl.innerText = "HUJAN RINGAN";
                    descEl.style.color = "#3b82f6";
                    descEl.style.background = "#dbeafe";
                } else if (rainVal <= 20) {
                    descEl.innerText = "HUJAN SEDANG";
                    descEl.style.color = "#2563eb";
                    descEl.style.background = "#bfdbfe";
                } else {
                    descEl.innerText = "HUJAN LEBAT";
                    descEl.style.color = "#ffffff";
                    descEl.style.background = "#1d4ed8";
                }

                // Logika Status
                let statusEl = document.getElementById('statusTanah');
                let rollAbs = Math.abs(parseFloat(latest.gyro_x || 0));
                let pitchAbs = Math.abs(parseFloat(latest.gyro_y || 0));
                let kemiringan = Math.max(rollAbs, pitchAbs);
                let soil = parseInt(latest.soil_moisture || 0);

                let level = 0;
                if (kemiringan < 15) level = 0;
                else if (kemiringan < 30) level = 1;
                else if (kemiringan < 45) level = 2;
                else level = 3;

                if (soil > 80 && level < 3) level += 1;

                if (level === 0) {
                    statusEl.innerText = "AMAN";
                    statusEl.className = "status-badge status-aman";
                } else if (level === 1) {
                    statusEl.innerText = "WASPADA";
                    statusEl.className = "status-badge status-waspada";
                } else if (level === 2) {
                    statusEl.innerText = "BAHAYA";
                    statusEl.className = "status-badge status-bahaya";
                } else {
                    statusEl.innerText = "SANGAT BAHAYA";
                    statusEl.className = "status-badge status-bahaya";
                }

                // Update GPS
                let lat = parseFloat(latest.latitude);
                let lng = parseFloat(latest.longitude);
                if (lat !== 0 && !isNaN(lat)) {
                    marker.setLatLng([lat, lng]);
                    if (map.getCenter().distanceTo([lat, lng]) > 10) map.panTo([lat, lng]);
                }

            } catch (error) { console.error("Sync Error."); }
        }

        setInterval(updateDashboard, 500); 
        updateDashboard(); 
    </script>
</body>
</html>