<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

if (isset($_GET['q'])) {
    $query = urlencode($_GET['q']);
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$query}&addressdetails=1&limit=5&countrycodes=ID";
    $opts = [
        "http" => [
            "header" => "User-Agent: LaporanJalanApp/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    echo file_get_contents($url, false, $context);
}
elseif (isset($_GET['lat']) && isset($_GET['lon'])) {
    $lat = $_GET['lat'];
    $lon = $_GET['lon'];
    $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat={$lat}&lon={$lon}";
    $opts = [
        "http" => [
            "header" => "User-Agent: LaporanJalanApp/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    echo file_get_contents($url, false, $context);
}
else {
    echo json_encode(["error" => "Parameter tidak valid"]);
}
