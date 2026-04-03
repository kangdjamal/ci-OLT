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
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return redirect()->back()->with('error', 'Format IP OLT tidak valid!');
        }
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
        // 1. Inisialisasi koneksi ke variabel $db
        $db = \Config\Database::connect();

        // 2. Gunakan variabel $db tersebut untuk reconnect (Hapus $this->)
        $db->reconnect();
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

        $this->response->noCache();

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
        // 1. Rekonstruksi format index (gpon-onu_1-1-1_3 -> gpon-onu_1/1/1:3)
        $step1 = str_replace('-', '/', $onu_id);
        $lastUnderscore = strrpos($step1, '_');
        if ($lastUnderscore !== false) {
            $gpon_id = substr_replace($step1, ':', $lastUnderscore, 1);
        } else {
            $gpon_id = $step1;
        }
        $gpon_id = str_replace('gpon/onu', 'gpon-onu', $gpon_id);

        // 2. Siapkan eksekusi Python
        $python = env('PYTHON_VENV', 'python3');
        $script = env('SCRIPT_PATH') . "update_onu_detail.py";

        $ip     = escapeshellarg(session()->get('ip_olt'));
        $user   = escapeshellarg(session()->get('username'));
        $pass   = escapeshellarg(session()->get('password'));
        $target = escapeshellarg($gpon_id);

        // Jalankan script dan tangkap output JSON
        $command = "$python $script $ip $user $pass $target 2>&1";
        $output  = shell_exec($command);
        $result  = json_decode($output);

        // 3. Cek apakah Python berhasil memberikan data
        if ($result && isset($result->status) && $result->status === 'success') {

            // Update hanya kolom yang ada di SQLite Sukoharjo
            $db = \Config\Database::connect();
            $db->table('onu_devices')
            ->where('onu_index', $gpon_id)
            ->update([
                'status'  => $result->onu_status, // Misal: 'ready' atau 'working'
                'redaman' => $result->redaman     // String redaman lengkap dari OLT
            ]);

            session()->setFlashdata('pesan', "Update Berhasil! Data OLT terbaru untuk " . ($result->name ?? $gpon_id));
            session()->setFlashdata('warna', 'success');
        } else {
            // Jika Python gagal atau ada error login OLT
            $pesan_error = $result->message ?? "Output tidak dikenal: " . $output;
            session()->setFlashdata('pesan', 'Gagal Update: ' . $pesan_error);
            session()->setFlashdata('warna', 'danger');
        }

        // Kembali ke Dashboard
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

        $this->response->noCache();
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

    /////// AKTIVASI ////////////


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
        usleep(500000);
        //echo "Command: " . $command . "<br>";
        //echo "Raw Output: <pre>" . $output . "</pre>";
        //die("Proses Berhenti di Sini!");

        // 7. Berikan Feedback ke User


        if (strpos(strtolower($output), 'success') !== false) {


            // --- LOGIKA TAMBAHAN: AUTO SYNC DETAIL AGAR NAMA MUNCUL DI DASHBOARD ---
            sleep(1);
            $db = \Config\Database::connect();
            $db->query("PRAGMA wal_checkpoint(FULL);");
            $db->reconnect(); // Fresh connection

            $python_sync = env('PYTHON_VENV');
            $script_sync = env('SCRIPT_PATH') . "update_onu_detail.py"; // Pakai script detail
            $ip_sync     = escapeshellarg(session()->get('ip_olt'));
            $user_sync   = escapeshellarg(session()->get('username'));
            $pass_sync   = escapeshellarg(session()->get('password'));
            $target_sync = escapeshellarg($port_fix); // Port yang baru saja diaktifkan

            $cmd_sync = "$python_sync $script_sync $ip_sync $user_sync $pass_sync $target_sync 2>&1";
            $out_sync = shell_exec($cmd_sync);
            $res_sync = json_decode($out_sync);

            if ($res_sync && $res_sync->status === 'success') {
                // Update database SQLite agar Dashboard langsung kenal namanya
                $model_onu = new \App\Models\OnuModel();
                // Cari ID berdasarkan SN atau Port
                $existing = $model_onu->where('onu_index', $port_fix)->first();

                if ($existing) {
                    $model_onu->update($existing->id, [
                        'sn'     => $res_sync->sn,
                        'name'   => $res_sync->name,
                        'status' => $res_sync->onu_status,
                        'type'   => $res_sync->type ?? 'ZTE-F609' // Opsional jika ada
                    ]);
                }
            }
            // --- END LOGIKA TAMBAHAN ---

            // Balik ke halaman unconfig dengan pesan sukses
            $safe_id = str_replace(['/', ':'], ['-', '_'], $port_fix);

            return redirect()->to(base_url("olt/manage/$safe_id"))->with('success', "ONU $sn berhasil diaktivasi pada port $port_fix.");
        } else {
            // Balik ke form aktivasi sambil bawa pesan errornya
            // Ganti sementara buat debugging:
            return redirect()->back()->with('error', "Log OLT: <pre>$output</pre>")->withInput();
        }
    }

    //////////////////////
    //////////////////////



    public function manage($onu_id)
    {
        $model = new OnuModel();

        // 1. Transformasi URL ke format Database
        $db_index = str_replace('-', '/', $onu_id);
        $lastUnderscore = strrpos($db_index, '_');
        if ($lastUnderscore !== false) {
            $db_index = substr_replace($db_index, ':', $lastUnderscore, 1);
        }
        $db_index = str_replace('gpon/onu', 'gpon-onu', $db_index);

        // 2. Ambil data awal
        $onu = $model->where('onu_index', $db_index)->first();

        if (!$onu) {
            session()->setFlashdata('pesan', "Data ONU [$db_index] tidak ditemukan.");
            session()->setFlashdata('warna', 'danger');
            return redirect()->to(base_url('olt/dashboard'));
        }

        // 3. Validasi status
        $check_status = strtolower(trim($onu->status ?? ''));

        $allowed_status = ['ready', 'working', 'online', 'up'];

        if (!in_array($check_status, $allowed_status)) {
            $pesan = "ONU [$db_index] tidak dapat dimanage. ";

            if (empty($check_status) || $check_status == "-") {
                $pesan .= "Status perangkat belum terdeteksi atau belum diaktivasi.";
            } else {
                $pesan .= "Status saat ini: " . strtoupper($check_status);
            }

            session()->setFlashdata('pesan', $pesan);
            session()->setFlashdata('warna', 'warning');
            return redirect()->to(base_url('olt/dashboard'));
        }

        // 4. Jalankan Python
        $python = env('PYTHON_VENV');
        $script = env('SCRIPT_PATH') . "get_manage.py";
        $ip     = escapeshellarg(session()->get('ip_olt'));
        $user   = escapeshellarg(session()->get('username'));
        $pass   = escapeshellarg(session()->get('password'));
        $target = escapeshellarg($db_index);

        $command = "$python $script $ip $user $pass $target 2>&1";
        $output  = shell_exec($command);

        // Parsing JSON
        $result = json_decode($output);

        if ($result && $result->status === 'success') {
            // Update database lokal (opsional)
            $model->update($onu->id, [
                'sn'     => $result->sn,
                'name'   => $result->name,
                'status' => $result->onu_status
            ]);

            // Ambil data terbaru dari DB lalu timpa dengan data live untuk View
            $onu_updated = $model->where('onu_index', $db_index)->first();
            $onu_updated->redaman = $result->redaman;
            $onu_updated->profile = $result->profile;
            $onu_updated->vlan    = $result->vlan;
            $onu_updated->tcont   = $result->tcont;
            $onu_updated->gemport = $result->gemport;
            $onu_updated->sn      = $result->sn;
        } else {
            $error_msg = $result->message ?? $output;
            session()->setFlashdata('pesan', "Gagal Sync OLT: " . $error_msg);
            session()->setFlashdata('warna', 'warning');
            $onu_updated = $onu; // Fallback ke data lama jika gagal
        }

        $data = [
            'title'   => "Manage: " . $db_index,
            'onu'     => $onu_updated,
            'gpon_id' => $db_index
        ];

        return view('manage_onu', $data);
    }

//////////

public function update_config()
{
    $onu_index = $this->request->getPost('onu_index');
    $new_name  = $this->request->getPost('new_name');
    $profile   = $this->request->getPost('tcont_profile');
    $vlan      = $this->request->getPost('vlan_id');

    // 1. Siapkan Argumen Python
    $python = env('PYTHON_VENV');
    $script = env('SCRIPT_PATH') . "update_profil_onu.py";

    $ip     = escapeshellarg(session()->get('ip_olt'));
    $user   = escapeshellarg(session()->get('username'));
    $pass   = escapeshellarg(session()->get('password'));
    $target = escapeshellarg($onu_index);
    $name   = escapeshellarg($new_name);
    $prof   = escapeshellarg($profile);
    $vlan_arg = escapeshellarg($vlan);

    // 2. Eksekusi Python
    $command = "$python $script $ip $user $pass $target $name $prof $vlan_arg 2>&1";
    $output  = shell_exec($command);
    $result  = json_decode($output);

    // 3. Response handling
    if ($result && $result->status === 'success') {
        session()->setFlashdata('pesan', "Sukses! Konfigurasi ONU $onu_index telah diperbarui.");
        session()->setFlashdata('warna', 'success');
    } else {
        $error = $result->message ?? $output;
        session()->setFlashdata('pesan', "Gagal Update OLT: " . $error);
        session()->setFlashdata('warna', 'danger');
    }

    // 4. Redirect kembali ke halaman MANAGE (Gunakan format URL gpon-onu_1-2-6_11)
    $url_index = str_replace(['/', ':'], ['-', '_'], $onu_index);
    return redirect()->to(base_url("olt/manage/$url_index"));
}

    ///////

    public function delete_onu()
    {
        // 1. Tangkap data dari Form POST
        $user_answer    = $this->request->getPost('user_captcha');
        $correct_answer = session()->get('captcha_result');
        $onu_index      = $this->request->getPost('onu_index');

        // Pengaman: Jika data index kosong, jangan lanjut
        if (empty($onu_index)) {
            return redirect()->to(base_url('olt/dashboard'))->with('pesan', 'Error: Index ONU tidak ditemukan.')->with('warna', 'danger');
        }

        // Buat format URL untuk redirect (Contoh: gpon-onu_1/2/6:11 -> gpon-onu_1-2-6_11)
        $url_index = str_replace(['/', ':'], ['-', '_'], $onu_index);

        // 2. VALIDASI CAPTCHA (Server-side)
        if ($user_answer != $correct_answer) {
            // Bersihkan session agar angka berubah lagi
            session()->remove('captcha_result');

            // Kembalikan ke halaman manage dengan pesan error
            return redirect()->to(base_url("olt/manage/$url_index"))
            ->with('pesan', "Gagal Hapus: Jawaban Matematika Salah!")
            ->with('warna', 'danger');
        }

        // 3. JIKA CAPTCHA BENAR, LANJUT EKSEKUSI PYTHON
        // Ambil path dari .env untuk fleksibilitas server
        $python = env('PYTHON_VENV', 'python3'); // Fallback ke python3 jika env kosong
        $script = env('SCRIPT_PATH') . "delete_onu.py";

        // Ambil data session login OLT
        $ip     = escapeshellarg(session()->get('ip_olt'));
        $user   = escapeshellarg(session()->get('username'));
        $pass   = escapeshellarg(session()->get('password'));
        $target = escapeshellarg($onu_index);

        // Jalankan perintah shell
        $command = "$python $script $ip $user $pass $target 2>&1";
        $output  = shell_exec($command);
        $result  = json_decode($output);

        // Bersihkan captcha setelah eksekusi (berhasil/gagal)
        session()->remove('captcha_result');

        // 4. HANDLING RESPON DARI PYTHON
        if ($result && $result->status === 'success') {
            // Jika sukses hapus di OLT & DB, arahkan ke halaman Unconfigured
            return redirect()->to(base_url('olt/unconfig'))
            ->with('pesan', "Sukses: ONU $onu_index telah dihapus dari OLT dan Database.")
            ->with('warna', 'success');
        } else {
            // Jika Python gagal (misal telnet timeout atau DB error)
            $error_msg = $result->message ?? "Script Error: $output";
            return redirect()->to(base_url("olt/manage/$url_index"))
            ->with('pesan', "Gagal Eksekusi OLT: " . $error_msg)
            ->with('warna', 'danger');
        }
    }

    ////////

    public function logout()
    {
        // Menghapus semua data session yang kita set tadi
        session()->destroy();

        // Atau jika ingin spesifik:
        // session()->remove(['ip_olt', 'username', 'password', 'logged_in']);

        return redirect()->to(base_url('olt'))->with('pesan', 'Anda telah keluar dari sesi OLT.');
    }

}
