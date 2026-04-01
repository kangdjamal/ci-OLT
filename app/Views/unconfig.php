<?= $this->extend('layout/main'); ?>

<?= $this->section('content'); ?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">ONU Unconfigured</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Tunggu Otorisasi</h6>
            <button class="btn btn-sm btn-primary" onclick="location.reload();">
                <i class="fas fa-sync-alt fa-sm"></i> Refresh Data
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>PON Port</th>
                            <th>Serial Number (SN)</th>
                            <th>Model</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($onu_list)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="fas fa-check-circle text-success fa-3x"></i>
                                    </div>
                                    <h5 class="text-gray-500">Tidak ada ONU yang butuh konfigurasi</h5>
                                    <p class="small text-muted">Semua perangkat sudah terdaftar atau tidak ada perangkat baru terdeteksi.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                        <?php foreach ($onu_list as $onu): ?>
                        <tr>
                            <td><?= $onu['port']; ?></td>
                            <td><strong><?= $onu['sn']; ?></strong></td>
                            <td><?= $onu['model']; ?></td>
                            <td><span class="badge badge-warning">Waiting Auth</span></td>
                            <td>
                            <?php
                            // 1. Ganti / jadi _ agar segment URL tidak pecah
                            $port_safe = str_replace('/', '_', $onu['port']);
                            // 2. Jika model unknown, kasih default ZTE-F609 agar Python tidak eror
                            $model_safe = ($onu['model'] == 'unknown' || empty($onu['model'])) ? 'ZTE-F609' : $onu['model'];
                            ?>

                            <a href="<?= base_url("olt/auth_page/$port_safe/{$onu['sn']}/$model_safe") ?>" class="btn btn-primary">
                            Aktivasi
                            </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>
