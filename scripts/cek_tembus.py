#!/home/jamal/Prakom/ci-OLT/venv/bin/python3
import sys
from netmiko import ConnectHandler

# Pastikan argumen cukup
if len(sys.argv) < 4:
    print("ERROR: Argumen tidak lengkap")
    sys.exit(1)

device = {
    "device_type": "zte_zxros_telnet",
    "host": sys.argv[1],
    "username": sys.argv[2],
    "password": sys.argv[3],
    "port": 23,
    "timeout": 10, # Timeout singkat agar tidak menunggu lama jika IP salah
}

try:
    net_connect = ConnectHandler(**device)
    # Jalankan perintah paling ringan hanya untuk verifikasi
    net_connect.send_command('show card')
    net_connect.disconnect()

    # Cetak SUCCESS jika sampai tahap ini tanpa exception
    print("SUCCESS")

except Exception as e:
    # Cetak pesan error agar bisa ditangkap PHP
    print(f"AUTH_FAILED: Login Gagal")
    sys.exit(1)
