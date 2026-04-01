#!/home/jamal/Prakom/ci-OLT/venv/bin/python3
import os
import sys
from netmiko import ConnectHandler

# Tangkap argumen dari PHP (IP, User, Pass)
if len(sys.argv) < 4:
    print("ERROR: Missing arguments")
    sys.exit(1)

# Konfigurasi Koneksi Telnet (ZTE C320)
device = {
    "device_type": "zte_zxros_telnet",
    "host": sys.argv[1],
    "username": sys.argv[2],
    "password": sys.argv[3],
    "port": 23,
}

try:
    # Koneksi ke OLT
    net_connect = ConnectHandler(**device)

    # Jalankan perintah uncfg
    output = net_connect.send_command('show gpon onu uncfg')

    # Putus koneksi
    net_connect.disconnect()

    # Cetak output mentah agar ditangkap oleh shell_exec di PHP
    print(output)

except Exception as e:
    # Jika error, kirim pesan agar tertangkap di catch PHP
    print(f"ERROR: {str(e)}")
    sys.exit(1)
