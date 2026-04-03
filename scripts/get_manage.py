#!/usr/bin/python3
import sys
import json
import re
import os
from netmiko import ConnectHandler

# 1. Validasi Argumen
if len(sys.argv) < 5:
    print(json.dumps({"status": "error", "message": "Missing arguments"}))
    sys.exit(1)

host = sys.argv[1]
username = sys.argv[2]
password = sys.argv[3]
onu_index = sys.argv[4]

db_path = os.getenv("DB_PATH", "/tmp/onu.sqlite3")

device = {
    "device_type": "zte_zxros_telnet",
    "host": host,
    "username": username,
    "password": password,
    "port": 23,
    "timeout": 15
}

def parse_all(detail, run, out_pwr):
    data = {
        "status": "success",
        "name": "-",
        "sn": "-",
        "onu_status": "Unknown",
        "redaman": "N/A",
        "profile": "N/A",
        "vlan": "0",
        "tcont": "1",
        "gemport": "1"
    }

    # --- 1. Parsing Detail Info ---
    m_name = re.search(r"Name:\s+(.*)", detail)
    m_sn = re.search(r"Serial number:\s+(.*)", detail)
    m_state = re.search(r"Phase state:\s+(.*)", detail)

    if m_name: data["name"] = m_name.group(1).strip()
    if m_sn: data["sn"] = m_sn.group(1).strip()
    if m_state: data["onu_status"] = m_state.group(1).strip()

    # --- 2. Parsing Running Config ---
    m_prof = re.search(r"tcont (\d+) profile (.*)", run)
    m_vlan = re.search(r"vlan (\d+)", run)
    m_gem = re.search(r"gemport (\d+)", run)

    if m_prof:
        data["tcont"] = m_prof.group(1)
        data["profile"] = m_prof.group(2).strip()
    if m_vlan: data["vlan"] = m_vlan.group(1)
    if m_gem: data["gemport"] = m_gem.group(1)

    # --- 3. Parsing Redaman (Teknik Mas Jamal) ---
    if out_pwr:
        redaman_raw = out_pwr.strip()
        # Cari semua angka (termasuk desimal dan negatif)
        matches = re.findall(r"[-+]?\d*\.\d+|\d+", redaman_raw)

        # Jika data lengkap (ZTE normalnya 6 angka untuk attenuation)
        if len(matches) >= 6:
            data["redaman"] = (
                f"ONU Tx: {matches[1]} dBm\n"
                f"OLT Rx: {matches[0]} dBm\n"
                f"Redaman UP: {matches[2]} dB\n"
                f"==================\n"
                f"OLT Tx: {matches[3]} dBm\n"
                f"ONU Rx: {matches[4]} dBm\n"
                f"Redaman DOWN: {matches[5]} dB"
            )
        else:
            data["redaman"] = "ONU Offline / LOS"

    return data

try:
    net_connect = ConnectHandler(**device)

    # Ambil data real dari OLT
    d_out = net_connect.send_command(f"show gpon onu detail-info {onu_index}")
    r_out = net_connect.send_command(f"show running-config interface {onu_index}")
    p_out = net_connect.send_command(f"show pon power attenuation {onu_index}")

    net_connect.disconnect()

    # Gabungkan hasil parsing
    result = parse_all(d_out, r_out, p_out)
    result['onu_index'] = onu_index

    # Update Database Background
    try:
        import sqlite3
        conn = sqlite3.connect(db_path)
        cur = conn.cursor()
        sql = """UPDATE onus SET name=?, status=?, sn=?, redaman=?, profile=?, vlan=?, last_sync=CURRENT_TIMESTAMP
                 WHERE onu_index=?"""
        cur.execute(sql, (result['name'], result['onu_status'], result['sn'], result['redaman'], result['profile'], result['vlan'], onu_index))
        conn.commit()
        conn.close()
    except:
        pass

    # Output JSON untuk PHP
    print(json.dumps(result))

except Exception as e:
    print(json.dumps({"status": "error", "message": str(e)}))
    sys.exit(1)
