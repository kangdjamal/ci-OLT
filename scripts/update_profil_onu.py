#!/usr/bin/python3
import sys
import json
import sqlite3
import os  # Tambahkan ini untuk membaca env
from datetime import datetime
from netmiko import ConnectHandler

# 1. Cek Argumen
if len(sys.argv) < 8:
    print(json.dumps({"status": "error", "message": "Missing arguments"}))
    sys.exit(1)

# Mapping Argumen
host      = sys.argv[1]
username  = sys.argv[2]
password  = sys.argv[3]
onu_index = sys.argv[4]
new_name  = sys.argv[5]
new_prof  = sys.argv[6]
new_vlan  = sys.argv[7]

# 2. Path Database (Mengikuti standar script kamu yang lain)
# Membaca dari environment variable DB_PATH, jika tidak ada pakai /tmp/onu.sqlite3
DB_PATH = os.getenv("DB_PATH", "/tmp/onu.sqlite3")

device = {
    "device_type": "zte_zxros_telnet",
    "host": host,
    "username": username,
    "password": password,
    "port": 23,
}

try:
    # --- 1. EKSEKUSI KE OLT ---
    net_connect = ConnectHandler(**device)
    name_for_olt = new_name.replace(" ", "_")

    commands = [
        f"interface {onu_index}",
        f"name {name_for_olt}",
        f"description {name_for_olt}",
        f"tcont 2 profile {new_prof}",
        f"service-port 2 vport 2 user-vlan {new_vlan} vlan {new_vlan}"
    ]

    net_connect.send_config_set(commands)
    net_connect.disconnect()

    # --- 2. UPDATE KE SQLITE ---
    # Pastikan file database ada sebelum mencoba koneksi
    if os.path.exists(DB_PATH):
        conn = sqlite3.connect(DB_PATH)
        cursor = conn.cursor()

        now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

        cursor.execute("""
            UPDATE onu_devices
            SET name = ?,
                last_update = ?
            WHERE onu_index = ?
        """, (new_name, now, onu_index))

        conn.commit()
        conn.close()
        db_status = "DB Updated"
    else:
        db_status = f"DB Not Found at {DB_PATH}"

    print(json.dumps({
        "status": "success",
        "message": f"OLT OK | {db_status}"
    }))

except Exception as e:
    print(json.dumps({"status": "error", "message": str(e)}))
