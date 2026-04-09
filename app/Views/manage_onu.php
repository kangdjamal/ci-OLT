<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Management ONU: <span class="text-primary"><?= $onu->onu_index ?></span></h1>
        <a href="<?= base_url('olt/dashboard') ?>" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
        </a>
    </div>

    <?php if (session()->getFlashdata('pesan')) : ?>
        <div class="alert alert-<?= session()->getFlashdata('warna') ?> alert-dismissible fade show shadow" role="alert">
            <i class="fas fa-info-circle mr-2"></i> <?= session()->getFlashdata('pesan') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-xl-4 col-lg-5">

            <div class="card shadow mb-4 border-left-primary text-dark">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary text-uppercase small">Identitas & Signal</h6>
                    <span class="badge badge-<?= (strtolower($onu->status) == 'working' || strtolower($onu->status) == 'ready') ? 'success' : 'danger' ?>">
                        <?= strtoupper($onu->status ?? 'UNKNOWN') ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-microchip fa-3x text-gray-300 mb-2"></i>
                        <h5 class="mb-0 font-weight-bold text-dark"><?= $onu->type ?? 'ZTE-F609' ?></h5>
                        <small class="text-muted font-weight-bold">SN: <?= $onu->sn ?? '-' ?></small>
                    </div>

                    <table class="table table-sm table-borderless small mb-3 text-dark">
                        <tr>
                            <td class="text-muted text-uppercase">Nama / Alias</td>
                            <td class="text-right font-weight-bold"><?= $onu->name ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted text-uppercase">Interface</td>
                            <td class="text-right font-weight-bold text-primary"><?= $onu->onu_index ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted text-uppercase">Last Sync</td>
                            <td class="text-right font-weight-bold small"><?= date('H:i:s') ?> <span class="text-muted"><?= date('d/m/y') ?></span></td>
                        </tr>
                    </table>

                    <label class="small font-weight-bold text-dark"><i class="fas fa-broadcast-tower mr-1"></i> Optical Power (Live):</label>
                    <div class="p-2 rounded bg-dark border-left-success shadow-sm">
                        <pre class="m-0 text-success" style="font-size: 0.75rem; line-height: 1.4; font-family: 'Courier New', monospace; white-space: pre-wrap;"><?= $onu->redaman ?></pre>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4 border-left-info text-dark">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-info text-uppercase small">
                        <i class="fas fa-globe-asia mr-1"></i> GIS Koordinat ONU
                    </h6>
                    <i class="fas fa-satellite-dish text-gray-300"></i>
                </div>
                <div class="card-body">

<form id="form-gis" action="<?= base_url('olt/save_gis') ?>" method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="onu_index" value="<?= $onu->onu_index ?>">

    <div class="row mb-3">
        <div class="col-6">
            <label class="small font-weight-bold text-muted text-uppercase">Latitude</label>
            <input type="text" id="lat" name="latitude" class="form-control form-control-sm bg-light font-weight-bold text-dark" value="<?= $onu->latitude ?? '-7.6652' ?>" readonly>
        </div>
        <div class="col-6">
            <label class="small font-weight-bold text-muted text-uppercase">Longitude</label>
            <input type="text" id="lng" name="longitude" class="form-control form-control-sm bg-light font-weight-bold text-dark" value="<?= $onu->longitude ?? '110.8345' ?>" readonly>
        </div>
    </div>

    <div class="row no-gutters">
        <div class="col-6 pr-1">
            <button type="button" id="btn-edit-map" class="btn btn-info btn-sm btn-block shadow-sm font-weight-bold">
                <i class="fas fa-map-marker-alt mr-1"></i> UBAH LETAK
            </button>
        </div>
        <div class="col-6 pl-1">
            <button type="submit" id="btn-save-gis" class="btn btn-success btn-sm btn-block shadow-sm font-weight-bold" disabled>
                <i class="fas fa-save mr-1"></i> SIMPAN
            </button>
        </div>
    </div>
</form>

                    <div id="gis-instruction" class="alert alert-warning mt-3 mb-0 p-2 d-none shadow-sm" style="font-size: 0.75rem;">
                        <i class="fas fa-info-circle mr-1"></i> <strong>Mode Edit Aktif:</strong> Klik pada peta di kanan untuk memindahkan posisi.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-map-marker-alt fa-fw"></i> Lokasi Penempatan ONU (Live Map)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div id="map-wrapper" style="position: relative; height: 500px; overflow: hidden;">
                        <div id="sidebar" class="leaflet-sidebar collapsed">
                            <div class="leaflet-sidebar-tabs">
                                <ul role="tablist">
                                    <li><a href="#home" role="tab"><i class="fas fa-info-circle"></i></a></li>
                                    <li><a href="#layers" role="tab"><i class="fas fa-layer-group"></i></a></li>
                                </ul>
                            </div>
                            <div class="leaflet-sidebar-content">
                                <div class="leaflet-sidebar-pane" id="home">
                                    <h1 class="leaflet-sidebar-header">Detail Lokasi <span class="leaflet-sidebar-close"><i class="fa fa-caret-left"></i></span></h1>
                                    <div class="mt-3 text-dark">
                                        <table class="table table-sm small">
                                            <tr><th>Nama</th><td>: <?= $onu->name ?></td></tr>
                                            <tr><th>SN</th><td>: <?= $onu->sn ?></td></tr>
                                        </table>
                                    </div>
                                </div>
                                <div class="leaflet-sidebar-pane" id="layers">
                                    <h1 class="leaflet-sidebar-header">Layer Jaringan <span class="leaflet-sidebar-close"><i class="fa fa-caret-left"></i></span></h1>
                                    <div id="layers-list" class="mt-3 text-dark small"></div>
                                </div>
                            </div>
                        </div>
                        <div id="map" style="height: 100%; width: 100%;"></div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4 border-left-success">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success text-uppercase small">
                        <i class="fas fa-cog mr-1"></i> Configuration & Profile
                    </h6>
                </div>
                <div class="card-body text-dark">
                    <form action="<?= base_url('olt/update_config') ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="onu_index" value="<?= $onu->onu_index ?>">

                        <div class="form-group mb-4">
                            <label class="font-weight-bold small text-muted text-uppercase">Deskripsi Pelanggan (Name)</label>
                            <input type="text" name="new_name" class="form-control font-weight-bold border-left-primary" value="<?= $onu->name ?>" placeholder="Contoh: KMF_NOC">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold small text-muted text-uppercase">Bandwidth Profile</label>
                                <select name="tcont_profile" class="form-control border-left-info shadow-sm">
                                    <?php
                                    $available_profiles = ['5M', '10M', '20M', '30M', '50M', '100M', 'FULL-SPEED'];
                                    $current_profile = $onu->profile ?? 'N/A';
                                    ?>
                                    <?php foreach ($available_profiles as $p) : ?>
                                        <option value="<?= $p ?>" <?= ($current_profile == $p) ? 'selected' : '' ?>>
                                            <?= $p ?> <?= ($current_profile == $p) ? '(Aktif)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3 text-center">
                                <label class="font-weight-bold small text-muted text-uppercase">VLAN</label>
                                <input type="number" name="vlan_id" class="form-control text-center" value="<?= $onu->vlan ?? '0' ?>">
                            </div>
                            <div class="col-md-2 mb-3 text-center">
                                <label class="font-weight-bold small text-muted text-uppercase">T-Cont</label>
                                <input type="text" class="form-control bg-light text-center font-weight-bold" value="<?= $onu->tcont ?? '2' ?>" readonly>
                            </div>
                            <div class="col-md-2 mb-3 text-center">
                                <label class="font-weight-bold small text-muted text-uppercase">Gemport</label>
                                <input type="text" class="form-control bg-light text-center font-weight-bold" value="<?= $onu->gemport ?? '1' ?>" readonly>
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="btn btn-success shadow-sm px-4 font-weight-bold text-uppercase">
                                <i class="fas fa-save mr-1"></i> Simpan Konfigurasi
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow mb-4 border-left-danger">
                <div class="card-header py-3 bg-gray-100">
                    <h6 class="m-0 font-weight-bold text-danger text-uppercase small">Danger Zone</h6>
                </div>
                <div class="card-body">
                    <p class="small text-dark mb-3">Menghapus registrasi ONU di OLT dan database sistem.</p>
                    <form action="<?= base_url('olt/delete_onu') ?>" method="POST" class="p-3 border rounded bg-light">
                        <?= csrf_field() ?>
                        <input type="hidden" name="onu_index" value="<?= $onu->onu_index ?>">
                        <?php
                            $v1 = rand(1,9); $v2 = rand(1,9);
                            session()->set('captcha_result', $v1 + $v2);
                        ?>
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-danger">Keamanan: <?= $v1 ?> + <?= $v2 ?> =</label>
                            <input type="number" name="user_captcha" class="form-control col-md-3 shadow-sm" required>
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm font-weight-bold">
                            <i class="fas fa-trash-alt mr-1"></i> VALIDASI & HAPUS
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/onu-map-engine.js') ?>"></script>

<script>
    // Gunakan Vanilla JS untuk membungkus jQuery agar aman dari 'ReferenceError'
    document.addEventListener("DOMContentLoaded", function() {

        // Pastikan jQuery ($) sudah siap sebelum dipakai
        if (typeof $ !== 'undefined') {

            // 1. Inisialisasi Data dari PHP
            const config = {
                lat: <?= $onu->latitude ?? -7.6652 ?>,
                lng: <?= $onu->longitude ?? 110.8345 ?>,
                onu_name: "<?= addslashes($onu->name) ?>",
                onu_sn: "<?= $onu->sn ?>",
                kml_url: "<?= base_url('maps/jaringan.kml') ?>"
            };

            // 2. Jalankan Engine Leaflet
            if (typeof initOnuMap === 'function') {
                initOnuMap(config);
            }

            // 3. Listener Tombol Ubah Letak (Pakai jQuery)
            $('#btn-edit-map').on('click', function() {
                const btn = $(this);
                const isEditing = btn.hasClass('btn-warning');

                if (!isEditing) {
                    btn.removeClass('btn-info').addClass('btn-warning')
                       .html('<i class="fas fa-times mr-1"></i> BATAL');
                    $('#gis-instruction').removeClass('d-none');
                    if (typeof togglePickerMode === 'function') togglePickerMode(true);
                } else {
                    btn.removeClass('btn-warning').addClass('btn-info')
                       .html('<i class="fas fa-map-marker-alt mr-1"></i> UBAH LETAK');
                    $('#gis-instruction').addClass('d-none');
                    $('#btn-save-gis').prop('disabled', true);
                    if (typeof togglePickerMode === 'function') togglePickerMode(false);
                }
            });

        } else {
            console.error("Wah Mal, jQuery belum ke-load! Cek urutan script di layout/main.");
        }
    });

    // Fungsi global untuk diakses oleh engine JS
    function syncCoordsToCard(lat, lng) {
        document.getElementById('lat').value = lat.toFixed(8);
        document.getElementById('lng').value = lng.toFixed(8);
        document.getElementById('btn-save-gis').disabled = false;
    }
</script>


<?= $this->endSection() ?>
