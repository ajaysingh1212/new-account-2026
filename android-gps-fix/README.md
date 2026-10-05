# TG1 Android tester corrections

This is a source replacement bundle, not a complete Android/Gradle project or APK. Preserve your existing project and activity_main.xml; the provided layout IDs already match.

## Confirmed findings
1. The screenshot reports TCP connect timeout from phone 192.168.1.61 to PC 192.168.1.60:9000. The server was bound to 127.0.0.1, so it could not receive LAN connections. This happens BEFORE JSON, CRC, licence or key validation.
2. The activity generates a random UUID and disables the IMEI input. Its UUID does not match the registered IMEI 353243280279794. A key belongs to its exact registered identifier: editing the app UUID to the registered IMEI fixes this; do not mix them.
3. The Java client uses verified TLS, but the previous local server used plaintext TCP. Use a TLS LAN listener AND trust its CA certificate. Never install trust-all code or disable endpoint identity checks.
4. TG1 header, UTF-8 length, CRC32b and actual LF framing in the supplied Java codec are correct.
5. user_id was sent as a string; Laravel accepts integer-like strings, so this was NOT the timeout cause. It now sends a numeric positive ID.
6. The old decoder used unlimited readLine, accepted arbitrary response codes and did not verify ACK packet_id. The replacement bounds replies and checks ACK identity. A stale socket or lost ACK triggers one reconnect/retry using the SAME packet_id/payload. TLS verification failures are never bypassed.
7. Buttons send manually entered coordinates, not actual phone GPS. There is no automatic heartbeat, foreground tracking service or location permission flow in this tester. A production app must implement those separately; don't represent these coordinates as sensor readings.

## Android integration
- Replace the two Java files from app/src/main/java/com/gpstester/app.
- Keep your existing activity_main.xml.
- Add app/src/main/res/xml/network_security_config.xml.
- Add app/src/main/res/raw/gps_lan_ca.pem (public CA certificate only).
- Merge AndroidManifest.merge.xml into your EXISTING manifest, preserving theme/activity configuration.
- minSdk 24 or later is required by this example's TLS endpoint identification APIs.
- If targetSdk >= 37, also merge app/AndroidManifest.sdk37.merge.xml; MainActivity requests ACCESS_LOCAL_NETWORK at runtime on Android 17+. Do not add this permission for targetSdk <= 36. Reference: https://developer.android.com/privacy-and-security/local-network-permission
- Never include server private keys in the app. Do not hardcode device_key in Java, XML, Git or logs. This tester only holds it in memory.

## Input values
Host: 192.168.1.60 (no tcp:// prefix)
Port: 9000
User: 13
Licence: SIM-PATNA-AJAY-001
IMEI: 353243280279794 (replace the generated UUID in the editable field)
Device key: the key already provided for this registered IMEI.

## Server
The LAN listener uses TLS on 192.168.1.60:9000; certificate SAN must include that IP.
Firewall should allow only your test phone/LAN and only this TCP port, never disable the entire firewall.
If DHCP changes the PC IP, update the listener, certificate SAN, Android domain config and host together.

Example command from D:/tracking/tracking2026:
php artisan gps:listen --host=192.168.1.60 --port=9000 --cert=storage/app/private/gps-tls/server.pem --key=storage/app/private/gps-tls/server.key

Watch terminal records:
Get-Content storage/logs/mobile-listener.log -Tail 20 -Wait

01 -> HANDSHAKE and ACK 80.
02 -> SAVED or LIVE_CACHED and ACK 80. saved=false can mean movement below the admin history radius, not a connection failure.
03 -> HEARTBEAT and ACK 80.
04 -> LOGOUT/offline and ACK 80.
REJECTED with ACK 81 means the network worked but identity/payload validation failed.
For invalid_fields inspect field_errors when provided. Never print the device_key/raw frame to logs.

TLS certificate trust reference: https://developer.android.com/privacy-and-security/security-config

## Verified on this PC
- Listener active on tls://192.168.1.60:9000.
- Windows firewall rule TG1-GPS-Test-Phone-9000 allows only remote phone 192.168.1.61 to local 192.168.1.60 TCP 9000. If the PHONE IP changes, update only this scoped rule through an administrator.
- LAN-address TLS test from this PC accepted 01, 02 and 03 for user 13 with certificate/hostname verification enabled.
- Manual location 25.5941 / 85.1376 saved in gps_locations id 38, provider manual, is_mock true, and live_cached true. This is explicitly a test point, not a reading from the phone sensor.
- Real fragmented TLS transport regression plus GPS tests: 38 passed, 142 assertions.
- Java protocol class compiled against installed Android SDK. Full APK build and actual phone end-to-end test remain unverified because the complete Android project/Gradle configuration was not supplied.

Listener TLS bug found during validation: implicit nonblocking TLS accepted encrypted records into the hex parser. The server now explicitly completes TLS on each accepted socket before parsing, rejects failed handshakes, and times out incomplete handshakes. Server-side verify_peer=false means client certificates are not required; mobile-to-server certificate and hostname verification remain enabled. Devices authenticate with their provisioned secret.
