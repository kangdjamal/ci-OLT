/**
 * ONU Map Engine - ci-OLT Project
 * Expertly crafted for Diskominfo Surakarta OLT Management
 */

// Global Variables
let map;
let isPickerActive = false;
let markerPelanggan = null;
let networkLayer;

function initOnuMap(config) {
    // 1. Cek apakah container map ada
    const mapContainer = document.getElementById('map');
    if (!mapContainer) {
        console.error("Element #map tidak ditemukan!");
        return;
    }

    // 2. Inisialisasi Peta
    map = L.map('map', {
        center: [config.lat, config.lng],
        zoom: 18,
        zoomControl: false
    });

    // Pindahkan Zoom ke kanan bawah
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    // 3. Base Layer (Google Hybrid)
    const googleHybrid = L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        attribution: '© Google Maps'
    }).addTo(map);

    // 4. Setup Sidebar-v2
    const sidebar = L.control.sidebar({ container: 'sidebar' }).addTo(map);

    // 5. Inisialisasi Marker ONU Utama (Icon Rumah)
    const onuIcon = L.divIcon({
        html: '<i class="fas fa-house-user fa-2x text-primary" style="text-shadow: 2px 2px white;"></i>',
        iconSize: [30, 30],
        className: 'custom-div-icon'
    });

    markerPelanggan = L.marker([config.lat, config.lng], { icon: onuIcon })
    .addTo(map)
    .bindPopup(`
    <div class="text-dark">
    <strong class="text-primary">${config.onu_name}</strong><br>
    <small class="font-weight-bold">SN: ${config.onu_sn}</small><br>
    <span class="badge badge-primary mt-1">Lokasi Unit</span>
    </div>
    `);

    // 6. Load Layer KML Jaringan (Omnivore)
    networkLayer = omnivore.kml(config.kml_url, null, L.geoJson(null, {
        onEachFeature: function (feature, layer) {
            const name = feature.properties.name || "Infrastruktur Tanpa Nama";
            const desc = feature.properties.description || "Tidak ada deskripsi.";

            layer.bindPopup(`
            <div class="text-dark" style="min-width:150px">
            <strong class="text-primary border-bottom d-block mb-1">${name}</strong>
            <small>${desc}</small>
            </div>
            `);

            // Efek hover untuk kabel
            if (layer instanceof L.Polyline) {
                layer.setStyle({ color: '#3498db', weight: 4 });
                layer.on('mouseover', function(e) { e.target.setStyle({ weight: 7, color: '#f1c40f' }); });
                layer.on('mouseout', function(e) { networkLayer.resetStyle(e.target); });
            }
        }
    }))
    .on('ready', function() {
        console.log("KML Jaringan Loaded.");
        fillSidebarLayers(); // Isi daftar ODP di sidebar
    })
    .on('error', function(e) {
        console.error("Gagal load KML:", e);
    })
    .addTo(map);

    // 7. Layer Control
    const baseMaps = { "Google Satelit": googleHybrid };
    const overlayMaps = {
        "Jaringan Kabel/ODP": networkLayer,
        "Posisi ONU": markerPelanggan
    };
    L.control.layers(baseMaps, overlayMaps, { position: 'topright' }).addTo(map);

    // 8. Aktifkan Klik Listener
    setupPickerListener();

    // 9. Auto Open Sidebar & Popup
    setTimeout(() => {
        sidebar.open('home');
        markerPelanggan.openPopup();
    }, 1000);
}

function setupPickerListener() {
    map.on('click', function(e) {
        if (isPickerActive) {
            const { lat, lng } = e.latlng;

            // Pindahkan marker rumah
            if (markerPelanggan) {
                markerPelanggan.setLatLng([lat, lng]);
            }

            // Panggil fungsi di view manage_onu.php
            if (typeof syncCoordsToCard === 'function') {
                syncCoordsToCard(lat, lng);
            }
        }
    });
}

function togglePickerMode(status) {
    isPickerActive = status;
    const mapDiv = document.getElementById('map');
    if (!mapDiv) return;

    if (status) {
        mapDiv.style.cursor = 'crosshair';
    } else {
        mapDiv.style.cursor = '';
    }
}

function fillSidebarLayers() {
    const listContainer = document.querySelector('#layers-list'); // ID disesuaikan dengan view rapi tadi
    if (!listContainer) return;

    listContainer.innerHTML = '<ul class="list-group list-group-flush" id="odp-list"></ul>';
    const ul = document.getElementById('odp-list');

    networkLayer.eachLayer(function(layer) {
        if (layer instanceof L.Marker && layer.feature.properties.name) {
            const name = layer.feature.properties.name;
            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action py-2 small border-0';
            li.style.cursor = 'pointer';
            li.innerHTML = `<i class="fas fa-box text-warning mr-2"></i> ${name}`;

            li.onclick = function() {
                map.flyTo(layer.getLatLng(), 19);
                layer.openPopup();
            };
            ul.appendChild(li);
        }
    });
}
