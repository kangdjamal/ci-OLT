#!/home/jamal/Prakom/ci-OLT/venv/bin/python3
import os
import sys
from netmiko import ConnectHandler

# Tangkap argumen dari PHP (IP, User, Pass, Port)
if len(sys.argv) < 5:
    print("ERROR: Missing arguments (IP, User, Pass, Port)")
    sys.exit(1)

# Konfigurasi Koneksi Telnet (ZTE C320/C300)
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

    # Ambil port dari argumen ke-4
    target_port = sys.argv[4]

    # Jalankan perintah state untuk port tersebut
    command = f'show gpon onu state gpon-olt_{target_port}'
    output = net_connect.send_command(command)

    # Putus koneksi
    net_connect.disconnect()

    # Cetak output mentah (Raw Text)
    print(output)

except Exception as e:
    print(f"ERROR: {str(e)}")
    sys.exit(1)
