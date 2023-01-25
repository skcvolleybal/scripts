<?php
  $teams = json_decode(file_get_contents(dirname(__FILE__) . "/standen.json"), true);
  echo "
    <div class='panel panel-default'>
      <table class='table'>
        <thead>
          <tr>
            <th>Team</th>
            <th>Stand</th>
          </tr>
        </thead>
        <tbody>";
          foreach ($teams as $team){
            echo "<tr><td><a href='index.php/" . $team["link"] . "'>" . $team["name"] . "</a></td><td>" . $team["stand"] . "e</td></tr>";
          }
  echo "
        </tbody>
      </table>
    </div>";
