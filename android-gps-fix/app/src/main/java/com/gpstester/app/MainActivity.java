package com.gpstester.app;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;
import javax.net.ssl.SSLHandshakeException;
import java.net.SocketTimeoutException;
import java.net.ConnectException;

import androidx.appcompat.app.AppCompatActivity;

import org.json.JSONObject;

import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;
import java.util.TimeZone;
import java.util.UUID;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class MainActivity extends AppCompatActivity {

    private EditText etHost, etPort, etUserId, etImei;
    private EditText etLicense, etDeviceKey;
    private EditText etLatitude, etLongitude;

    private Button btnHandshake, btnLocation;
    private Button btnHeartbeat, btnLogout;

    private TextView tvResult;
    private volatile boolean destroyed;
    private int pendingCode;
    private static final int LAN_PERMISSION_REQUEST = 9000;

    private final ExecutorService executor =
            Executors.newSingleThreadExecutor();

    private MobileGpsProtocol.Client gpsClient;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        initializeViews();

        // Use the identifier registered on the server; never silently replace it.
        setupDeviceId();

        gpsClient = new MobileGpsProtocol.Client();

        btnHandshake.setOnClickListener(v -> sendPacket(1));
        btnLocation.setOnClickListener(v -> sendPacket(2));
        btnHeartbeat.setOnClickListener(v -> sendPacket(3));
        btnLogout.setOnClickListener(v -> sendPacket(4));
    }

    private void initializeViews() {
        etHost = findViewById(R.id.etHost);
        etPort = findViewById(R.id.etPort);
        etUserId = findViewById(R.id.etUserId);
        etImei = findViewById(R.id.etImei);
        etLicense = findViewById(R.id.etLicense);
        etDeviceKey = findViewById(R.id.etDeviceKey);
        etLatitude = findViewById(R.id.etLatitude);
        etLongitude = findViewById(R.id.etLongitude);

        btnHandshake = findViewById(R.id.btnHandshake);
        btnLocation = findViewById(R.id.btnLocation);
        btnHeartbeat = findViewById(R.id.btnHeartbeat);
        btnLogout = findViewById(R.id.btnLogout);

        tvResult = findViewById(R.id.tvResult);
    }

    /**
     * Generates a stable UUID once and reuses it.
     * This UUID must match the IMEI/identifier registered
     * on the Laravel server.
     */
    private void setupDeviceId() {
        SharedPreferences prefs =
                getSharedPreferences("gps_tester", MODE_PRIVATE);

        String deviceId = prefs.getString("device_id", null);

        if (deviceId == null || deviceId.trim().isEmpty()) {
            deviceId = UUID.randomUUID().toString();

            prefs.edit()
                    .putString("device_id", deviceId)
                    .apply();
        }

        etImei.setText(deviceId);
        etImei.setEnabled(true);
        etHost.setText("192.168.1.60");
        etPort.setText("9000");
    }

    private String getText(EditText editText) {
        return editText.getText().toString().trim();
    }

    private String getUtcTimestamp() {
        SimpleDateFormat sdf = new SimpleDateFormat(
                "yyyy-MM-dd'T'HH:mm:ss'Z'",
                Locale.US
        );

        sdf.setTimeZone(TimeZone.getTimeZone("UTC"));

        return sdf.format(new Date());
    }

    private JSONObject buildPayload(int code) throws Exception {
        String userId = getText(etUserId);
        String imei = getText(etImei);
        String license = getText(etLicense);
        String deviceKey = getText(etDeviceKey);

        if (userId.isEmpty()) {
            throw new Exception("User ID required.");
        }

        long numericUserId;
        try {
            numericUserId = Long.parseLong(userId);
            if (numericUserId <= 0) throw new NumberFormatException();
        } catch (NumberFormatException e) {
            throw new Exception("User ID must be a positive integer.");
        }
        if (!imei.matches("(?:[0-9]{15}|[0-9a-fA-F]{8}-(?:[0-9a-fA-F]{4}-){3}[0-9a-fA-F]{12})")) {
            throw new Exception("Enter the exact registered IMEI or installation UUID.");
        }

        if (license.isEmpty()) {
            throw new Exception("License Number required.");
        }

        if (!deviceKey.matches("[0-9a-fA-F]{64}")) {
            throw new Exception(
                    "Device Key must be a 64-character hexadecimal key."
            );
        }

        JSONObject payload = new JSONObject();

        payload.put("user_id", numericUserId);
        payload.put("license_number", license);
        payload.put("imei", imei);
        payload.put("device_key", deviceKey);
        payload.put("packet_id", UUID.randomUUID().toString());
        payload.put("recorded_at", getUtcTimestamp());

        if (code == 2) {
            String latitude = getText(etLatitude);
            String longitude = getText(etLongitude);

            if (latitude.isEmpty() || longitude.isEmpty()) {
                throw new Exception(
                        "Latitude and Longitude required."
                );
            }

            double lat = Double.parseDouble(latitude);
            double lon = Double.parseDouble(longitude);

            if (Double.isNaN(lat) || Double.isNaN(lon) || Double.isInfinite(lat) || Double.isInfinite(lon) || lat < -90 || lat > 90 ||
                    lon < -180 || lon > 180) {
                throw new Exception("Invalid coordinates.");
            }

            payload.put("source_type", "android");
            payload.put("latitude", lat);
            payload.put("longitude", lon);
            payload.put("provider", "manual");
            payload.put("is_mock", true);
        }

        // Manual tester does not claim GPS is enabled without checking the OS.

        return payload;
    }

    private String getPacketName(int code) {
        switch (code) {
            case 1:
                return "Handshake / Hello";
            case 2:
                return "Location";
            case 3:
                return "Heartbeat";
            case 4:
                return "Stop Tracking / Offline";
            default:
                return "Unknown";
        }
    }

    private void sendPacket(int code) {
        String lanPermission = "android.permission.ACCESS_LOCAL_NETWORK";
        if (android.os.Build.VERSION.SDK_INT >= 37 && getApplicationInfo().targetSdkVersion >= 37
                && checkSelfPermission(lanPermission) != android.content.pm.PackageManager.PERMISSION_GRANTED) {
            pendingCode = code;
            requestPermissions(new String[]{lanPermission}, LAN_PERMISSION_REQUEST);
            return;
        }
        final String host = getText(etHost);
        final String portText = getText(etPort);

        if (host.isEmpty() || host.contains("://") || host.contains("/") || host.matches(".*\\s.*")) {
            Toast.makeText(
                    this,
                    "Enter only an IP or hostname, without tcp:// or a port.",
                    Toast.LENGTH_SHORT
            ).show();
            return;
        }

        final int port;

        try {
            port = Integer.parseInt(portText);

            if (port < 1 || port > 65535) {
                throw new NumberFormatException();
            }
        } catch (NumberFormatException e) {
            Toast.makeText(
                    this,
                    "Enter a valid server port.",
                    Toast.LENGTH_SHORT
            ).show();
            return;
        }

        final JSONObject payload;

        try {
            payload = buildPayload(code);
        } catch (Exception e) {
            Toast.makeText(
                    this,
                    e.getMessage(),
                    Toast.LENGTH_LONG
            ).show();
            return;
        }

        getSharedPreferences("gps_tester", MODE_PRIVATE).edit()
                .putString("device_id", getText(etImei)).apply();
        setButtonsEnabled(false);
        tvResult.setText("Sending " + getPacketName(code) + "...");

        executor.execute(() -> {
            try {
                JSONObject response =
                        gpsClient.send(host, port, code, payload);

                // Never display the secret device key.
                JSONObject safePayload = new JSONObject(payload.toString());
                safePayload.remove("device_key");

                String result =
                        (response.optBoolean("ok", false) ? "ACCEPTED" : "REJECTED") +
                                " - Packet: " + getPacketName(code) + "\n\n" +
                                "Request:\n" + safePayload.toString(2) +
                                "\n\nResponse:\n" + response.toString(2);

                runOnUiThread(() -> {
                    if (destroyed) return;
                    tvResult.setText(result);
                    setButtonsEnabled(true);
                });

            } catch (Exception e) {
                String error;
                if (e instanceof SSLHandshakeException) {
                    error = "TLS trust/hostname failed. Bundle the LAN CA certificate and network security config; do not disable verification.";
                } else if (e instanceof SocketTimeoutException) {
                    error = "Timeout: check LAN listener, firewall, same Wi-Fi/client isolation. If connected, check server reply logs.";
                } else if (e instanceof ConnectException) {
                    error = "Connection refused/unreachable: check server IP, port and LAN listener.";
                } else {
                    error = e.getClass().getSimpleName() + ": " + e.getMessage();
                }

                runOnUiThread(() -> {
                    if (destroyed) return;
                    tvResult.setText(
                            "Error sending " + getPacketName(code) +
                                    ":\n" + error
                    );

                    setButtonsEnabled(true);
                });
            }
        });
    }

    private void setButtonsEnabled(boolean enabled) {
        btnHandshake.setEnabled(enabled);
        btnLocation.setEnabled(enabled);
        btnHeartbeat.setEnabled(enabled);
        btnLogout.setEnabled(enabled);
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, String[] permissions, int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);
        if (requestCode == LAN_PERMISSION_REQUEST && !destroyed) {
            if (grantResults.length > 0 && grantResults[0] == android.content.pm.PackageManager.PERMISSION_GRANTED) {
                sendPacket(pendingCode);
            } else {
                tvResult.setText("Local network permission denied. Allow it in app settings before sending to the PC.");
            }
        }
    }

    @Override
    protected void onDestroy() {
        destroyed = true;
        if (gpsClient != null) {
            executor.execute(() -> {
                try {
                    gpsClient.close();
                } catch (Exception ignored) {
                }
            });
        }

        executor.shutdown();
        super.onDestroy();
    }
}
