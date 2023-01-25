<?php
  echo "
  <style>
    .wrapper { 
      overflow:hidden;
    }

    .wrapper div {
       
    }
    #one {
      float:left; 
      width:29%;
      padding-right: 5px;
    }
    #two { 
      overflow:hidden;
    }

    @media screen and (max-width: 600px) {
       #one { 
        float: none;
        width:auto;
      }
    }
  </style>";

  $teamInfo = Array(
    "Heren 1" => Array("facebook" => "", "klasse" => "Promotieklasse", "trainingstijden" => "Ma 20:00-21:30, Wo 18:30-20:00", "trainer" => "ma: Ron Durge, wo: Nico", "coaches" => "Nico"),
    "Heren 2" => Array("facebook" => "", "klasse" => "1e klasse", "trainingstijden" => "Ma 20:00-21:30, Wo 18:30-20:00", "trainer" => "ma: Ron Durge, wo: Pieter van den Aarsen", "coaches" => "Pieter van den Aarsen"),
    "Heren 3" => Array("facebook" => "", "klasse" => "2e klasse", "trainingstijden" => "Di 21:30-23:00", "trainer" => "Buddy", "coaches" => "Anne Willemsen, Loes van Niekerk"),
    "Heren 4" => Array("facebook" => "", "klasse" => "3e klasse", "trainingstijden" => "Di 21:30-23:00", "trainer" => "Buddy", "coaches" => "Victoria Moek, Lisette Haag"),
    "Heren 5" => Array("facebook" => "", "klasse" => "3e klasse", "trainingstijden" => "Ma 18:30-20:00", "trainer" => "Maurice", "coaches" => "Paula Verhoef, Tara van Leeuwen"),
    "Heren 6" => Array("facebook" => "", "klasse" => "4e klasse", "trainingstijden" => "Ma 18:30-20:00", "trainer" => "Maurice", "coaches" => "Carline Tas"),
    "Heren 7" => Array("facebook" => "", "klasse" => "4e klasse", "trainingstijden" => "Ma 20:00-21:30", "trainer" => "Pieter van W", "coaches" => "Suzanne Wiercx, Fleur Clemens"),
    "Heren 8" => Array("facebook" => "", "klasse" => "4e klasse", "trainingstijden" => "Di 20:00-21:30", "trainer" => "Pieter van W", "coaches" => "Rutger van Bokhorst, Daphne Roggekamp"),
    "Dames 1" => Array("facebook" => "", "klasse" => "Promotieklasse", "trainingstijden" => "Ma 20:00-21:30, Wo 21:30-23:00", "trainer" => "ma: Pim Scherpenzeel, wo: Remco Haasbroek", "coaches" => "Pim Scherpenzeel"),
    "Dames 2" => Array("facebook" => "https://www.facebook.com/SKCDames2/", "klasse" => "1e klasse", "trainingstijden" => "Di 20:00-21:30, Wo 20:00-21:30", "trainer" => "Buddy", "coaches" => "Buddy Doornbos"),
    "Dames 3" => Array("facebook" => "https://www.facebook.com/SKC-Dames-3-1783269245270161/ ", "klasse" => "2e klasse", "trainingstijden" => "Di 20:00-21:30", "trainer" => "Buddy", "coaches" => "Casper van Houten, Emy Theunissen"),
    "Dames 4" => Array("facebook" => "", "klasse" => "2e klasse", "trainingstijden" => "Di 18:30-20:00", "trainer" => "Buddy", "coaches" => "Nynke Kingma, Sanne Verhoeven, Myrna Mertens"),
    "Dames 5" => Array("facebook" => "", "klasse" => "3e klasse", "trainingstijden" => "Di 18:30-20:00", "trainer" => "Buddy", "coaches" => "Stephanie Verbruggen, Myrna Mertens"),
    "Dames 6" => Array("facebook" => "", "klasse" => "3e klasse", "trainingstijden" => "Di 21:30-23:00", "trainer" => "Tim", "coaches" => "Roos Veltman, Lucas Akerboom"),
    "Dames 7" => Array("facebook" => "", "klasse" => "3e klasse", "trainingstijden" => "Di 21:30-23:00", "trainer" => "Tim", "coaches" => "Ralf Werring, Laila Fazli, Eleni Wirahadiraksa"),
    "Dames 8" => Array("facebook" => "", "klasse" => "4e klasse", "trainingstijden" => "Di 20:00-21:30", "trainer" => "Tim", "coaches" => "Michael Dang, Govert Verberg"),
    "Dames 9" => Array("facebook" => "", "klasse" => "4e klasse", "trainingstijden" => "Di 20:00-21:30", "trainer" => "Tim", "coaches" => "Jari van Werkhoven, Pierre-Yves"),
    "Dames 10" => Array("facebook" => "", "klasse" => "4e klasse", "trainingstijden" => "Wo 18:30-20:00", "trainer" => "Buddy", "coaches" => "Jonathan Neuteboom, Sjoerd van Verbeek"),
    "Dames 11" => Array("facebook" => "", "klasse" => "4e klasse", "trainingstijden" => "Wo 18:30-20:00", "trainer" => "Buddy", "coaches" => "Afra Korteweg Maris, Lisan Andersen"),
    "Dames 12" => Array("facebook" => "", "klasse" => "4e klasse", "trainingstijden" => "Ma 18:30-20:00", "trainer" => "Emy", "coaches" => "Yke Rusticus, Timothy Heye"),
    "Dames 13" => Array("facebook" => "", "klasse" => "4e klasse", "trainingstijden" => "Ma 18:30-20:00", "trainer" => "Emy", "coaches" => "Jan Banda, Luca Brunke, Cearan de Boer"),
    "Dames 14" => Array("facebook" => "", "klasse" => "4e klasse", "trainingstijden" => "Di 18:30-20:00", "trainer" => "Pieter van W", "coaches" => "Pien Meijerink, Elisa Hendriks"),
    "Dames 15" => Array("facebook" => "", "klasse" => "4e klasse", "trainingstijden" => "Di 18:30-20:00", "trainer" => "Pieter van W", "coaches" => "Simon Hagen, Yu Kai Tan")
  );

  if (isset($jumi[0])){
    $team = $jumi[0];
  }
  else if (isset($_GET["team"])){
    $team = $_GET["team"];
  }
  else {
    echo "Team is niet gezet";
    return;
  }
  
  $filename = dirname(__FILE__) . "/$team.json";
  
  if (!file_exists($filename)){
    echo "File '$filename' bestaat niet";
    return;
  }
  
  $schedule = json_decode(file_get_contents($filename), true);
  
  $teamName = ucfirst(substr($team, 0, 5) . " " . substr($team, 5));

  $klasse = $klasses[$teamName];

  echo "
  <div class='wrapper'>
    <div class='list-group'>
      <div href='#' class='list-group-item active' style='display: flex;'>
         <h4>" . $teamName . " Teamoverzicht</h4>
      </div>
      <div href='#' class='list-group-item'>Niveau: <a target='_blank' href='http://www.volleybal.nl/competitie/poule/" . $schedule["poule"] . "/regio-west'>" . $teamInfo[$teamName]["klasse"]  . "</a></div>
      <div href='#' class='list-group-item'>Trainer: " . $teamInfo[$teamName]["trainer"]  . "</div>
      <div href='#' class='list-group-item'>Trainingstijden: " . $teamInfo[$teamName]["trainingstijden"]  . "</div>
      <div href='#' class='list-group-item'>Coaches: " . $teamInfo[$teamName]["coaches"]  . "</div>" .
      (!empty($teamInfo[$teamName]["facebook"]) ? "<div href='#' class='list-group-item'><a style='color: #337ab7;' href='" . $teamInfo[$teamName]["facebook"] . "'<i class='fa fa-2x fa-facebook-official'></i></a></div>" : "") . "
    </div>
    <div id='one'>
      <div class='panel panel-primary'>
        <div class='panel-heading'>Stand (Poule " . $schedule["poule"] . ")</div>
        <table class='table'>
          <thead>
            <tr>
              <th>#</th>
              <th>Team</th>
              <th>W</th>
              <th>P</th>
            </tr>
          </thead>
          <tbody>";
            foreach ($schedule["stand"] as $ranking){
              $style = "";
              $number = substr($team, 5);
              if (strtoupper($ranking["team"]) == strtoupper("SKC " . $team[0] . "S " . $number)){
                $style = "font-weight: bold;";
              }
              echo "
              <tr style='$style'>
                <td>" . $ranking["stand"] . "</td>
                <td>" . $ranking["team"] . "</td>
                <td>" . $ranking["wedstrijden"] . "</td>
                <td>" . $ranking["punten"] . "</td>
              </tr>";
            }
            echo "
          </tbody>
        </table>
      </div>
    </div>
    <div id='two'>
      <div class='panel panel-primary'>
        <div class='panel-heading'>Uitslagen</div>
        <table class='table'>
          <thead>
            <tr>
              <th>Wedstrijd</th>
              <th>Uitslag</th>
              <th>Setstanden</th>
            </tr>
          </thead>
          <tbody>";
            if (empty($schedule["uitslagen"])){
              echo "
              <tr>
                <td colspan=3>Nog geen uitslagen</td>
              </tr>";
            }
            else {
              foreach ($schedule["uitslagen"] as $result){
              echo "
              <tr>
                <td>" . $result["teams"] . "</td>
                <td>" . $result["setsVoor"] . " - " . $result["setsTegen"] . "</td>
                <td>" . $result["setstanden"] . "</td>
              </tr>";
              }
            }
            echo "
          </tbody>
        </table>
      </div>
      <div class='panel panel-primary'>
        <div class='panel-heading'>Programma</div>
        <table class='table'>
          <thead>
            <tr>
              <th>Dag</th>
              <th>Tijd</th>
              <th>Wedstrijd</th>
              <th>Locatie</th>
            </tr>
          </thead>
          <tbody>";
            if (empty($schedule["programma"])){
              echo "
              <tr>
                <td colspan=3>Geen programma</td>
              </tr>";
            }
            else {
              foreach ($schedule["programma"] as $nextMatch){
                echo "
                <tr>
                  <td>" . $nextMatch["dag"] . "</td>
                  <td>" . $nextMatch["tijd"] . "</td>
                  <td>" . $nextMatch["teams"] . "</td>
                  <td>" . $nextMatch["locatie"] . "</td>
                </tr>";
              }
            }
            echo "
          </tbody>
        </table>
      </div>
    </div>
  </div>";
  
    
  
  
  
