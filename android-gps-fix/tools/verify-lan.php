<?php

require 'D:/tracking/tracking2026/vendor/autoload.php';

use App\Services\Gps\HexPacketCodec;
use Illuminate\Support\Str;

$key = getenv('GPS_DEVICE_KEY') ?: throw new RuntimeException('Set GPS_DEVICE_KEY; it is never logged.');
$context = stream_context_create(['ssl' => [
    'verify_peer' => true, 'verify_peer_name' => true,
    'peer_name' => '192.168.1.60',
    'cafile' => __DIR__.'/../app/src/main/res/raw/gps_lan_ca.pem',
]]);
$socket = stream_socket_client('tls://192.168.1.60:9000', $errno, $error, 10, STREAM_CLIENT_CONNECT, $context);
if (! $socket) throw new RuntimeException($error);
stream_set_timeout($socket, 15);
$codec = new HexPacketCodec;
$packet = ['user_id' => 13, 'license_number' => 'SIM-PATNA-AJAY-001',
    'imei' => '353243280279794', 'device_key' => $key];
try {
    foreach ([1, 2, 3] as $code) {
        $payload = [...$packet, 'packet_id' => (string) Str::uuid(), 'recorded_at' => gmdate('c')];
        if ($code === 2) {
            $payload = [...$payload, 'source_type' => 'android',
                'latitude' => 25.5941, 'longitude' => 85.1376,
                'provider' => 'manual', 'is_mock' => true];
        }
        $frame = $codec->encode($code, $payload);
        $offset = 0;
        while ($offset < strlen($frame)) {
            $written = fwrite($socket, substr($frame, $offset, 31));
            if (! $written) throw new RuntimeException('Socket write failed.');
            $offset += $written;
        }
        $line = fgets($socket, HexPacketCodec::MAX_HEX_LENGTH + 3);
        if ($line === false) throw new RuntimeException('No ACK.');
        $reply = $codec->decode($line);
        if ($reply['code'] !== 0x80 || ! ($reply['payload']['ok'] ?? false)
            || ($reply['payload']['packet_id'] ?? null) !== $payload['packet_id']) {
            throw new RuntimeException('Rejected: '.json_encode($reply));
        }
        echo json_encode(['request_code' => sprintf('%02X', $code), 'tls_verified' => true,
            'reply' => $reply['payload']], JSON_PRETTY_PRINT).PHP_EOL;
        if ($code === 2) {
            $app = require 'D:/tracking/tracking2026/bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            $row = App\Models\GpsLocation::where('user_id', 13)
                ->whereHas('deviceSession', fn ($query) => $query->where('session_id', $packet['imei']))
                ->latest('recorded_at')->first();
            echo json_encode(['latest_history_id' => $row?->id, 'user_id' => $row?->user_id,
                'latitude' => $row?->latitude, 'longitude' => $row?->longitude,
                'provider' => $row?->provider, 'recorded_at' => $row?->recorded_at?->toIso8601String()]).PHP_EOL;
        }
    }
} finally {
    fclose($socket);
}
