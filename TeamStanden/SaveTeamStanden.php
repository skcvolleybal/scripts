<?php
require_once '../libs/SimplePie1.3/autoloader.php';
$teams = [
    ["name" => "SKC DS 1", "competitie" => "DPC", "stand" => 0, "link" => "teams/dames/dames-1"],
    ["name" => "SKC DS 2", "competitie" => "D1E", "stand" => 0, "link" => "teams/dames/dames-2"],
    ["name" => "SKC DS 3", "competitie" => "D2I", "stand" => 0, "link" => "teams/dames/dames-3"],
    ["name" => "SKC DS 4", "competitie" => "D2J", "stand" => 0, "link" => "teams/dames/dames-4"],
    ["name" => "SKC DS 5", "competitie" => "D3M", "stand" => 0, "link" => "teams/dames/dames-5"],
    ["name" => "SKC DS 6", "competitie" => "D3N", "stand" => 0, "link" => "teams/dames/dames-6"],
    ["name" => "SKC DS 7", "competitie" => "D3N", "stand" => 0, "link" => "teams/dames/dames-7"],
    ["name" => "SKC DS 8", "competitie" => "D4G", "stand" => 0, "link" => "teams/dames/dames-8"],
    ["name" => "SKC DS 9", "competitie" => "D4H", "stand" => 0, "link" => "teams/dames/dames-9"],
    ["name" => "SKC DS 10", "competitie" => "D4I", "stand" => 0, "link" => "teams/dames/dames-10"],
    ["name" => "SKC DS 11", "competitie" => "D4I", "stand" => 0, "link" => "teams/dames/dames-11"],
    ["name" => "SKC DS 12", "competitie" => "D4H", "stand" => 0, "link" => "teams/dames/dames-12"],
    ["name" => "SKC DS 13", "competitie" => "D4G", "stand" => 0, "link" => "teams/dames/dames-13"],
    ["name" => "SKC DS 14", "competitie" => "D4H", "stand" => 0, "link" => "teams/dames/dames-14"],
    ["name" => "SKC DS 15", "competitie" => "D4I", "stand" => 0, "link" => "teams/dames/dames-15"],

    ["name" => "SKC HS 1", "competitie" => "HPC", "stand" => 0, "link" => "teams/heren/heren-1"],
    ["name" => "SKC HS 2", "competitie" => "H1E", "stand" => 0, "link" => "teams/heren/heren-2"],
    ["name" => "SKC HS 3", "competitie" => "H2I", "stand" => 0, "link" => "teams/heren/heren-3"],
    ["name" => "SKC HS 4", "competitie" => "H3N", "stand" => 0, "link" => "teams/heren/heren-4"],
    ["name" => "SKC HS 5", "competitie" => "H4G", "stand" => 0, "link" => "teams/heren/heren-5"],
    ["name" => "SKC HS 6", "competitie" => "H4H", "stand" => 0, "link" => "teams/heren/heren-6"],
    ["name" => "SKC HS 7", "competitie" => "H4G", "stand" => 0, "link" => "teams/heren/heren-7"],
    ["name" => "SKC HS 8", "competitie" => "H4H", "stand" => 0, "link" => "teams/heren/heren-7"],
];

$ranking = [];
foreach ($teams as &$team) {
    $feed = new SimplePie();
    $feed->set_feed_url("https://api.nevobo.nl/export/poule/regio-west/" . $team["competitie"] . "/stand.rss");
    $feed->set_timeout(30);
    $feed->set_cache_duration(60 * 60 * 3);
    $feed->init();

    $start = $feed->get_item(0, 1);
    if ($start != null) {
        $items = explode('<br>', $start->get_description());
        $team["numberOfTeams"] = count($items) - 2;
        foreach ($items as $item) {
            if (strpos($item, $team["name"]) !== false) {
                $team["stand"] = substr($item, 0, strpos($item, "."));
            }
        }
    }
}

print_r($teams);

if (count($teams) > 0) {
    file_put_contents("standen.json", json_encode($teams));
}
