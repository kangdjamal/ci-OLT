<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>OLT Manager - Dashboard</title>

<link href="<?= base_url('assets/vendor/fontawesome-free/css/all.min.css') ?>" rel="stylesheet" type="text/css">
<link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

<link href="<?= base_url('assets/css/sb-admin-2.min.css') ?>" rel="stylesheet">

<link href="<?= base_url('assets/vendor/datatables/dataTables.bootstrap4.min.css') ?>" rel="stylesheet">

<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css">

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://api.mapbox.com/mapbox.js/plugins/leaflet-omnivore/v0.3.1/leaflet-omnivore.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
#map { height: 300px; width: 100%; border-radius: 8px; }
</style>

<style>
/* Agar tombol export terlihat rapi di atas tabel */
.dt-buttons {
    margin-bottom: 15px;
}
</style>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-sidebar-v2@3.2.3/css/leaflet-sidebar.min.css" />

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-omnivore/0.3.4/leaflet-omnivore.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet-sidebar-v2@3.2.3/js/leaflet-sidebar.min.js"></script>

</head>

<body id="page-top">

<div id="wrapper">

<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
<a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= base_url('olt/dashboard') ?>">
<div class="sidebar-brand-icon rotate-n-15">
<i class="fas fa-network-wired"></i>
</div>
<div class="sidebar-brand-text mx-3">OLT Admin</div>
</a>

<hr class="sidebar-divider my-0">

<li class="nav-item active">
<a class="nav-link" href="<?= base_url('olt/dashboard') ?>">
<i class="fas fa-fw fa-tachometer-alt"></i>
<span>Overview</span></a>
</li>

<hr class="sidebar-divider">

<div class="sidebar-heading">Interface</div>

<li class="nav-item">
<a class="nav-link" href="<?= base_url('olt/settings') ?>">
<i class="fas fa-fw fa-cog"></i>
<span>Settings</span>
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="<?= base_url('olt/unconfig'); ?>">
<i class="fas fa-fw fa-plug"></i>
<span>ONU Unconfig</span>
</a>
</li>

<hr class="sidebar-divider d-none d-md-block">

<li class="nav-item">
<a class="nav-link" href="<?= base_url('olt/logout') ?>" onclick="return confirm('Yakin ingin keluar?')">
<i class="fas fa-fw fa-sign-out-alt"></i>
<span>Logout</span>
</a>
</li>

<div class="text-center d-none d-md-inline">
<button class="rounded-circle border-0" id="sidebarToggle"></button>
</div>
</ul>
<div id="content-wrapper" class="d-flex flex-column">
<div id="content">
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
<button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
<i class="fa fa-bars"></i>
</button>

<ul class="navbar-nav ml-auto">
<div class="topbar-divider d-none d-sm-block"></div>
<li class="nav-item dropdown no-arrow">
<a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
<span class="mr-2 d-none d-lg-inline text-gray-600 small">IP OLT: <?= session()->get('ip_olt') ?? 'Not Connected' ?></span>
<img class="img-profile rounded-circle" src="https://ui-avatars.com/api/?name=Admin&background=4e73df&color=fff">
</a>
</li>
</ul>
</nav>
<div class="container-fluid">
<?= $this->renderSection('content') ?>
</div>
</div>

<footer class="sticky-footer bg-white">
<div class="container my-auto">
<div class="copyright text-center my-auto">
<span>Copyright &copy; Diskominfo Sukoharjo - 2026</span>
</div>
</div>
</footer>
</div>
</div>

<a class="scroll-to-top rounded" href="#page-top">
<i class="fas fa-angle-up"></i>
</a>

<script src="<?= base_url('assets/vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>

<script src="<?= base_url('assets/vendor/jquery-easing/jquery.easing.min.js') ?>"></script>

<script src="<?= base_url('assets/js/sb-admin-2.min.js') ?>"></script>

<script src="<?= base_url('assets/vendor/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/dataTables.bootstrap4.min.js') ?>"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<?= $this->renderSection('script') ?>

</body>
</html>
