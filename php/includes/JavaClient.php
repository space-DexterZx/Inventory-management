<?php
require_once __DIR__ . '/config.php';

function java_post(string $path, array $data): array {
    $url = JAVA_API . $path;
    $json = json_encode($data);
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $json,
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        return ['ok' => false, 'error' => 'Java service not running. Start it with ./run.sh'];
    }
    $res = json_decode($raw, true);
    return is_array($res) ? $res : ['ok' => false, 'error' => 'Bad response from Java service'];
}

function java_get(string $path): array {
    $url = JAVA_API . $path;
    $raw = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 5]]));
    if ($raw === false) {
        return ['ok' => false, 'error' => 'Java service not running'];
    }
    $res = json_decode($raw, true);
    return is_array($res) ? $res : ['ok' => false, 'error' => 'Bad response'];
}