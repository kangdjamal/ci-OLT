<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">ONU Monitoring</h1>
    <div class="d-none d-sm-inline-block">
        <form action="<?= base_url('olt/sync') ?>" method="post">
            <?= csrf_field() ?>
            <button type="submit" id="btnSync" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-sync-alt fa-sm text-white-50"></i> Sync from OLT
            </button>
        </form>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Daftar ONU (GPON)</h6>

    </div>
    <div class="card-body">
        <?php if (session()->getFlashdata('pesan')) : ?>
            <div class="alert alert-<?= session()->getFlashdata('warna') ?> alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('pesan') ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr class="bg-primary text-white">
                        <th>No</th>
                        <th>Nama</th>
                        <th>ID GPON</th>
                        <th>Status</th>
                        <th>Tipe</th>
                        <th>Redaman</th>
                        <th>Admin State</th>
                        <th>Phase State</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($onu_list)): ?>
                        <?php $no = 1; foreach ($onu_list as $onu): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="font-weight-bold text-dark"><?= esc($onu->name) ?></td>
                                <td><code><?= esc($onu->onu_index) ?></code></td>
                                <td>
                                    <span class="badge badge-info"><?= esc($onu->status) ?: '-' ?></span>
                                </td>
                                <td><?= esc($onu->type) ?></td>
                                <td style="background-color: #f8f9fc;">
                                    <?php if (!empty($onu->redaman) && $onu->redaman != 'N/A'): ?>
                                        <pre style="margin-bottom: 0; white-space: pre; color: #2e59d9;"><?= esc($onu->redaman) ?></pre>
                                    <?php else: ?>
                                        <small class="text-muted">No CLI Data</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($onu->admin_state) ?></td>
                                <td>
                                    <?php if (strtolower($onu->phase_state) == 'working'): ?>
                                        <span class="badge badge-success px-3">Working</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><?= esc($onu->phase_state) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                <?php $safe_id = str_replace(['/', ':'], ['-', '_'], $onu->onu_index); ?>

                                <div class="d-flex justify-content-center" style="gap: 8px;">
                                <form action="<?= base_url('olt/update_card/' . $safe_id) ?>" method="post" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-primary btn-sm btn-circle shadow-sm" title="Refresh Detail">
                                <i class="fas fa-sync-alt"></i>
                                </button>
                                </form>

                                <a href="<?= base_url('olt/manage/' . $safe_id); ?>"
                                class="btn btn-info btn-sm btn-circle shadow-sm"
                                title="Manage ONU">
                                <i class="fas fa-tools"></i>
                                </a>
                                </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<?= $this->endSection() ?>
