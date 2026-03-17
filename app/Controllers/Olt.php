<?php

namespace App\Controllers;

use App\Models\OnuModel;

class Olt extends BaseController
{
    public function __construct()
    {

        // List fungsi yang BOLEH diakses tanpa login (index dan auth)
        $allowed = ['index', 'auth'];

        // Ambil nama fungsi yang sedang diakses
        $router = service('router');
        $method = $router->methodName();

        if (!in_array($method, $allowed)) {
            if (!session()->get('logged_in')) {
                // Jika belum login, tendang ke halaman login
                header('Location: ' . base_url('olt'));
                exit;
            }
        }
    }

    public function index()
    {
        return view('login_olt');
    }

    public function auth()
    {
        $ip   = $this->request->getPost('ip_olt');
        $user = $this->request->getPost('username');
        $pass = $this->request->getPost('password');

        $python = env('PYTHON_VENV');
        $script = env('SCRIPT_PATH')."cek_tembus.py";

        // Eksekusi untuk cek apakah kredensial bisa tembus ke OLT
        $ip_s   = escapeshellarg($ip);
        $user_s = escapeshellarg($user);
        $pass_s = escapeshellarg($pass);

        $command = "$python $script $ip_s $user_s $pass_s 2>&1";
        $output  = shell_exec($command);

        if (trim($output) === "SUCCESS") {
            // Simpan ke session untuk digunakan function sync() nanti
            session()->set([
                'ip_olt'   => $ip,
                'username' => $user,
                'password' => $pass,
                'logged_in' => true
            ]);
            return redirect()->to(base_url('olt/dashboard'));
        } else {
            session()->setFlashdata('error', 'Gagal Koneksi OLT: ' . $output);
            return redirect()->to(base_url('olt'));
        }
    }

    public function dashboard()
    {
        $model = new OnuModel();

        // 1. Ambil data dari post
        $raw_ip   = $this->request->getPost('ip_olt');
        $raw_user = $this->request->getPost('username');
        $raw_pass = $this->request->getPost('password');

        // 2. Amankan dengan escapeshellarg SEBELUM disimpan ke session
        if ($raw_ip) {
            session()->set('ip_olt', escapeshellarg($raw_ip));
        }
        if ($raw_user) {
            session()->set('username', escapeshellarg($raw_user));
        }
        if ($raw_pass) {
            session()->set('password', escapeshellarg($raw_pass));
        }

        // BAGIAN ERROR DIHAPUS (Sudah digantikan oleh logika di atas)

        $onuData = $model->findAll();

        // 3. Kirim ke View (Gunakan trim agar tampilan di web bersih tanpa tanda kutip)
        $data = [
            'ip_olt'   => trim(session()->get('ip_olt') ?? '', "'"),
            'username' => trim(session()->get('username') ?? '', "'"),
            'onu_list' => $onuData
        ];

        return view('dashboard_olt', $data);
    }

    public function sync()
    {
        $python = env('PYTHON_VENV');
        $script = env('SCRIPT_PATH')."sync_olt.py";

        //$pythonVenv = "/home/jamal/Prakom/ci-OLT/venv/bin/python3";
        //$scriptPath = "/home/jamal/Prakom/ci-OLT/scripts/sync_olt.py";

        // Ambil kredensial dari session
        $ip   = escapeshellarg(session()->get('ip_olt'));
        $user = escapeshellarg(session()->get('username'));
        $pass = escapeshellarg(session()->get('password'));

        // Gabungkan kredensial ke dalam command
        $command = "$python $script $ip $user $pass 2>&1";
        $output = shell_exec($command);

        if (trim($output) === "SUCCESS") {
            session()->setFlashdata('pesan', 'Sinkronisasi OLT Berhasil!');
            session()->setFlashdata('warna', 'success');
        } else {
            session()->setFlashdata('pesan', 'Gagal: ' . $output);
            session()->setFlashdata('warna', 'danger');
        }

        return redirect()->to(base_url('olt/dashboard'));
    }

    public function update_card($onu_id)
    {
        $step1 = str_replace('-', '/', $onu_id);
        $lastUnderscore = strrpos($step1, '_');
        if ($lastUnderscore !== false) {
            $gpon_id = substr_replace($step1, ':', $lastUnderscore, 1);
        } else {
            $gpon_id = $step1;
        }
        $gpon_id = str_replace('gpon/onu', 'gpon-onu', $gpon_id);

        $python = env('PYTHON_VENV');
        $script =env('SCRIPT_PATH')."update_onu_detail.py";

        //$pythonVenv = "/home/jamal/Prakom/ci-OLT/venv/bin/python3";
        //$scriptPath = "/home/jamal/Prakom/ci-OLT/scripts/update_onu_detail.py";

        // Ambil kredensial dari session
        $ip   = escapeshellarg(session()->get('ip_olt'));
        $user = escapeshellarg(session()->get('username'));
        $pass = escapeshellarg(session()->get('password'));
        $target = escapeshellarg($gpon_id);

        // Tambahkan kredensial ke command sebelum target gpon_id
        $command = "$python $script $ip $user $pass $target 2>&1";
        $output = shell_exec($command);

        if (trim($output) === "SUCCESS") {
            session()->setFlashdata('pesan', "Update Berhasil!");
            session()->setFlashdata('warna', 'success');
        } else {
            session()->setFlashdata('pesan', 'Gagal: ' . $output);
            session()->setFlashdata('warna', 'danger');
        }

        return redirect()->to(base_url('olt/dashboard'));
    }

    // Menampilkan halaman setting
    public function settings()
    {
        // Proteksi session agar tidak bisa diintip tanpa login
        if (!session()->get('ip_olt')) {
            return redirect()->to(base_url('olt'))->with('error', 'Silakan login terlebih dahulu.');
        }

        return view('settings_olt');
    }

    // Proses pengosongan tabel
    public function clear_database()
    {
        // Proteksi session agar tidak sembarang orang bisa menghapus database
        if (!session()->get('ip_olt')) {
            return redirect()->to(base_url('olt'));
        }

        $db = \Config\Database::connect();
        // Mengosongkan tabel onu_devices
        $db->table('onu_devices')->truncate();

        session()->setFlashdata('pesan', 'Database berhasil dikosongkan!');
        session()->setFlashdata('warna', 'success');

        return redirect()->to(base_url('olt/settings'));
    }

    public function logout()
    {
        // Menghapus semua data session yang kita set tadi
        session()->destroy();

        // Atau jika ingin spesifik:
        // session()->remove(['ip_olt', 'username', 'password', 'logged_in']);

        return redirect()->to(base_url('olt'))->with('pesan', 'Anda telah keluar dari sesi OLT.');
    }

}
