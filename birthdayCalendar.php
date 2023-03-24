<?php

// BUG (14 dec 2022): als leden hun geboortedatum in Joomla niet is ingevuld, laat dit script onterecht hun verjaardag zien
// Staat op backlog 
// Tijdelijk offline: 


// Offline
// Test


  //  error_reporting(E_ALL);
  //  ini_set("display_errors", 1);

  require 'vendor/autoload.php';
  $env = json_decode(file_get_contents("../../env.json"));

  \Sentry\init(['dsn' => 'https://45df5d88f1084fcd96c8ae9fa7db50c7@o4504883122143232.ingest.sentry.io/4504883124240384',
  'environment' => $env->Environment ]);

   setlocale(LC_TIME, 'NL_nl');

   $query = "SELECT U.name, 
                    CASE 
                      WHEN MONTH(CURDATE()) < CAST(SUBSTR(cb_geboortedatum, 4, 2) as INTEGER) or 
                          (MONTH(CURDATE()) = CAST(SUBSTR(cb_geboortedatum, 4, 2) as INTEGER) and DAY(CURDATE()) <= CAST(SUBSTR(cb_geboortedatum, 1, 2) as INTEGER))
                      THEN 
                        CONCAT(
                          YEAR(CURDATE()), 
                          SUBSTR(cb_geboortedatum, 4, 2),
                          SUBSTR(cb_geboortedatum, 1, 2)
                        )
                      ELSE 
                        CONCAT(
                          YEAR(CURDATE()) + 1, 
                          SUBSTR(cb_geboortedatum, 4, 2),
                          SUBSTR(cb_geboortedatum, 1, 2)
                        )
                    END as date,
                    STR_TO_DATE(
                      CONCAT(
                        YEAR(CURDATE()), 
                        SUBSTR(cb_geboortedatum, 4, 2), 
                        SUBSTR(cb_geboortedatum, 1, 2)
                      ), '%Y%m%d'
                    ) = CURDATE() as is_today,
                    YEAR(CURDATE()) - SUBSTR(cb_geboortedatum, 7, 4) as age
             FROM   J3_comprofiler C
             INNER JOIN J3_users U on U.id = C.user_id
             INNER JOIN J3_user_usergroup_map M on U.id = M.user_id
             INNER JOIN (
               SELECT * 
               FROM J3_usergroups 
               WHERE parent_id = 12
             ) G on M.group_id = G.id
             WHERE  STR_TO_DATE(
                      CONCAT(
                        YEAR(Curdate()), 
                        SUBSTR(cb_geboortedatum, 4, 2), 
                        SUBSTR(cb_geboortedatum, 1, 2)
                      ), '%Y%m%d') BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH) 
             OR 
                    STR_TO_DATE(
                      CONCAT(
                        YEAR(CURDATE()) + 1, 
                        SUBSTR(cb_geboortedatum, 4, 2), 
                        SUBSTR(cb_geboortedatum, 1, 2)
                      ), '%Y%m%d') BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH) 
             ORDER  BY CASE 
                         WHEN MONTH(CURDATE()) < CAST(SUBSTR(cb_geboortedatum, 4, 2) as INTEGER) or 
                             (MONTH(CURDATE()) = CAST(SUBSTR(cb_geboortedatum, 4, 2) as INTEGER) and DAY(CURDATE()) <= CAST(SUBSTR(cb_geboortedatum, 1, 2) as INTEGER))
                         THEN 
                           CONCAT(
                             YEAR(CURDATE()), 
                             SUBSTR(cb_geboortedatum, 4, 2),
                             SUBSTR(cb_geboortedatum, 1, 2)
                           )
                         ELSE 
                           CONCAT(
                             YEAR(CURDATE()) + 1, 
                             SUBSTR(cb_geboortedatum, 4, 2),
                             SUBSTR(cb_geboortedatum, 1, 2)
                           )
                       END";
   
   require_once(dirname(__FILE__) . "/../configuration.php");

   $jConfig = new JConfig();
   $host = $jConfig->host;
   $db = $jConfig->db;
   $user = $jConfig->user;
   $password = $jConfig->password;
   $dbc = new PDO("mysql:host=$host;dbname=$db", $user, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));

   $stmt = $dbc->prepare($query);
   $stmt->execute();
   $birthDays = $stmt->fetchAll();

   $startOfTable = "
   ";
   $birthDayText = "";
   foreach ($birthDays as $birthDay){
      $img = $birthDay["is_today"] == "1" ? "<i class='fa fa-birthday-cake'></i> " : "";
      $birthdayDate = strftime('%e %h', strtotime($birthDay["date"]));
      $birthDayText .= "<div style='font-size: 10px;'>$birthdayDate: <b>" . $img . $birthDay["name"] . "</b> (" . $birthDay["age"] . ")" . "</div>";
   }
   if ($birthDayText == ""){
      $birthDayText = "Nog niemand jarig...";
   }
   echo "
   <div class='panel panel-primary'> 
      <div class='panel-heading'> 
         <h4 class='panel-title'>Verjaardagen</h4>
      </div> 
      <div class='panel-body'>
         $birthDayText
      </div>
   </div>";
?>