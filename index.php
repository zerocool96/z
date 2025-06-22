<?php
// Settings from URL
$mac = $_GET['mac'] ?? '00:1A:79:87:9A:C5';
$portal = $_GET['portal'] ?? 'http://4k.spicetv.cc/stalker_portal';

// Format
$portal = rtrim($portal, '/');

// Middleware emulation
$headers = [
    'User-Agent: Mozilla/5.0',
    "X-User-Agent: Model: MAG270; Link: Ethernet",
    "Referer: $portal/c/",
    "Cookie: mac=$mac; stb_lang=en; timezone=GMT",
    "Authorization: Bearer null",
];

// Auth
function stalker_request($url, $headers, $post = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    }
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Step 1: Handshake
$handshake = stalker_request("$portal/server/load.php?type=stb&action=handshake&token=null&prehash=false&JsHttpRequest=1-xml", $headers);
$token = $handshake['js']['token'] ?? '';
$headers[] = "Authorization: Bearer $token";

// Step 2: Get profile (to generate session)
stalker_request("$portal/server/load.php?type=stb&action=get_profile&JsHttpRequest=1-xml", $headers);

// Step 3: Get channels
$channels = stalker_request("$portal/server/load.php?type=itv&action=get_all_channels&JsHttpRequest=1-xml", $headers);

// Output M3U
header('Content-Type: audio/x-mpegurl');
echo "#EXTM3U\n";

foreach ($channels['js']['data'] as $ch) {
    $name = $ch['name'];
    $cmd = $ch['cmd'];
    // Full stream URL
    $stream_url = "$portal/$cmd";
    echo "#EXTINF:-1 tvg-id=\"\" tvg-name=\"$name\" group-title=\"IPTV\",$name\n$stream_url\n";
}
