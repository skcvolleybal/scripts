<?php
include 'PhpCache.php';

function get_content($URL)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $URL);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . "/cacert.pem");
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

$cache = new PhpCache();

$requestUri = $_SERVER['REQUEST_URI'];
$cacheKey = md5($requestUri);
if ($cache->DoesCacheKeyExist($cacheKey)) {
    $response = $cache->ReadFromCache($cacheKey);
    exit($response);
}

$queryString = $_SERVER['QUERY_STRING'];
parse_str($queryString, $queryVariables);


$searchString = $queryVariables['q'];

$searchUrl = "https://www.volleybal.nl/xhr/search.json?type=competition&q=";
$url = $searchUrl . urlencode($searchString);
$httpResponse = get_content($url);
$content = json_decode($httpResponse);

$items = [];
foreach ($content->results->competition as $item) {
    $description = $item->description;
    $startsWithTeam = '/^Team - /';
    if (preg_match($startsWithTeam, $description)) {
        $name = $item->title;
        $value = str_replace("/competitie/team/", "", $item->url);
        $items[] = [
            "name" => $name,
            "value" => $value,
        ];
    }
}

$response = [
    "query" => $searchString,
    "succes" => true,
    "results" => $items,
];

$responseText = json_encode($response);
$cache->WriteToCache($cacheKey, $responseText);
exit($responseText);
