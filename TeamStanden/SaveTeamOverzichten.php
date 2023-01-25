<?php
  require_once('../libs/SimplePie1.3/autoloader.php');

  $teams = [
    ["poule" => "HPC", "gender" => "heren", "number" => 1],
    ["poule" => "H1E", "gender" => "heren", "number" => 2],
    ["poule" => "H2I", "gender" => "heren", "number" => 3],
    ["poule" => "H3M", "gender" => "heren", "number" => 4],
    ["poule" => "H3M", "gender" => "heren", "number" => 5],
    ["poule" => "H4G", "gender" => "heren", "number" => 6],
    ["poule" => "H4H", "gender" => "heren", "number" => 7],
    ["poule" => "H4G", "gender" => "heren", "number" => 8],
    ["poule" => "DPC", "gender" => "dames", "number" => 1],
    ["poule" => "D1E", "gender" => "dames", "number" => 2],
    ["poule" => "D2J", "gender" => "dames", "number" => 3],
    ["poule" => "D2I", "gender" => "dames", "number" => 4],
    ["poule" => "D3M", "gender" => "dames", "number" => 5],
    ["poule" => "D3N", "gender" => "dames", "number" => 6],
    ["poule" => "D3M", "gender" => "dames", "number" => 7],
    ["poule" => "D4G", "gender" => "dames", "number" => 8],
    ["poule" => "D4I", "gender" => "dames", "number" => 9],
    ["poule" => "D4H", "gender" => "dames", "number" => 10],
    ["poule" => "D4H", "gender" => "dames", "number" => 11],
    ["poule" => "D4G", "gender" => "dames", "number" => 12],
    ["poule" => "D4G", "gender" => "dames", "number" => 13],
    ["poule" => "D4G", "gender" => "dames", "number" => 14],
    ["poule" => "D4H", "gender" => "dames", "number" => 15]
  ];
  
  $teamRegex = "([0-9a-zA-Z '\/-]+)";
  
  function GetRanking($rankingUrl){
    global $teamRegex;
    $regex = "/(\d+)\. $teamRegex, wedstr: (\d+), punten: (\d+)/";
    
    $feed = new SimplePie();
    $feed->set_feed_url($rankingUrl);
    $feed->set_timeout(30);
    $feed->set_cache_duration(60);
    $feed->enable_order_by_date(false);
    $feed->init();
    
    $start = $feed->get_item(0, 1);    
    $teams = explode('<br>', $start->get_description());
    $ranking = [];
    foreach ($teams as $team){
      if ($team == "# Team" || empty($team)) continue;
      if (preg_match_all($regex, $team, $result, PREG_SET_ORDER) !== false){
        $ranking[] = [
          "stand" => $result[0][1],
          "team" => $result[0][2],
          "wedstrijden" => $result[0][3],
          "punten" => $result[0][4]
        ];
      }
      else {
        echo "GetRanking: Kon tekst niet parsen met regex: " . $team . "<br />";
      }
    }
    
    
    return $ranking;
  }
  
  function GetResults($resultsUrl, $numberOfMatches){
    global $teamRegex;
    $regex = "/Wedstrijd: $teamRegex - $teamRegex, Uitslag: ([0-9\.]+)-([0-9\.]+), Setstanden: ([0-9, -]+)/";
    
    $feed = new SimplePie();
    $feed->set_feed_url($resultsUrl);
    $feed->set_timeout(30);
    $feed->set_cache_duration(60);
    $feed->enable_order_by_date(false);
    $feed->init();
    
    $results = [];
    
    $items = $feed->get_items(0, $numberOfMatches);
    foreach ($items as $item){
      if (preg_match_all($regex, $item->get_description(), $result, PREG_SET_ORDER) !== false){
        $results[] = [
          "teams" => $result[0][1] . " - " . $result[0][2],
          "setsVoor" => intval($result[0][3]),
          "setsTegen" => intval($result[0][4]),
          "setstanden" => $result[0][5]
        ];
      }
      else {
        echo "GetResults: Kon tekst niet parsen met regex: " . $team . "<br />";
      }
    }
    return $results;
  }
  
  function GetNextMatches($nextMatchesUrl, $numberOfMatches){
    global $teamRegex;
    $titleRegex = "/([0-9]+) ([a-z.]+) ([0-9]+:[0-9]+): $teamRegex - $teamRegex/";
    $descriptionRegex = "/Wedstrijd: ([a-zA-Z0-9]+)\s*([a-zA-Z0-9]+), Datum: ([a-zA-Z]+) ([0-9]+) ([a-zA-Z]+), ([0-9]+:[0-9]+), Speellocatie: ([a-zA-Z0-9'\- ]+), ([a-zA-Z0-9' ]+), ([a-zA-Z0-9]+)\s*([a-zA-Z0-9' ]+)/";
    
    $feed = new SimplePie();
    $feed->set_feed_url($nextMatchesUrl);
    $feed->set_timeout(15);
    $feed->set_cache_duration(60);
    $feed->enable_order_by_date(false);
    $feed->init();
    
    $nextMatches = [];
    
    $items = $feed->get_items(0, $numberOfMatches);
    foreach ($items as $item){
      if (preg_match_all($titleRegex, $item->get_title(), $title, PREG_SET_ORDER) !== false){
        if (preg_match_all($descriptionRegex, $item->get_description(), $description, PREG_SET_ORDER) !== false){
          $nextMatches[] = [
            "tijd" => $title[0][3],
            "teams" => $title[0][4] . " - " . $title[0][5],
            "dag" => ucwords($description[0][3] . " " . $description[0][4] . " " . $description[0][5]),
            "locatie" => ucwords($description[0][7] . ", " . $description[0][8] . ", " . $description[0][9]. " " . $description[0][10]),
          ];
        }
        else {
          echo "Nextmactches: Kon description niet parsen met regex: " . $team . "<br />";
        }
      }
      else {
        echo "Nextmactches: Kon title niet parsen met regex: " . $team . "<br />";
      }
    }
    
    return $nextMatches;
  }
  
  
  foreach ($teams as $team){
    $nextMatchesUrl = "https://api.nevobo.nl/export/team/CKL9R53/" . $team["gender"] . "/" . $team["number"] . "/programma.rss";
    $resultsUrl = "https://api.nevobo.nl/export/team/CKL9R53/" . $team["gender"] . "/" . $team["number"] . "/resultaten.rss";
    $rankingUrl = "https://api.nevobo.nl/export/poule/regio-west/" . $team["poule"] . "/stand.rss";
    
    $ranking = GetRanking($rankingUrl);
    $results = GetResults($resultsUrl, 3);
    $nextMatches = GetNextMatches($nextMatchesUrl, 3);
    
    $result = [
      "poule" => $team["poule"],
      "stand" => $ranking,
      "uitslagen" => $results,
      "programma" => $nextMatches
    ];
    
    print_r($result);
    
    if (count($result) > 0){
      file_put_contents($team["gender"]. $team["number"] . ".json", json_encode($result));
    }
  }
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
