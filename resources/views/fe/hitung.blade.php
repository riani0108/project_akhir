<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css">

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://unpkg.com/leaflet-geometryutil"></script>

<script>
    window.PELANGGAN_ID = "{{ auth('pelanggan')->id() }}";
</script>


@vite(['resources/js/app.js'])


<body>

    <div class="btnBlock">
        <button class="btn-map" onclick=fullScreenView()>View Full Screen</button>
        <a href="{{ route('input-lokasi.index') }}"
            class="input-lokasi"
            id="btn-input-lokasi">
            Input Lokasi
        </a>

    </div>

    <div id="search-map">
        <div class="search">
            <input type="text" id="search-input" placeholder="Cari lokasi...">
            <button type="submit" id="search-btn"><i class="fas fa-search"></i></button>
        </div>
    </div>


    <div class="formBlock">
        <form id="#">
            <input type="text" name="start" class="input" id="start" placeholder="Choose Starting Point" readonly>
            <input type="text" name="end" class="input" id="destination" placeholder="Choose Destination" readonly>
            <button type="submit" class="btn-direction">Get Direction</button>
        </form>
        <small id="mapHint" style="color:#555;">
            Klik peta untuk memilih <b>Starting Point</b>
        </small>
    </div>

    <div id="map"></div>

    <div id="ptmp-ui">
        <!-- PANEL ANTENNA -->
        <div class="antenna-panel">
            <div class="panel-header">
                <i class="fas fa-wifi"></i>
                Link Configuration

                <!-- 🔥 CLOSE BUTTON -->
                <button class="panel-close" onclick="hideAntennaPanel()">✕</button>
            </div>

            <div class="panel-group">
                <label>Access Point Antenna</label>
                <select id="ap-antenna">
                    <option value="sector_90_17">Sector 90° • 17 dBi</option>
                    <option value="sector_120_15">Sector 120° • 15 dBi</option>
                </select>
            </div>

            <div class="panel-group">
                <label>Client Antenna</label>
                <select id="client-antenna">
                    <option value="nanobeam_16">NanoBeam • 16 dBi</option>
                    <option value="litebeam_23">LiteBeam • 23 dBi</option>
                    <option value="dish_30">Dish • 30 dBi</option>
                </select>
            </div>

            <div id="link-status" class="link-status weak">
                Weak Signal
            </div>

            <button
                class="btn-remove-client"
                onclick="removeClient(activeClientId)">
                ❌ Remove Client
            </button>

        </div>


    </div>


    <div id="heatmap-legend" class="heatmap-legend">
        <span>Weak</span>
        <div class="heatmap-bar"></div>
        <span>Strong</span>
    </div>

    <!-- <button onclick="clearPtMP()">Clear PtMP</button> -->

    <div class="coordinate"></div>

    <!-- Tooltip mengikuti mouse -->
    <div id="mouse-follow"></div>

    <!-- Context Menu -->
    <div id="context-menu">
        <div class="context-header">
            <span>Menu</span>
            <button class="context-close" onclick="closeContextMenu()">✖</button>
        </div>
        <ul>
            <li onclick="copyCoord()">Copy Koordinat</li>
            <li onclick="zoomHere()">Zoom di Sini</li>
            <li onclick="removeMarkers()">Hapus Marker</li>
            <li onclick="startingPointHere()">Pilih sebagai Starting Point</li>
            <li onclick="destinationHere()">Pilih sebagai Destination</li>
        </ul>
    </div>



    <div class="uisp-page">
        <!-- LEFT COLUMN: FORM & CHART -->
        <div class="left-column">
            <!-- Form Input -->
            <div class="uisp-card form-card">
                <div class="uisp-card-header">
                    <i class="fas fa-broadcast-tower"></i>
                    <span>Point-to-Point Link Calculator</span>
                </div>

                <form id="calcForm" class="uisp-form">
                    <div class="uisp-grid">
                        <div class="form-group">
                            <label>Tower A Coordinate</label>
                            <input id="coord1" placeholder="-6.2088, 106.8456" required>
                        </div>

                        <div class="form-group">
                            <label>Tower A Height (m)</label>
                            <input type="number" id="height1" value="50" min="10" max="500">
                        </div>

                        <div class="form-group">
                            <label>Tower B Coordinate</label>
                            <input id="coord2" placeholder="-7.7956, 110.3695" required>
                        </div>

                        <div class="form-group">
                            <label>Tower B Height (m)</label>
                            <input type="number" id="height2" value="70" min="10" max="500">
                        </div>

                        <div class="form-group">
                            <label>Frequency (GHz)</label>
                            <input type="number" id="frequency" step="0.1" value="6" min="1" max="80">
                        </div>

                        <div class="form-group">
                            <label>K-Factor</label>
                            <input type="number" id="kfactor" step="0.01" value="1.33" min="0.5" max="2">
                        </div>
                    </div>

                    <div class="uisp-action">
                        <button type="button" onclick="performCalculation()" class="btn-calculate">
                            <i class="fas fa-play"></i> Calculate Link
                        </button>
                    </div>
                </form>
            </div>


            <!-- Chart -->
            <div class="uisp-card chart-card">
                <div class="uisp-card-header">
                    <i class="fas fa-chart-line"></i>
                    <span>Terrain Profile & LOS Visualization</span>
                </div>
                <div class="chart-container">
                    <canvas id="terrainChart" height="300"></canvas>
                </div>
                <div class="chart-legend">
                    <div class="legend-item">
                        <span class="legend-color" style="background: #FF0000"></span>
                        <span>Line of Sight (LOS)</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color" style="background: #8B4513"></span>
                        <span>Ground Elevation</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color" style="background: rgba(0,170,0,0.15)"></span>
                        <span>Fresnel Zone</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color" style="background: #FF4500; border: 1px dashed #FF4500"></span>
                        <span>60% Clearance</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: RESULTS -->
        <div class="right-column">
            <div class="results-container">
                <div class="results-header">
                    <div class="header-left">
                        <i class="fas fa-chart-bar"></i>
                        <h3>Link Analysis Results</h3>
                    </div>
                    <div class="header-right">
                        <span class="last-updated" id="lastUpdated">-</span>
                    </div>
                </div>

                <div class="results-content" id="output">
                    <!-- Initial empty state -->
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-satellite-dish"></i>
                        </div>
                        <h4>Ready to Calculate</h4>
                        <p>Fill in the parameters and click "Calculate Link" to analyze your point-to-point connection.</p>
                        <div class="sample-data">
                            <p><strong>Sample coordinates:</strong></p>
                            <p>Tower A: -6.2088, 106.8456 (Jakarta)</p>
                            <p>Tower B: -7.7956, 110.3695 (Yogyakarta)</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Bar -->
                <div class="quick-stats-bar" id="quickStats" style="display: none;">
                    <div class="stat-item">
                        <span class="stat-label">Distance</span>
                        <span class="stat-value" id="statDistance">-</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Status</span>
                        <span class="stat-value status-badge" id="statStatus">-</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Min Clearance</span>
                        <span class="stat-value" id="statClearance">-</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Freq</span>
                        <span class="stat-value" id="statFreq">-</span>
                    </div>
                </div>
            </div>

            <!-- Additional Analysis -->
            <div class="analysis-container" id="detailedAnalysis" style="display: none;">
                <div class="analysis-tabs">
                    <button class="tab-btn active" onclick="showTab('tab-overview')">Overview</button>
                    <button class="tab-btn" onclick="showTab('tab-critical')">Critical Points</button>
                    <button class="tab-btn" onclick="showTab('tab-recommendations')">Recommendations</button>
                </div>

                <div class="tab-content active" id="tab-overview">
                    <div class="data-grid">
                        <div class="data-card">
                            <h5>Tower Information</h5>
                            <table class="data-table">
                                <tr>
                                    <td>Elevation A</td>
                                    <td id="dataElevA">- m</td>
                                </tr>
                                <tr>
                                    <td>Elevation B</td>
                                    <td id="dataElevB">- m</td>
                                </tr>
                                <tr>
                                    <td>Total Height A</td>
                                    <td id="dataHeightA">- m</td>
                                </tr>
                                <tr>
                                    <td>Total Height B</td>
                                    <td id="dataHeightB">- m</td>
                                </tr>
                            </table>
                        </div>

                        <div class="data-card">
                            <h5>Link Parameters</h5>
                            <table class="data-table">
                                <tr>
                                    <td>Frequency</td>
                                    <td id="dataFreq">- GHz</td>
                                </tr>
                                <tr>
                                    <td>K-Factor</td>
                                    <td id="dataKFactor">-</td>
                                </tr>
                                <tr>
                                    <td>Earth Bulge Max</td>
                                    <td id="dataBulge">- m</td>
                                </tr>
                                <tr>
                                    <td>Fresnel Radius Max</td>
                                    <td id="dataFresnel">- m</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="tab-critical">
                    <div class="critical-points-list" id="criticalList">
                        <!-- Critical points will be populated here -->
                    </div>
                </div>

                <div class="tab-content" id="tab-recommendations">
                    <div class="recommendations-list" id="recommendationsList">
                        <!-- Recommendations will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>