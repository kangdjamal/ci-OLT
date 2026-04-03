#!/usr/bin/python3
import sys
import sqlite3
import re
import time
import json
import os
from pathlib import Path
from datetime import datetime
from dotenv import load_dotenv
from netmiko import ConnectHandler

# Pastikan argumen lengkap: [0]script, [1]IP, [2]User, [3]Pass, [4]ONU_Index
if len(sys.argv) < 5:
    print("ERROR: Missing arguments. Usage: script.py IP User Pass ONU_Index")
    sys.exit(1)

device = {
    "device_type": "zte_zxros_telnet",
    "host": sys.argv[1],
    "username": sys.argv[2],
    "password": sys.argv[3],
    "port": 23,
}
onu_index = sys.argv[4] # Format: gpon-onu_1/1/1:3

db_path = os.getenv("DB_PATH", "/tmp/onu.sqlite3")

try:
    net_connect = ConnectHandler(**device)
    out_detail = net_connect.send_command(f"show gpon onu detail-info {onu_index}")
    time.sleep(1)


# --- 1. Inisialisasi Nilai Default (WAJIB) ---
    # Ini agar jika parsing gagal, variabel tetap ada dan tidak Error 'not defined'
    nama = "N/A"
    tipe = "N/A"
    status = "N/A"
    sn = "-"
    redaman_raw = "--"
    profile = "N/A"
    vlan = "0"
    tcont_id = "1"
    gemport = "1"


    # --- 2. Proses Parsing Output Detail Info ---
    # Anggap out_detail adalah hasil dari: net_connect.send_command("show gpon onu detail-info ...")
    if out_detail:
        for line in out_detail.splitlines():
            if "Name:" in line:
                nama = line.split("Name:")[1].strip()
            if "Type:" in line:
                tipe = line.split("Type:")[1].strip()
            if "State:" in line:
                status = line.split("State:")[1].strip()

    # --- 3. Proses Output Power Attenuation ---
    # Anggap out_pwr adalah hasil dari:
# --- Ambil data Power dari OLT ---
    out_pwr = net_connect.send_command(f"show pon power attenuation {onu_index}")

    redaman_final = "N/A" # Default jika data tidak ditemukan

    if out_pwr:
        # 1. Bersihkan output mentah
        redaman_raw = out_pwr.strip()

        # 2. Cari semua angka (termasuk desimal dan negatif)
        matches = re.findall(r"[-+]?\d*\.\d+|\d+", redaman_raw)

        # 3. Jika data lengkap (ZTE normalnya 6 angka), susun ulang formatnya
        if len(matches) >= 6:
            redaman_final = (
                f"ONU Tx: {matches[1]} dBm\n"
                f"OLT Rx: {matches[0]} dBm\n"
                f"Redaman UP: {matches[2]} dB\n"
                f"==================\n"
                f"OLT Tx: {matches[3]} dBm\n"
                f"ONU Rx: {matches[4]} dBm\n"
                f"Redaman DOWN: {matches[5]} dB"
            )
        else:
            # Jika ONU Offline biasanya output tidak lengkap, simpan mentahnya saja
            redaman_final = "N/A"

    # --- 4. Update ke Database SQLite ---
    conn = sqlite3.connect(db_path)
    cursor = conn.cursor()

    sql = """
        UPDATE onu_devices
        SET name = ?,
            type = ?,
            status = ?,
            redaman = ?,
            last_update = CURRENT_TIMESTAMP
        WHERE onu_index = ?
    """

    # Masukkan redaman_final ke dalam tuple execute
    cursor.execute(sql, (nama, tipe, status, redaman_final, onu_index))

    conn.commit()
    conn.close()

    net_connect.disconnect()
    print(json.dumps({
    "status": "success",
    "name": nama,
    "sn": sn,
    "onu_status": status,
    "redaman":redaman_final,
    "profile": profile,
    "vlan": vlan,
    "tcont": tcont_id,
    "gemport": gemport
    }))

except Exception as e:
    print(f"Error OLT: {str(e)}")
    sys.exit(1)
