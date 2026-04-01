<?php echo $this->extend('layout/main'); ?>

<?php echo $this->section('content'); ?>
<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Aktivasi ONU Baru</h1>

    <div class="row">
        <div class="col-lg-7">
            <div class="card shadow mb-4 border-left-success">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Form Konfirmasi Registrasi</h6>
                </div>
                <div class="card-body">
                    <form action="<?= base_url('olt/activate_process'); ?>" method="POST">
                        <?= csrf_field(); ?>

                        <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold text-dark">Gpon Port / Interface</label>
                                        <input type="text" name="port" class="form-control" value="<?= $port; ?>" required>
                                        <small class="form-text text-danger font-weight-bold">
                                            Format OLT: 1/2/6:1 (Gunakan / dan :)
                                        </small>
                                        <input type="hidden" name="onu_type" value="<?= $onu_type; ?>">
                                    </div>
                                </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Serial Number (SN)</label>
                                    <input type="text" name="sn" class="form-control bg-light" value="<?= $sn; ?>" readonly style="font-weight: bold; color: #4e73df;">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Nama Pelanggan / Deskripsi</label>
                            <input type="text" name="customer_name" class="form-control" placeholder="Contoh: Klien_KMF_01" required>
                            <small class="form-text text-muted">Gunakan underscore (_) tanpa spasi untuk deskripsi OLT.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">VLAN Internet</label>
                                    <input type="number" name="vlan" class="form-control" value="1189" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Bandwidth Profile</label>
                                    <select name="profile" class="form-control" required>
                                        <option value="10M">10 Mbps</option>
                                        <option value="20M" selected>20 Mbps</option>
                                        <option value="50M">50 Mbps</option>
                                        <option value="100M">100 Mbps</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="tcont" value="2">
                        <input type="hidden" name="gemport" value="2">

                        <div class="alert alert-warning border-0 small shadow-sm mt-3">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <strong>Note:</strong> T-CONT dan Gemport otomatis disetel ke profil <strong>2</strong>.
                        </div>

                        <hr>
                        <button type="submit" class="btn btn-success btn-block shadow-sm btn-lg">
                            <i class="fas fa-check-circle mr-2"></i> KONFIRMASI & AKTIVASI
                        </button>
                        <a href="<?= base_url('olt/unconfig'); ?>" class="btn btn-link btn-block text-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-4 shadow-sm border-left-primary">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Deteksi ONU Terdekat</h6>
                    <form action="" method="post">
                        <button type="submit" name="action" value="show_neighbors" class="btn btn-primary btn-sm shadow-sm">
                            <i class="fas fa-sync-alt mr-1"></i> Tampilkan Real-time
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($show_neighbors): ?>
            <div class="card shadow-sm bg-dark">
                <div class="card-body p-0">
                    <pre class="text-success p-3 mb-0" style="font-size: 0.75rem; min-height: 250px; overflow-x: auto; background: #1a1a1a;">
<?= esc($neighbors) ?>
                    </pre>
                </div>
                <div class="card-footer bg-dark border-top border-secondary py-2">
                    <code class="text-info small"># show gpon onu state</code>
                </div>
            </div>
            <?php else: ?>
                <div class="text-center py-5 bg-white rounded shadow-sm border">
                    <i class="fas fa-terminal fa-3x text-gray-200 mb-3"></i>
                    <p class="text-muted small">Data tetangga akan muncul di sini<br>setelah tombol di atas diklik.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php echo $this->endSection(); ?>
