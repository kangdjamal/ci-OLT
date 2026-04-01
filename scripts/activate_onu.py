import sys
import time
from netmiko import ConnectHandler

# 1. Validasi Argumen (Total 11 argumen dari PHP + 1 nama script = 12)
if len(sys.argv) < 12:
    print("PYTHON_ERROR: Argumen kurang! Dibutuhkan 11 data (termasuk ONU Type).")
    sys.exit(1)

# Mapping data dari Controller
host        = sys.argv[1]
username    = sys.argv[2]
password    = sys.argv[3]
port        = sys.argv[4]  # Format: 1/2/6:11
sn          = sys.argv[5]
name        = sys.argv[6]
vlan        = sys.argv[7]
tcont       = sys.argv[8]
gemport     = sys.argv[9]
profile     = sys.argv[10]
onu_type    = sys.argv[11] # Tipe dari unconfig (misal: F660 atau ZTE-F660)

# Pecah port (1/2/6:11) menjadi parent (1/2/6) dan index (11)
try:
    parent_port, onu_index = port.split(':')
except ValueError:
    print(f"PYTHON_ERROR: Format port salah ({port}). Harusnya x/y/z:idx")
    sys.exit(1)

device = {
    "device_type": "zte_zxros_telnet",
    "host": host,
    "username": username,
    "password": password,
    "port": 23,
    "global_delay_factor": 2, # Memberi napas buat OLT yang agak lambat
    "fast_cli": False,
}

try:
    net_connect = ConnectHandler(**device)

    # --- TAHAP 1: REGISTRASI ONU ---
    # Kita harus masuk ke interface gpon-olt dulu
    reg_commands = [
        f'interface gpon-olt_{parent_port}',
        f'onu {onu_index} type {onu_type} sn {sn}',
        'exit'
    ]
    output_reg = net_connect.send_config_set(reg_commands)

    # Cek apakah registrasi ditolak OLT
    if "%" in output_reg or "Error" in output_reg:
        print(f"OLT_REJECTED_REG: {output_reg}")
        net_connect.disconnect()
        sys.exit(1)

    # --- TAHAP 2: KONFIGURASI SERVICE ---
    # Masuk ke interface gpon-onu yang baru saja dibuat
    conf_commands = [
        f'interface gpon-onu_{port}',
        f'name {name}',
        f'description {name}',
        f'tcont {tcont} profile {profile}',
        f'gemport {gemport} unicast tcont {tcont} dir both',
        f'gemport {gemport} traffic-limit upstream {profile} downstream {profile}',
        f'service-port {gemport} vport {gemport} user-vlan {vlan} vlan {vlan}',
        'exit'
    ]
    output_conf = net_connect.send_config_set(conf_commands)

    # Cek apakah konfigurasi ditolak
    if "% Invalid" in output_conf or "Error" in output_conf:
        print(f"OLT_REJECTED_CONF: {output_conf}")
        net_connect.disconnect()
        sys.exit(1)

    # --- TAHAP 3: SAVE (Opsional tapi disarankan) ---
    # net_connect.send_command('write')

    net_connect.disconnect()

    # Berikan tanda sukses untuk PHP
    print("success")
    print(output_reg)
    print(output_conf)

except Exception as e:
    print(f"PYTHON_ERROR: {str(e)}")
    sys.exit(1)
