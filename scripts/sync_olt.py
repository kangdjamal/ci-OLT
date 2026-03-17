#!/home/jamal/Prakom/ci-OLT/venv/bin/python3
import os
import sys
import sqlite3
from pathlib import Path
from datetime import datetime
from dotenv import load_dotenv
from netmiko import ConnectHandler


# Tangkap argumen dari PHP (IP, User, Pass)
if len(sys.argv) < 4:
    print("ERROR: Missing arguments (IP, User, or Password)")
    sys.exit(1)

# Konfigurasi Koneksi Telnet menggunakan sys.argv
device = {
    "device_type": "zte_zxros_telnet",
    "host": sys.argv[1],
    "username": sys.argv[2],
    "password": sys.argv[3],
    "port": 23,
}

db_path = os.getenv("DB_PATH","/tmp/onu.sqlite3")

try:
    # Koneksi ke OLT
    net_connect = ConnectHandler(**device)
    output = net_connect.send_command('show gpon onu state')

    # Koneksi ke SQLite
    conn = sqlite3.connect(db_path)
    cursor = conn.cursor()

    # Flag: Ambil waktu sekarang
    now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

    for line in output.splitlines():
        if 'gpon-onu_' in line:
            parts = line.split()
            # Parsing kolom: Index, Admin State, Phase State
            onu_index = parts[0]
            admin_st  = parts[1]
            phase_st  = parts[4] # Biasanya 'working' atau 'OffLine'

            # Update atau Insert jika belum ada
            cursor.execute("""
                INSERT INTO onu_devices (onu_index, admin_state, phase_state, last_update)
                VALUES (?, ?, ?, ?)
                ON CONFLICT(onu_index) DO UPDATE SET
                admin_state = excluded.admin_state,
                phase_state = excluded.phase_state,
                last_update = excluded.last_update
            """, (onu_index, admin_st, phase_st, now))

    conn.commit()
    conn.close()
    net_connect.disconnect()

    # Output tunggal agar ditangkap PHP
    print("SUCCESS")

except Exception as e:
    print(str(e))
    sys.exit(1)
