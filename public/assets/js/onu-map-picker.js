// Variable global untuk menyimpan instance peta dan marker
let map, mainMarker;
let isPickerMode = false;

function initOnuMap(config) {
    // 1. Inisialisasi Peta
    map = L.map('map').setView([config.lat, config.lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // 2. Tambahkan Marker ONU Saat Ini
    mainMarker = L.marker([config.lat, config.lng], {
        draggable: false // Default mati, hanya aktif lewat tombol
    }).addTo(map);

    mainMarker.bindPopup("<b>" + config.onu_name + "</b><br>" + config.onu_sn).openPopup();

    // 3. Event Klik pada Peta
    map.on('click', function(e) {
        if (isPickerMode) {
            const { lat, lng } = e.latlng;

            // Pindahkan posisi marker
            mainMarker.setLatLng([lat, lng]);

            // Panggil fungsi sinkronisasi ke card di HTML
            if (typeof syncCoordsToCard === 'function') {
                syncCoordsToCard(lat, lng);
            }

            // Matikan mode picker setelah titik dipilih (Opsional)
            // togglePickerMode(false);
        }
    });
}

// Fungsi untuk On/Off Mode Pilih Lokasi
function togglePickerMode(active) {
    isPickerMode = active;
    const mapContainer = document.getElementById('map');

    if (active) {
        // Ubah cursor peta jadi icon pin/crosshair
        mapContainer.style.cursor = 'crosshair';
        // Atau gunakan gambar pin custom:
        // mapContainer.style.cursor = 'url("https://cdn0.iconfinder.com/data/icons/small-n-flat/24/678111-map-marker-32.png") 16 32, auto';
    } else {
        mapContainer.style.cursor = '';
    }
}
