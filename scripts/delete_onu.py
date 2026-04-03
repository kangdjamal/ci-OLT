#!/usr/bin/python3
import sys
import json
import sqlite3
import os
from netmiko import ConnectHandler

# 1. Cek Argumen
if len(sys.argv) < 5:
    print(json.dumps({"status": "error", "message": "Missing arguments"}))
    sys.exit(1)

host      = sys.argv[1]
username  = sys.argv[2]
password  = sys.argv[3]
onu_index = sys.argv[4]

# Path Database (Pastikan ini sesuai dengan lokasi di server Sukoharjo kamu)
DB_PATH = os.getenv("DB_PATH", "/var/www/html/ci-olt/writable/onu.sqlite3")

device = {
    "device_type": "zte_zxros_telnet",
    "host": host,
    "username": username,
    "password": password,
    "port": 23,
}

try:
    # --- 1. PROSES HAPUS DI OLT ---
    net_connect = ConnectHandler(**device)

    # Parsing: gpon-onu_1/2/6:11 -> olt_port=gpon-olt_1/2/6, onu_id=11
    olt_port = onu_index.replace("gpon-onu", "gpon-olt").split(":")[0]
    onu_id = onu_index.split(":")[1]

    commands = [
        f"interface {olt_port}",
        f"no onu {onu_id}",
        "exit"
    ]

    net_connect.send_config_set(commands)
    net_connect.disconnect()

    # --- 2. PROSES HAPUS DI SQLITE ---
    db_status = "DB Not Updated"
    if os.path.exists(DB_PATH):
        conn = sqlite3.connect(DB_PATH)
        cursor = conn.cursor()
        cursor.execute("DELETE FROM onu_devices WHERE onu_index = ?", (onu_index,))
        conn.commit()
        conn.close()
        db_status = "Data Deleted from DB"
    else:
        db_status = f"DB File Not Found at {DB_PATH}"

    print(json.dumps({
        "status": "success",
        "message": f"ONU {onu_index} Berhasil Dihapus (OLT & DB)."
    }))

except Exception as e:
    print(json.dumps({"status": "error", "message": str(e)}))
