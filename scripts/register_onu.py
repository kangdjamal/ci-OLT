#!/home/jamal/Prakom/ci-OLT/venv/bin/python3
import sys
import argparse
from netmiko import ConnectHandler

def main():
    if len(sys.argv) < 4:
        print("ERROR: Missing OLT credentials (IP, User, or Password)")
        sys.exit(1)

    olt_ip = sys.argv[1]
    olt_user = sys.argv[2]
    olt_pass = sys.argv[3]

    parser = argparse.ArgumentParser(description="ZTE C320 ONU Registration Script")
    parser.add_argument("--port", required=True)
    parser.add_argument("--sn", required=True)
    parser.add_argument("--name", required=True)
    parser.add_argument("--vlan", required=True)
    parser.add_argument("--tcont", default="1")
    parser.add_argument("--gemport", default="1")
    parser.add_argument("--profile", default="UP-100M")
    parser.add_argument("--type", default="ZTE-F609")

    args = parser.parse_known_args(sys.argv[4:])[0]

    device = {
        "device_type": "zte_zxros_telnet",
        "host": olt_ip,
        "username": olt_user,
        "password": olt_pass,
        "port": 23,
    }

    try:
        if ":" not in args.port:
            print("ERROR: Invalid Port Format (e.g., 1/1/1:1)")
            sys.exit(1)

        iface_olt, onu_idx = args.port.split(":")

        net_connect = ConnectHandler(**device)

        # Seluruh perintah dijalankan tanpa komentar/hashtag
        commands = [
            f"interface gpon-olt_{iface_olt}",
            f"onu {onu_idx} type {args.type} sn {args.sn}",
            "exit",
            f"interface gpon-onu_{args.port}",
            f"name {args.name}",
            f"description {args.name.replace(' ', '_')}",
            "sn-bind enable sn",
            f"tcont {args.tcont} profile {args.profile}",
            f"gemport {args.gemport} tcont {args.tcont}",
            "exit",
            f"pon-onu-mng gpon-onu_{args.port}",
            f"service internet gemport {args.gemport} iphost 1 vlan {args.vlan}",
            "exit",
            f"interface gpon-onu_{args.port}",
            f"service-port 1 vport 1 user-vlan {args.vlan} vlan {args.vlan}"
        ]

        output = net_connect.send_config_set(commands)
        net_connect.disconnect()

        print(f"SUCCESS: ONU {args.sn} registered at {args.port}. Data is in running-config.")

    except Exception as e:
        print(f"ERROR: {str(e)}")
        sys.exit(1)

if __name__ == "__main__":
    main()
