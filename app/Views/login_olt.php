<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Login OLT Manager</title>
    <link href="<?= base_url('assets/css/sb-admin-2.min.css') ?>" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card o-hidden border-0 shadow-lg my-5">

                    <div class="card-body p-5">
                    <div class="text-center"><h1 class="h4 text-gray-900 mb-4">OLT Access</h1></div>

                    <?php if (session()->getFlashdata('error')) : ?>
                    <div class="alert alert-danger small"><?= session()->getFlashdata('error') ?></div>
                    <?php endif; ?>

                    <form action="<?= base_url('olt/auth') ?>" method="post">
                    <div class="form-group">
                    <input type="text" name="ip_olt" class="form-control" placeholder="IP Address OLT" required>
                    </div>
                    <div class="form-group">
                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                    </div>
                    <div class="form-group">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Masuk ke OLT</button>
                    </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</body>
</html>
