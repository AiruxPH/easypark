<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realistic Parking Map (Prototype)</title>
    <style>
        body {
            margin: 0;
            background: #222;
            color: #fff;
            font-family: sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        h1 {
            margin-bottom: 20px;
        }

        .map-container {
            width: 90%;
            max-width: 1000px;
            background: #333;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            position: relative;
        }

        svg {
            width: 100%;
            height: auto;
            display: block;
        }

        /* Slot Styles */
        .slot-shape {
            cursor: pointer;
            transition: fill 0.3s, stroke-width 0.2s;
            stroke: #fff;
            stroke-width: 2px;
        }

        .slot-shape:hover {
            opacity: 0.8;
            stroke-width: 4px;
        }

        .status-available {
            fill: #28a745;
        }

        .status-occupied {
            fill: #dc3545;
        }

        .status-reserved {
            fill: #ffc107;
        }

        .status-unavailable {
            fill: #6c757d;
        }

        .slot-label {
            fill: #fff;
            font-weight: bold;
            pointer-events: none;
            font-size: 14px;
            text-anchor: middle;
            alignment-baseline: middle;
        }

        .legend {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .dot {
            width: 15px;
            height: 15px;
            border-radius: 50%;
        }

        /* Tooltip */
        #tooltip {
            position: absolute;
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
            padding: 10px;
            border-radius: 5px;
            pointer-events: none;
            display: none;
            z-index: 10;
            border: 1px solid #555;
        }
    </style>
</head>

<body>

    <h1>🅿️ Live Parking Overview</h1>

    <div class="map-container">
        <!-- SVG Parking Lot -->
        <svg viewBox="0 0 800 500" id="parkingMap">
            <!-- Asphalt Background -->
            <rect width="800" height="500" fill="#444" rx="15" />

            <!-- Road Markings -->
            <path d="M 50,250 L 750,250" stroke="#eda121" stroke-width="4" stroke-dasharray="15,15" />

            <!-- Parking Rows (A, B, C) -->
            <!-- We will generate these dynamically based on assumed layout, 
                 OR define static placeholders. 
                 Let's define a nice grid assuming DB has slots "1" to "20". -->

            <!-- Row Top (Slots 1-10) -->
            <g transform="translate(60, 50)">
                <text x="-40" y="50" fill="#ccc" font-size="20">Row A</text>
                <!-- Slots will be injected here via JS if not matching, 
                     but for "Realistic Map" we usually draw them manually.
                     Let's draw 10 slots. Class 'slot-group' for easier selecting. -->

                <!-- Slot 1 -->
                <g class="slot-group" data-id="1" transform="translate(0,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-1" />
                    <text x="30" y="55" class="slot-label">1</text>
                </g>
                <g class="slot-group" data-id="2" transform="translate(70,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-2" />
                    <text x="30" y="55" class="slot-label">2</text>
                </g>
                <g class="slot-group" data-id="3" transform="translate(140,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-3" />
                    <text x="30" y="55" class="slot-label">3</text>
                </g>
                <g class="slot-group" data-id="4" transform="translate(210,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-4" />
                    <text x="30" y="55" class="slot-label">4</text>
                </g>
                <g class="slot-group" data-id="5" transform="translate(280,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-5" />
                    <text x="30" y="55" class="slot-label">5</text>
                </g>
                <g class="slot-group" data-id="6" transform="translate(350,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-6" />
                    <text x="30" y="55" class="slot-label">6</text>
                </g>
                <g class="slot-group" data-id="7" transform="translate(420,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-7" />
                    <text x="30" y="55" class="slot-label">7</text>
                </g>
                <g class="slot-group" data-id="8" transform="translate(490,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-8" />
                    <text x="30" y="55" class="slot-label">8</text>
                </g>
                <g class="slot-group" data-id="9" transform="translate(560,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-9" />
                    <text x="30" y="55" class="slot-label">9</text>
                </g>
                <g class="slot-group" data-id="10" transform="translate(630,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-10" />
                    <text x="30" y="55" class="slot-label">10</text>
                </g>
            </g>

            <!-- Row Bottom (Slots 11-20) -->
            <g transform="translate(60, 350)">
                <text x="-40" y="50" fill="#ccc" font-size="20">Row B</text>
                <!-- Slot 11 -->
                <g class="slot-group" data-id="11" transform="translate(0,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-11" />
                    <text x="30" y="55" class="slot-label">11</text>
                </g>
                <g class="slot-group" data-id="12" transform="translate(70,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-12" />
                    <text x="30" y="55" class="slot-label">12</text>
                </g>
                <g class="slot-group" data-id="13" transform="translate(140,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-13" />
                    <text x="30" y="55" class="slot-label">13</text>
                </g>
                <g class="slot-group" data-id="14" transform="translate(210,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-14" />
                    <text x="30" y="55" class="slot-label">14</text>
                </g>
                <g class="slot-group" data-id="15" transform="translate(280,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-15" />
                    <text x="30" y="55" class="slot-label">15</text>
                </g>
                <g class="slot-group" data-id="16" transform="translate(350,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-16" />
                    <text x="30" y="55" class="slot-label">16</text>
                </g>
                <g class="slot-group" data-id="17" transform="translate(420,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-17" />
                    <text x="30" y="55" class="slot-label">17</text>
                </g>
                <g class="slot-group" data-id="18" transform="translate(490,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-18" />
                    <text x="30" y="55" class="slot-label">18</text>
                </g>
                <g class="slot-group" data-id="19" transform="translate(560,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-19" />
                    <text x="30" y="55" class="slot-label">19</text>
                </g>
                <g class="slot-group" data-id="20" transform="translate(630,0)">
                    <rect width="60" height="100" class="slot-shape status-unavailable" id="rect-20" />
                    <text x="30" y="55" class="slot-label">20</text>
                </g>
            </g>

            <!-- Entrance / Exit -->
            <text x="10" y="255" fill="#f0ad4e" font-size="16" font-weight="bold">ENTRANCE</text>
            <text x="730" y="255" fill="#f0ad4e" font-size="16" font-weight="bold">EXIT</text>
        </svg>

        <div id="tooltip"></div>
    </div>

    <div class="legend">
        <div class="legend-item">
            <div class="dot" style="background: #28a745"></div> Available
        </div>
        <div class="legend-item">
            <div class="dot" style="background: #dc3545"></div> Occupied
        </div>
        <div class="legend-item">
            <div class="dot" style="background: #ffc107"></div> Reserved
        </div>
        <div class="legend-item">
            <div class="dot" style="background: #6c757d"></div> Unavailable
        </div>
    </div>

    <script>
        const tooltip = document.getElementById('tooltip');

        // Fetch and Update
        function updateMap() {
            fetch('api.php')
                .then(res => res.json())
                .then(slots => {
                    // Reset all first? No, just update matching.
                    slots.forEach(slot => {
                        // Assuming slot_number maps to our data-id
                        // We need a way to map 'Slot 1' or 'A1' -> data-id="1"
                        // Our SVG has IDs 1-20. 
                        // Let's try to match exactly or sanitize string.
                        // Ideally the DB slot_number is just a number.
                        // If it's "A-01", we might need logic.

                        // Try to find by data-id matching slot_number
                        let element = document.querySelector(`.slot-group[data-id="${slot.slot_number}"]`);

                        // Fallback: If slot_number is 1, and our SVG expects 01? Or vice versa.
                        // Let's just try exact match first.
                        if (!element) return;

                        const rect = element.querySelector('rect');
                        const text = element.querySelector('text');

                        // Update Class
                        rect.setAttribute('class', `slot-shape status-${slot.slot_status}`);

                        // Data for Tooltip
                        element.dataset.type = slot.slot_type;
                        element.dataset.status = slot.slot_status;
                        element.dataset.plate = slot.plate_number || 'N/A';
                    });
                })
                .catch(err => console.error('Map Update Error:', err));
        }

        // Mouse Events for Tooltip
        document.querySelectorAll('.slot-group').forEach(group => {
            group.addEventListener('mouseenter', e => {
                const rect = group.getBoundingClientRect();
                tooltip.style.display = 'block';
                tooltip.style.left = rect.left + 'px';
                tooltip.style.top = (rect.bottom + 5) + 'px';

                const id = group.dataset.id;
                const status = group.dataset.status || 'Unknown';
                const type = group.dataset.type || '-';
                const plate = group.dataset.plate === 'N/A' || !group.dataset.plate ? '' : `<br>🚗 Plate: ${group.dataset.plate}`;

                tooltip.innerHTML = `<strong>Slot ${id}</strong><br>Status: ${status}<br>Type: ${type}${plate}`;
            });
            group.addEventListener('mouseleave', () => {
                tooltip.style.display = 'none';
            });
        });

        // Loop
        updateMap();
        setInterval(updateMap, 3000); // Live refresh every 3s
    </script>
</body>

</html>