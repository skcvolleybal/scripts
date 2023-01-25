<STYLE TYPE="text/css">
.coach-table {
  width:100%;
}
.coach-results td{
  border: 1px solid;
  min-width: 30px;
}
.green {
  color: black;
}
.orange {
  color: orange;
}
.red {
  color: red;
}
.green-result {
  color: green;
}
.orange-result {
  color: orange;
}
.red-result {
  color: red;
}



</STYLE>
<?php

require_once 'libs/SimplePie1.3/autoloader.php';
$teams = ["---",
    "Dames 1",
    "Dames 2",
    "Dames 3",
    "Dames 4",
    "Dames 5",
    "Dames 6",
    "Dames 7",
    "Dames 8",
    "Dames 9",
    "Dames 10",
    "Dames 11",
    "Dames 12",
    "Dames 13",
    "Dames 14",
    "Dames 15",
    "Heren 1",
    "Heren 2",
    "Heren 3",
    "Heren 4",
    "Heren 5",
    "Heren 6",
    "Heren 7",
    "Heren 8",
    "Heren 9",
    "Kratos DS 1"];
$date_format = "d M Y";
//print_r($_POST);
function return_url($team)
{
    if ($team == "---") {
        return;
    }
    if ($team == "Kratos DS 1"){
        return "https://api.nevobo.nl/export/team/ckn1s6f/dames/1/programma.rss";
    }

    $gender = strtolower(substr($team, 0, 5));
    $teamNumber = strtolower(trim(substr($team, 6, 2)));

    return "https://api.nevobo.nl/export/team/CKL9R53/$gender/$teamNumber/programma.rss";
}

function GetSchedule($team)
{
    $date_format = "d M Y";
    $feed = new SimplePie();
    $feed->set_feed_url(return_url($team));
    $feed->enable_order_by_date(false);
    $feed->init();
    $feed->handle_content_type();
    for ($i = 0; $i < $feed->get_item_quantity(); $i++) {
        $time = $feed->get_item($i)->get_date("H:s");
        $match = trim(substr($feed->get_item($i)->get_title(), 14));
        $home = strpos($feed->get_item($i)->get_description(), "Speellocatie: Universitair SC, Einsteinweg 6") ? true : false;
        $match = [
            "time" => $feed->get_item($i)->get_date("H:s"),
            "match" => $match,
            "home" => $home,
        ];
        $date = $feed->get_item($i)->get_date($date_format);

        if (!empty($date)) {
            $matches[$date] = $match;
        }
    }

    $year = $feed->get_item()->get_date("Y");
    return $matches;
}

echo '<form name="myform" action="" method="POST">';

if (isset($_POST['team1']) || isset($_POST['team2']) || isset($_POST['team3'])) {
    $team1 = $_POST['team1'];
    $team2 = $_POST['team2'];
    $team3 = $_POST['team3'];

    if ($team1 != "---") {
        $schedule1 = GetSchedule($team1);
    }
    if ($team2 != "---") {
        $schedule2 = GetSchedule($team2);
    }
    if ($team3 != "---") {
        $schedule3 = GetSchedule($team3);
    }
    //print_r($schedule1);

    $currentMonth = date('F');
    $isMatchInfirstSixMonths = array_key_exists($month, ['January', 'February', 'March', 'April', 'May', 'June']);
    $currentYear = date('Y');
    if (date('n') < 7) {
        if ($isMatchInfirstSixMonths) {
            $year = $currentYear;
        } else {
            $year = $currentYear - 1;
        }
    } else {
        if ($isMatchInfirstSixMonths) {
            $year = $currentYear + 1;
        } else {
            $year = $currentYear;
        }
    }

    $date = date($date_format, strtotime('1 september ' . $year));
    $end_date = date($date_format, strtotime('1 september ' . ($year + 1)));

    if ($team3 != $teams[0]) {
        echo "<table class=coach-table><tr>
              <td><b>$team1</b></td>
              <td><b>Locatie</b></td>
              <td><b>Tijd</b></td>
              <td><b>Datum</b></td>
              <td><b>Tijd</b></td>
              <td><b>Locatie</b></td>
              <td><b>$team2</b></td>
              <td width=10px></td>
              <td><b>Tijd</b></td>
              <td><b>Locatie</b></td>
              <td><b>$team3</b></td>
            </tr>";
    } else {
        echo "<table class=coach-table><tr>
              <td><b>$team1</b></td>
              <td><b>Locatie</b></td>
              <td><b>Tijd</b></td>
              <td><b>Datum</b></td>
              <td><b>Tijd</b></td>
              <td><b>Locatie</b></td>
              <td><b>$team2</b></td>
            </tr>";
    }
    $attending_teams = 0;
    $green = 0;
    $orange = 0;
    $red = 0;

    // echo "$date en $end_date";
    while (strtotime($date) <= strtotime($end_date)) { //echo $date . "<br>";
        if (isset($schedule1[$date])) {
            $time1 = $schedule1[$date]['time'];
            $location1 = $schedule1[$date]['home'] ? "Thuis" : "Uit";
            $match1 = $schedule1[$date]['match'];
            $attending_teams++;
            $team1_attending = true;
        } else {
            $time1 = "";
            $location1 = "";
            $match1 = "";
            $team1_attending = false;
        }

        if (isset($schedule2[$date])) {
            $time2 = $schedule2[$date]['time'];
            $location2 = $schedule2[$date]['home'] ? "Thuis" : "Uit";
            $match2 = $schedule2[$date]['match'];
            $attending_teams++;
            $team2_attending = true;
        } else {
            $time2 = "";
            $location2 = "";
            $match2 = "";
            $team2_attending = false;
        }

        if (isset($schedule3) && isset($schedule3[$date])) {
            $time3 = $schedule3[$date]['time'];
            $location3 = $schedule3[$date]['home'] ? "Thuis" : "Uit";
            $match3 = $schedule3[$date]['match'];
            $attending_teams++;
            $team3_attending = true;
        } else {
            $time3 = "";
            $location3 = "";
            $match3 = "";
            $team3_attending = false;
        }

        // check the color
        if ($attending_teams > 0) {
            $color = "green";
            if (isset($schedule3)) {
                if ($team1_attending) {
                    $green++;
                    if ($team2_attending && $team3_attending) {
                        if ($location1 == "Thuis" && $location2 == "Thuis" && abs($time1 - $time2) >= 2) {
                            $color = "orange";
                            $green--;
                            $orange++;
                        } else if ($location1 == "Thuis" && $location3 == "Thuis" && abs($time1 - $time3) >= 2) {
                            $color = "orange";
                            $green--;
                            $orange++;
                        } else if (abs($time1 - $time2) >= 1) {
                            $color = "orange";
                            $green--;
                            $orange++;
                        } else if (abs($time1 - $time3) >= 1) {
                            $color = "orange";
                            $green--;
                            $orange++;
                        } else {
                            $color = "red";
                            $green--;
                            $red++;
                        }
                    }
                }
            } else {
                if ($team1_attending && $team2_attending) {
                    if ($location1 == "Thuis" && $location2 == "Thuis" && abs($time1 - $time2) >= 2) {
                        $color = "green";
                        $green++;
                    } else if (abs($time1 - $time2) >= 2) {
                        $color = "orange";
                        $orange++;
                    } else {
                        $color = "red";
                        $red++;
                    }
                } else if ($team1_attending) {
                    $green++;
                }
            }

            // print
            if ($team3 != $teams[0]) {
                echo "<tr>
                  <td class='$color'>$match1</td>
                  <td class='$color'>$location1</td>
                  <td class='$color'>$time1</td>
                  <td class='$color'>$date</td>
                  <td class='$color'>$time2</td>
                  <td class='$color'>$location2</td>
                  <td class='$color'>$match2</td>
                  <td class='$color' width=20px></td>
                  <td class='$color'>$time3</td>
                  <td class='$color'>$location3</td>
                  <td class='$color'>$match3</td>
                </tr>";
            } else {
                echo "<tr>
                <td class='$color'>$match1</td>
                <td class='$color'>$location1</td>
                <td class='$color'>$time1</td>
                <td class='$color'>$date</td>
                <td class='$color'>$time2</td>
                <td class='$color'>$location2</td>
                <td class='$color'>$match2</td>
              </tr>";
            }
        }

        $attending_teams = 0;
        $date = date($date_format, strtotime("+1 day", strtotime($date)));
    }
    echo '</table>';
    echo "<p>Uiteindelijke score (aantal wedstrijden):
          <table class=coach-results>
            <tr>
              <td class='green-result'>" . $green . "</td>
              <td class='orange-result'>" . $orange . "</td>
              <td class='red-result'>" . $red . "</td>
            </tr>
          </table></p>";
}

echo '<table>
          <tr>
            <td>Coach team:</td>
            <td>
              <select name="team1" onchange="this.form.submit();">';

foreach ($teams as $team) {
    echo '<option value="' . $team . '"';
    if (isset($_POST['team1']) && $_POST['team1'] == $team) {
        echo ' selected';
    }
    echo '>' . $team . '</option>';
}

echo '      </select>
            </td>
          </tr>
          <tr>
            <td>Coach 1:</td>
            <td>
              <select name="team2" onchange="this.form.submit();">';

foreach ($teams as $team) {
    echo '<option value="' . $team . '"';
    if (isset($_POST['team2']) && $_POST['team2'] == $team) {
        echo ' selected';
    }
    echo '>' . $team . '</option>';
}

echo '      </select>
            </td>
          </tr>
          <tr>
            <td>Coach 2:</td>
            <td>
              <select name="team3" onchange="this.form.submit();">';

foreach ($teams as $team) {
    echo "<option value='$team'";
    if (isset($_POST['team3']) && $_POST['team3'] == $team) {
        echo " selected";
    }
    echo ">$team</option>";
}

echo '      </select>
            </td>
          </tr>
        </table>
      </form>';
