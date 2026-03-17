<?php

namespace App\Models;

use CodeIgniter\Model;

class OnuModel extends Model
{
    // Menggunakan grup koneksi 'onu_db' yang sudah kita setting di Database.php
    protected $DBGroup          = 'onu_db';
    protected $table            = 'onu_devices';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;

    // Daftar kolom yang diizinkan untuk diisi/diupdate
    protected $allowedFields    = [
        'onu_index',
        'name',
        'type',
        'status',
        'redaman',
        'admin_state',
        'phase_state',
        'last_update'
    ];

    // Menangani timestamp otomatis untuk last_update
    protected $useTimestamps = false;
}
