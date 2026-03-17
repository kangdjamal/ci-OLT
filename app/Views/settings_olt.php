<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Settings</h1>

    <?php if (session()->getFlashdata('pesan')) : ?>
        <div class="alert alert-<?= session()->getFlashdata('warna') ?> alert-dismissible fade show">
            <?= session()->getFlashdata('pesan') ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Manajemen Database</h6>
        </div>
        <div class="card-body">
            <p>Gunakan tombol di bawah ini untuk membersihkan semua data ONU yang tersimpan di SQLite.</p>

            <form action="<?= base_url('olt/clear_database') ?>" method="post"
                  onsubmit="return confirm('Apa anda yakin ingin mengosongkan database? Data yang dihapus tidak bisa dikembalikan.');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash-alt"></i> Kosongkan Database
                </button>
            </form>
        </div>
    </div>

</div>
<?= $this->endSection() ?>
