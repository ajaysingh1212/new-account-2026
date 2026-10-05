package com.gpstester.app;

import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.io.IOException;
import java.net.InetSocketAddress;
import java.nio.ByteBuffer;
import java.nio.ByteOrder;
import java.nio.charset.StandardCharsets;
import java.util.Arrays;
import java.util.Locale;
import java.util.zip.CRC32;

import javax.net.ssl.SSLParameters;
import javax.net.ssl.SSLSocket;
import javax.net.ssl.SSLSocketFactory;

public final class MobileGpsProtocol {

    private MobileGpsProtocol() {}

    public static String encode(int code, JSONObject payload)
            throws Exception {

        byte[] json = payload.toString()
                .getBytes(StandardCharsets.UTF_8);

        if (code < 1 || code > 4) throw new IllegalArgumentException("Unknown packet code");
        if (json.length > 16384) {
            throw new IllegalArgumentException("JSON payload too large");
        }

        ByteBuffer buffer = ByteBuffer
                .allocate(8 + json.length + 4)
                .order(ByteOrder.BIG_ENDIAN);

        buffer.put((byte) 0x54);
        buffer.put((byte) 0x47);
        buffer.put((byte) 0x01);
        buffer.put((byte) code);
        buffer.putInt(json.length);
        buffer.put(json);

        CRC32 crc = new CRC32();
        crc.update(buffer.array(), 0, 8 + json.length);
        buffer.putInt((int) crc.getValue());

        StringBuilder hex = new StringBuilder();

        for (byte b : buffer.array()) {
            hex.append(String.format(
                    Locale.US, "%02X", b & 0xFF));
        }

        return hex + "\n";
    }

    public static JSONObject decode(String line) throws Exception {

        String hex = line.trim();

        if (hex.length() < 24 || hex.length() > (16384 + 12) * 2 || hex.length() % 2 != 0 || !hex.matches("[0-9a-fA-F]+")) {
            throw new IllegalArgumentException("Invalid HEX length");
        }

        byte[] frame = new byte[hex.length() / 2];

        for (int i = 0; i < frame.length; i++) {
            frame[i] = (byte) Integer.parseInt(
                    hex.substring(i * 2, i * 2 + 2), 16);
        }

        if (frame[0] != 0x54
                || frame[1] != 0x47
                || frame[2] != 0x01) {
            throw new IllegalArgumentException("Invalid TG1 header");
        }

        int code = frame[3] & 0xFF;

        int jsonLength = ByteBuffer.wrap(frame, 4, 4)
                .order(ByteOrder.BIG_ENDIAN)
                .getInt();

        if (jsonLength < 0
                || jsonLength > 16384
                || frame.length != jsonLength + 12) {
            throw new IllegalArgumentException("Invalid frame length");
        }

        CRC32 crc = new CRC32();
        crc.update(frame, 0, 8 + jsonLength);

        long receivedCrc = Integer.toUnsignedLong(
                ByteBuffer.wrap(frame, 8 + jsonLength, 4)
                        .order(ByteOrder.BIG_ENDIAN)
                        .getInt());

        if (crc.getValue() != receivedCrc) {
            throw new IllegalArgumentException("CRC32 mismatch");
        }

        String json = new String(
                Arrays.copyOfRange(frame, 8, 8 + jsonLength),
                StandardCharsets.UTF_8);

        JSONObject result = new JSONObject(json);

        result.put("_response_code",
                String.format(Locale.US, "%02X", code));

        return result;
    }

    public static class Client implements AutoCloseable {

        private SSLSocket socket;
        private BufferedReader reader;
        private BufferedWriter writer;

        private String connectedHost;
        private int connectedPort = -1;

        private boolean isConnected() {
            return socket != null
                    && socket.isConnected()
                    && !socket.isClosed()
                    && !socket.isInputShutdown()
                    && !socket.isOutputShutdown();
        }

        private void connect(String host, int port)
                throws Exception {

            close();

            SSLSocket newSocket = null;

            try {
                SSLSocketFactory factory =
                        (SSLSocketFactory)
                                SSLSocketFactory.getDefault();

                newSocket = (SSLSocket) factory.createSocket();

                newSocket.connect(
                        new InetSocketAddress(host, port), 10000);

                newSocket.setSoTimeout(15000);

                SSLParameters params =
                        newSocket.getSSLParameters();

                params.setEndpointIdentificationAlgorithm("HTTPS");
                newSocket.setSSLParameters(params);

                newSocket.startHandshake();

                socket = newSocket;

                reader = new BufferedReader(
                        new InputStreamReader(
                                socket.getInputStream(),
                                StandardCharsets.US_ASCII));

                writer = new BufferedWriter(
                        new OutputStreamWriter(
                                socket.getOutputStream(),
                                StandardCharsets.US_ASCII));

                connectedHost = host;
                connectedPort = port;

            } catch (Exception e) {
                if (newSocket != null) {
                    try {
                        newSocket.close();
                    } catch (Exception ignored) {}
                }

                close();
                throw e;
            }
        }

        public synchronized JSONObject send(
                String host, int port, int code, JSONObject payload
        ) throws Exception {
            try {
                return sendOnce(host, port, code, payload);
            } catch (IOException e) {
                if (e instanceof javax.net.ssl.SSLException) throw e;
                // Reuse packet_id/payload so a lost ACK cannot create a second history point.
                close();
                return sendOnce(host, port, code, payload);
            }
        }

        private JSONObject sendOnce(
                String host,
                int port,
                int code,
                JSONObject payload
        ) throws Exception {

            if (!isConnected()
                    || !host.equals(connectedHost)
                    || port != connectedPort) {
                connect(host, port);
            }

            try {
                writer.write(encode(code, payload));
                writer.flush();

                StringBuilder response = new StringBuilder();
                int next;
                while ((next = reader.read()) != -1 && next != '\n') {
                    if (response.length() >= (16384 + 12) * 2 + 1) {
                        throw new IllegalArgumentException("Server response too large");
                    }
                    response.append((char) next);
                }
                String responseLine = next == -1 ? null : response.toString();

                if (responseLine == null) {
                    throw new IOException(
                            "Server closed the connection");
                }

                JSONObject reply = decode(responseLine);
                String responseCode = reply.optString("_response_code");
                if (!responseCode.equals("80") && !responseCode.equals("81")) {
                    throw new IllegalArgumentException("Unexpected server message code");
                }
                if (responseCode.equals("80") &&
                        !payload.getString("packet_id").equals(reply.optString("packet_id"))) {
                    throw new IllegalArgumentException("ACK packet_id mismatch");
                }
                return reply;

            } catch (Exception e) {
                close();
                throw e;
            }
        }

        @Override
        public synchronized void close() {
            if (socket != null) {
                try {
                    socket.close();
                } catch (Exception ignored) {}
            }

            socket = null;
            reader = null;
            writer = null;
            connectedHost = null;
            connectedPort = -1;
        }
    }
}
