<?php
// Cek file .env (apakah sudah terbaca?)
$path_env = getenv('SCRIPT_PATH');
$full_path = $path_env . "cek_tembus.py";

echo "Path dari ENV: " . $path_env . "<br>";
echo "Full Path: " . $full_path . "<br>";
echo "File Eksis? " . (file_exists($full_path) ? "YA" : "TIDAK") . "<br>";

// Tes eksekusi sederhana
$output = shell_exec("ls -l " . $full_path);
echo "Output LS: <pre>$output</pre>";
