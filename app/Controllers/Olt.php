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

    public function unconfig()
    {
        $python = env('PYTHON_VENV');
        // Pastikan Anda membuat script python baru bernama 'get_unconfig.py'
        // atau sesuaikan dengan nama script python Anda
        $script = env('SCRIPT_PATH') . "get_unconfig.py";

        $ip   = escapeshellarg(session()->get('ip_olt'));
        $user = escapeshellarg(session()->get('username'));
        $pass = escapeshellarg(session()->get('password'));

        // Jalankan script python untuk ambil output 'show gpon onu uncfg'
        $command = "$python $script $ip $user $pass 2>&1";
        $output = shell_exec($command);

        // Kirim data ke view
        $data = [
            'title'      => "ONU Unconfigured",
            'activeMenu' => "unconfig",
            'raw_output' => $output, // Untuk debugging jika perlu
            'onu_list'   => $this->parseOnu($output)
        ];

        return view('unconfig', $data);
    }

    private function parseOnu($output)
    {
        $list = [];

        // Cek jika kosong atau error
        if (strpos($output, 'No related information') !== false || strpos($output, 'ERROR') !== false) {
            return [];
        }

        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            // Regex ini menangkap format ZTE: gpon-onu_1/1/1:1  SN:ZTEGC1234567  Type:F660
            // Sesuaikan spasi jika output asli OLT sedikit berbeda
            if (preg_match('/gpon-onu_(\d+\/\d+\/\d+:\d+)\s+([A-Z0-9]+)\s+(\S+)/i', trim($line), $matches)) {
                $list[] = [
                    'port'  => $matches[1],
                    'sn'    => $matches[2],
                    'model' => $matches[3]
                ];
            }
        }

        return $list;
    }

    public function auth_page($port_slug = null, $sn = null, $onu_type = null)
    {
        // --- STEP 1: NORMALISASI DATA ---
        // Mengubah slug URL (1_2_6:1) jadi format asli (1/2/6:1)
        $port_full = str_replace('_', '/', $port_slug);

        // Pecah untuk ambil Interface Head saja (1/2/6)
        $port_parts = explode(':', $port_full);
        $parent_port = $port_parts[0];

        // --- STEP 2: LOGIC DETEKSI TETANGGA ---
        $neighbors = "";
        $executed_cmd = "";
        $show_neighbors = $this->request->getPost('action') === 'show_neighbors';

        if ($show_neighbors) {
            $ip     = session()->get('ip_olt');
            $user   = session()->get('username');
            $pass   = session()->get('password');
            $python = env('PYTHON_VENV');
            $script = env('SCRIPT_PATH') . "get_onu_neighbours.py";

            // Kirim ke Python format 1/2/6
            $port_s = escapeshellarg($parent_port);
            $command = "$python $script " . escapeshellarg($ip) . " " . escapeshellarg($user) . " " . escapeshellarg($pass) . " $port_s 2>&1";

            $neighbors = shell_exec($command);

            // Command untuk Card CLI (Pakai Spasi agar tidak % Invalid)
            $executed_cmd = "show gpon onu state gpon-olt " . $parent_port;
        }

        // --- STEP 3: LEMPAR KE VIEW ---
        $data = [
            'port'           => $port_full, // NOC melihat 1/2/6:1 di input text
            'sn'             => $sn,
            'onu_type'       => ($onu_type == 'unknown' || empty($onu_type)) ? 'ZTE-F609' : $onu_type,
            'neighbors'      => $neighbors,
            'show_neighbors' => $show_neighbors,
            'executed_cmd'   => $executed_cmd
        ];

        return view('auth_page', $data);
    }

    // app/Controllers/Olt.php

    public function activate_process()
    {
        // 1. Tangkap data dari form (Customer & VLAN)
        // 1. Tangkap Port
        $port_raw = $this->request->getPost('port');

        // 2. WAJIB REFORMASI FORMAT DI SINI
        $port_fix = str_replace(['_', '-'], ['/', ':'], $port_raw);

        $sn      = $this->request->getPost('sn');
        $name    = $this->request->getPost('customer_name');
        $onu_type = $this->request->getPost('onu_type');
        $vlan    = $this->request->getPost('vlan');
        $profile = $this->request->getPost('profile');

        // 2. Kunci T-CONT & Gemport di angka 2 (Instruksi Penyelia)
        // Walaupun di form ada input hidden, kita timpa di sini agar lebih aman
        $tcont   = "2";
        $gemport = "2";

        // 3. Ambil Kredensial OLT dari Session Login
        $ip   = session()->get('ip_olt');
        $user = session()->get('username');
        $pass = session()->get('password');

        // 4. Konfigurasi Path (Sesuai .env Docker kemarin)
        $python = env('PYTHON_VENV') ?? '/usr/bin/python3';
        $script = env('SCRIPT_PATH') . "activate_onu.py";

        // 5. Susun Command Shell (Escaping agar aman dari karakter aneh)
        // Pastikan urutan argv di Python: 1:IP, 2:User, 3:Pass, 4:Port, 5:SN, 6:Name, 7:VLAN, 8:Tcont, 9:Gemport, 10:Profile
        $args = escapeshellarg($ip) . " " .
        escapeshellarg($user) . " " .
        escapeshellarg($pass) . " " .
        escapeshellarg($port_fix) . " " .
        escapeshellarg($sn) . " " .
        escapeshellarg($name) . " " .
        escapeshellarg($vlan) . " " .
        escapeshellarg($tcont) . " " .
        escapeshellarg($gemport) . " " .
        escapeshellarg($profile). " " .
        escapeshellarg($onu_type); // Tambah argumen ke-11

        $command = "$python $script $args 2>&1";

        // 6. Eksekusi & Tangkap Output (Ini yang bikin loading agak lama)
        $output  = shell_exec($command);
        //echo "Command: " . $command . "<br>";
        //echo "Raw Output: <pre>" . $output . "</pre>";
        //die("Proses Berhenti di Sini!");

        // 7. Berikan Feedback ke User
        if (strpos(strtolower($output), 'success') !== false) {
            // Balik ke halaman unconfig dengan pesan sukses
            return redirect()->to(base_url('olt/unconfig'))->with('success', "ONU $sn berhasil diaktivasi pada port $port.");
        } else {
            // Balik ke form aktivasi sambil bawa pesan errornya
            // Ganti sementara buat debugging:
            return redirect()->back()->with('error', "Log OLT: <pre>$output</pre>")->withInput();
        }
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
