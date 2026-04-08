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
                <span aria-hidden="true">&times;</span>
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
                            <td class="text-right"><?= date('H:i:s') ?> <span class="small text-muted"><?= date('d/m/y') ?></span></td>
                        </tr>
                    </table>

                    <label class="small font-weight-bold text-dark"><i class="fas fa-broadcast-tower mr-1"></i> Optical Power (Live):</label>
                    <div class="p-2 rounded bg-dark border-left-success shadow-sm">
                        <pre class="m-0 text-success" style="font-size: 0.75rem; line-height: 1.4; font-family: 'Courier New', monospace; white-space: pre-wrap;"><?= $onu->redaman ?></pre>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-map-marker-alt fa-fw"></i> Lokasi Penempatan ONU (Live Map)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="embed-responsive" style="height: 450px;">
                        <iframe src="https://www.google.com/maps/d/embed?mid=1yl6oV82Y660a_37p_2cjhicjhJn-9mk&ehbc=2E312F" width="640" height="480"></iframe>
                    </div>
                    <div class="p-3">
                        <strong>Nama Aset:</strong> <?= $onu->name ?><br>
                        <small class="text-muted italic">*Peta tersinkronisasi otomatis dengan Google My Maps Diskominfo.</small>
                    </div>
                </div>
            </div>
            <div class="card shadow mb-4 border-left-success">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success text-uppercase small">
                        <i class="fas fa-cog mr-1"></i>Configuration & Profile
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
                                    if (!in_array($current_profile, $available_profiles) && $current_profile !== 'N/A') {
                                        array_unshift($available_profiles, $current_profile);
                                    }
                                    ?>
                                    <?php foreach ($available_profiles as $p) : ?>
                                        <option value="<?= $p ?>" <?= ($current_profile == $p) ? 'selected' : '' ?>>
                                            <?= $p ?> <?= ($current_profile == $p) ? '(Aktif)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-2 mb-3">
                                <label class="font-weight-bold small text-muted text-uppercase">VLAN ID</label>
                                <input type="number" name="vlan_id" class="form-control text-center" value="<?= $onu->vlan ?? '0' ?>">
                            </div>

                            <div class="col-md-2 mb-3">
                                <label class="font-weight-bold small text-muted text-uppercase text-center d-block">T-Cont</label>
                                <input type="text" class="form-control bg-light text-center font-weight-bold" value="<?= $onu->tcont ?? '2' ?>" readonly>
                            </div>

                            <div class="col-md-2 mb-3">
                                <label class="font-weight-bold small text-muted text-uppercase text-center d-block">Gemport</label>
                                <input type="text" class="form-control bg-light text-center font-weight-bold" value="<?= $onu->gemport ?? '1' ?>" readonly>
                            </div>
                        </div>

                        <hr>
                        <div class="text-right">
                            <button type="submit" class="btn btn-success shadow-sm px-4 font-weight-bold text-uppercase">
                                <i class="fas fa-save mr-1"></i> Simpan Perubahan
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
                    <p class="small text-dark mb-3">Tindakan ini akan menghapus registrasi ONU di OLT dan menghapus data dari database sistem secara permanen.</p>

                    <?php
                        $val1 = rand(1, 9);
                        $val2 = rand(1, 9);
                        session()->set('captcha_result', $val1 + $val2);
                    ?>

                    <form action="<?= base_url('olt/delete_onu') ?>" method="POST" class="p-3 border rounded bg-light">
                        <?= csrf_field() ?>
                        <input type="hidden" name="onu_index" value="<?= $onu->onu_index ?>">

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-danger text-uppercase">Keamanan: Hitung Hasil Penjumlahan</label>
                            <div class="input-group" style="max-width: 300px;">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-danger text-white font-weight-bold shadow-sm">
                                        <?= $val1 ?> + <?= $val2 ?> =
                                    </span>
                                </div>
                                <input type="number" name="user_captcha" class="form-control shadow-sm" placeholder="Isi Jawaban..." required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-danger btn-sm px-3 font-weight-bold text-uppercase shadow-sm">
                            <i class="fas fa-trash-alt mr-1"></i> VALIDASI & HAPUS ONU
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
<?= $this->endSection() ?>
