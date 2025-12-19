<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realistic Parking Map (Design v2)</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            background: #1a1a1a;
            color: #fff;
            font-family: 'Inter', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        h1 {
            margin-bottom: 20px;
            font-weight: 800;
            background: linear-gradient(135deg, #FF6B6B, #4ECDC4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .map-container {
            width: 95%;
            max-width: 1100px;
            background: #2b2b2b;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
            position: relative;
            border: 1px solid #444;
        }

        svg {
            width: 100%;
            height: auto;
            display: block;
            background: #333;
            /* Fallback */
            border-radius: 12px;
            overflow: hidden;
        }

        /* Tooltip */
        #tooltip {
            position: absolute;
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            padding: 12px 18px;
            border-radius: 8px;
            pointer-events: none;
            display: none;
            z-index: 100;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            font-size: 14px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -100%);
            /* Center above cursor */
            margin-top: -15px;
        }

        #tooltip strong {
            display: block;
            margin-bottom: 4px;
            font-size: 16px;
            color: #111;
        }

        #tooltip .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: white;
            margin-bottom: 5px;
        }

        /* Status Colors for JS injection */
        .color-available {
            background: #28a745;
        }

        .color-occupied {
            background: #dc3545;
        }

        .color-reserved {
            background: #ffc107;
            color: black !important;
        }

        .color-unavailable {
            background: #6c757d;
        }

        .legend {
            display: flex;
            gap: 20px;
            margin-top: 25px;
            font-size: 14px;
            font-weight: 500;
            color: #ccc;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            box-shadow: 0 0 5px currentColor;
        }
    </style>
</head>

<body>

    <h1>🅿️ Parking Lot View</h1>

    <!-- Floor Selector -->
    <div class="floor-controls">
        <button class="floor-btn active" onclick="switchFloor(1)">Level 1</button>
        <button class="floor-btn" onclick="switchFloor(2)">Level 2</button>
    </div>

    <style>
        .floor-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .floor-btn {
            background: #444;
            color: #aaa;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 1px;
        }

        .floor-btn.active {
            background: #4ECDC4;
            color: #1a1a1a;
            box-shadow: 0 0 15px rgba(78, 205, 196, 0.4);
        }

        .floor-btn:hover:not(.active) {
            background: #555;
            color: #fff;
        }
    </style>

    <div class="map-container">
        <!-- SVG Canvas -->
        <svg viewBox="0 0 900 600" id="parkingMap">
            <defs>
                <!-- Asphalt Pattern -->
                <pattern id="asphalt" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse">
                    <rect width="100" height="100" fill="#3a3a3a" />
                    <!-- Simple noise simulation with dots -->
                    <circle cx="20" cy="20" r="1.5" fill="#444" opacity="0.5" />
                    <circle cx="70" cy="60" r="1.5" fill="#444" opacity="0.5" />
                    <circle cx="40" cy="80" r="1.5" fill="#222" opacity="0.3" />
                    <circle cx="80" cy="10" r="1.5" fill="#222" opacity="0.3" />
                </pattern>

                <!-- Top-Down Car Shape (Standard) -->
                <g id="car-top" transform="scale(0.8)">
                    <!-- Shadow -->
                    <rect x="-32" y="-55" width="64" height="110" rx="10" fill="#000" opacity="0.4"
                        filter="url(#blur)" />
                    <!-- Body -->
                    <path d="M -28,-50 Q -30,-20 -30,0 Q -30,20 -28,50 L 28,50 Q 30,20 30,0 Q 30,-20 28,-50 Z"
                        fill="currentColor" stroke="#fff" stroke-width="1" />
                    <!-- Windshields -->
                    <path d="M -25,-35 L 25,-35 L 22,-20 L -22,-20 Z" fill="#333" />
                    <path d="M -25,35 L 25,35 L 22,20 L -22,20 Z" fill="#333" />
                    <!-- Roof -->
                    <rect x="-26" y="-20" width="52" height="40" rx="5" fill="currentColor" filter="brightness(1.2)" />
                    <rect x="-26" y="-20" width="52" height="40" rx="5" fill="#fff" opacity="0.1" />
                </g>

                <!-- Top-Down Moto Shape -->
                <g id="moto-top" transform="scale(0.7)">
                    <rect x="-10" y="-30" width="20" height="60" rx="5" fill="currentColor" stroke="#fff"
                        stroke-width="1" />
                    <circle cx="0" cy="-20" r="6" fill="#333" /> <!-- Handlebars -->
                    <rect x="-15" y="-22" width="30" height="4" fill="#333" />
                </g>

                <filter id="blur">
                    <feGaussianBlur in="SourceGraphic" stdDeviation="3" />
                </filter>

                <filter id="glow" x="-50%" y="-50%" width="200%" height="200%">
                    <feGaussianBlur stdDeviation="2.5" result="coloredBlur" />
                    <feMerge>
                        <feMergeNode in="coloredBlur" />
                        <feMergeNode in="SourceGraphic" />
                    </feMerge>
                </filter>
            </defs>

            <!-- Floor -->
            <rect width="900" height="600" fill="url(#asphalt)" />

            <!-- Road Markings -->
            <!-- Central Divider -->
            <path d="M 50,300 L 850,300" stroke="#eda121" stroke-width="4" stroke-dasharray="20,20" />
            <!-- Arrows -->
            <path d="M 100,280 L 140,280 L 130,270 M 140,280 L 130,290" stroke="#rgba(255,255,255,0.5)" stroke-width="3"
                fill="none" />
            <path d="M 800,320 L 760,320 L 770,330 M 760,320 L 770,310" stroke="#rgba(255,255,255,0.5)" stroke-width="3"
                fill="none" />

            <!-- JS populates slots here -->
            <g id="slots-layer"></g>

            <!-- Decorative Elements -->
            <text x="50" y="550" fill="#666" font-size="24" font-weight="900" opacity="0.5"
                transform="rotate(-90 50,550)">SECTION A</text>
            <text x="850" y="50" fill="#666" font-size="24" font-weight="900" opacity="0.5"
                transform="rotate(90 850,50)">SECTION B</text>

        </svg>

        <div id="tooltip"></div>
    </div>

    <!-- Live Legend -->
    <div class="legend">
        <div class="legend-item">
            <div class="legend-dot" style="background:#28a745; box-shadow:0 0 8px #28a745;"></div> Available
        </div>
        <div class="legend-item">
            <div class="legend-dot" style="background:#dc3545; box-shadow:0 0 8px #dc3545;"></div> Occupied (Car)
        </div>
        <div class="legend-item">
            <div class="legend-dot" style="background:#ffc107; box-shadow:0 0 8px #ffc107;"></div> Reserved
        </div>
        <div class="legend-item">
            <div class="legend-dot" style="background:#6c757d;"></div> Unavailable
        </div>
    </div>

    <script>
        const tooltip = document.getElementById('tooltip');
        const slotsLayer = document.getElementById('slots-layer');
        let currentFloor = 1;

        // Configuration for Drawing Slots
        // Floor 1: Slots 1-10
        // Floor 2: Slots 11-20
        // Both floors use the same X/Y layout for simplicity (Simulating stacked floors)
        const SLOTS_CONFIG = [
            // --- Level 1 ---
            // Slots 1-5 (Top Row)
            ...Array.from({ length: 5 }, (_, i) => ({ id: i + 1, floor: 1, x: 150 + (i * 120), y: 50, rotation: 180 })),
            // Slots 6-10 (Bottom Row)
            ...Array.from({ length: 5 }, (_, i) => ({ id: i + 6, floor: 1, x: 150 + (i * 120), y: 400, rotation: 0 })),

            // --- Level 2 ---
            // Slots 11-15 (Top Row)
            ...Array.from({ length: 5 }, (_, i) => ({ id: i + 11, floor: 2, x: 150 + (i * 120), y: 50, rotation: 180 })),
            // Slots 16-20 (Bottom Row)
            ...Array.from({ length: 5 }, (_, i) => ({ id: i + 16, floor: 2, x: 150 + (i * 120), y: 400, rotation: 0 })),
        ];

        function switchFloor(floor) {
            currentFloor = floor;
            // Update UI Buttons
            document.querySelectorAll('.floor-btn').forEach((btn, idx) => {
                btn.classList.toggle('active', (idx + 1) === floor);
            });
            drawGrid(); // Re-draw slots for this floor
            updateMap(); // Update status immediately
        }

        // Draw initial grid
        function drawGrid() {
            slotsLayer.innerHTML = ''; // clear

            // Filter configs by current floor
            const floorSlots = SLOTS_CONFIG.filter(cfg => cfg.floor === currentFloor);

            floorSlots.forEach(cfg => {
                const g = document.createElementNS("http://www.w3.org/2000/svg", "g");
                g.setAttribute("class", "slot-wrapper");
                g.setAttribute("transform", `translate(${cfg.x}, ${cfg.y})`);
                g.dataset.id = cfg.id;

                // Parking Line Box (White borders)
                const box = document.createElementNS("http://www.w3.org/2000/svg", "rect");
                box.setAttribute("x", -30);
                box.setAttribute("y", 0);
                box.setAttribute("width", 60);
                box.setAttribute("height", 110);
                box.setAttribute("fill", "none");
                box.setAttribute("stroke", "#rgba(255,255,255,0.3)");
                box.setAttribute("stroke-width", "3");

                // Number
                const num = document.createElementNS("http://www.w3.org/2000/svg", "text");
                num.textContent = cfg.id;
                num.setAttribute("x", 0);
                num.setAttribute("y", cfg.rotation === 180 ? 20 : 130);
                num.setAttribute("fill", "#666");
                num.setAttribute("font-size", "16");
                num.setAttribute("font-weight", "900");
                num.setAttribute("text-anchor", "middle");

                // Status Indicator Light (LED strip at the back)
                const light = document.createElementNS("http://www.w3.org/2000/svg", "rect");
                light.setAttribute("class", "status-indicator");
                light.setAttribute("x", -25);
                light.setAttribute("y", 5);
                light.setAttribute("width", 50);
                light.setAttribute("height", 4);
                light.setAttribute("rx", 2);
                light.setAttribute("fill", "#28a745"); // default green
                light.setAttribute("filter", "url(#glow)");

                // Car Group (Hidden by default)
                const carG = document.createElementNS("http://www.w3.org/2000/svg", "g");
                carG.setAttribute("class", "vehicle-visual");
                carG.setAttribute("transform", "translate(0, 60)"); // center in slot

                // Use the defs based on type, dynamically set later. 
                const useCar = document.createElementNS("http://www.w3.org/2000/svg", "use");
                useCar.setAttribute("href", "#car-top");
                useCar.setAttribute("class", "car-shape");
                useCar.setAttribute("display", "none");
                useCar.setAttribute("fill", "#dc3545");

                carG.appendChild(useCar);

                g.appendChild(box);
                g.appendChild(num);
                g.appendChild(light);
                g.appendChild(carG);

                // Add Events
                g.addEventListener('mouseenter', handleHover);
                g.addEventListener('mouseleave', () => tooltip.style.display = 'none');

                slotsLayer.appendChild(g);
            });
        }

        // Live Update
        function updateMap() {
            fetch('api.php')
                .then(res => res.json())
                .then(slots => {
                    slots.forEach(slot => {
                        // Find the group
                        const g = slotsLayer.querySelector(`.slot-wrapper[data-id="${slot.slot_number}"]`);
                        if (!g) return; // Slot not on this floor

                        // 1. Update Light Color
                        const light = g.querySelector('.status-indicator');
                        let color = '#28a745'; // Green
                        if (slot.slot_status === 'occupied') color = '#dc3545';
                        else if (slot.slot_status === 'reserved') color = '#ffc107';
                        else if (slot.slot_status === 'unavailable') color = '#6c757d';

                        light.setAttribute('fill', color);

                        // 2. Show/Hide Vehicle
                        const carUse = g.querySelector('.car-shape');
                        if (slot.slot_status === 'occupied') {
                            carUse.setAttribute('display', 'block');
                            // Determine vehicle type visual
                            if (slot.slot_type === 'two_wheeler') {
                                carUse.setAttribute('href', '#moto-top');
                                carUse.setAttribute('fill', '#4db8ff'); // distinct color for moto
                            } else {
                                carUse.setAttribute('href', '#car-top');
                                carUse.setAttribute('fill', '#dc3545');
                            }
                        } else {
                            carUse.setAttribute('display', 'none');
                        }

                        // Store Data for Tooltip
                        g.dataset.status = slot.slot_status;
                        g.dataset.type = slot.slot_type;
                        g.dataset.plate = slot.plate_number;
                    });
                });
        }

        function handleHover(e) {
            const g = e.currentTarget;
            const rect = g.getBoundingClientRect();
            const status = g.dataset.status || 'available';
            const type = g.dataset.type || 'standard';
            const plate = g.dataset.plate;

            let statusClass = 'color-' + status;

            tooltip.style.display = 'block';
            tooltip.style.left = (rect.left + rect.width / 2) + 'px';
            tooltip.style.top = rect.top + 'px';

            let content = `<strong>Slot ${g.dataset.id}</strong>`;
            content += `<span class="status-badge ${statusClass}">${status}</span>`;
            if (status === 'occupied' && plate) {
                content += `<div style="margin-top:6px; font-family:monospace; background:#eee; color:#333; padding:2px 4px; border-radius:3px; text-align:center;">${plate}</div>`;
            }
            content += `<div style="margin-top:4px; font-size:11px; color:#666; text-transform:uppercase;">${type}</div>`;

            tooltip.innerHTML = content;
        }

        // Initialize
        drawGrid(); // Render SVG structure
        updateMap(); // Fetch data
        setInterval(updateMap, 3000); // Loop
    </script>
</body>

</html>