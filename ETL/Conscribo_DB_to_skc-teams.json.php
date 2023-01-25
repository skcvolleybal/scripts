<?php

/**
 * 
 *  Transforms the Conscribo MYSQL staging table to skc-teams.json
 * 
 * @category   CategoryName
 * @package    PackageName
 * @author     kmsch
 * @copyright  2022 kmsch
 */

//

// 0 Get environment variables

echo _DIR_;
die();

$env = json_decode(file_get_contents("../../../env.json"));

 
// To-do: remove from this script
$host = $env->Staging_DB_Conscribo->host;
$user = $env->Staging_DB_Conscribo->username;
$password = $env->Staging_DB_Conscribo->password;
$dbname = $env->Staging_DB_Conscribo->db;


// 1 Authenticate at SKC MySQL Database
$conn = authDB($host, $user, $password, $dbname);

$teams = getTeams($conn); 

print_r($teams);

// To-do: write as skc-teams.json
// ///// Done.


function authDB(string $host, string $user, string $password, string $dbname)
{
    $conn = mysqli_connect($host, $user, $password, $dbname);
    if (!$conn)
    {
        die("SKC MySQL database connection failed: " . mysqli_connect_error());
    }
    $conn->set_charset('utf8'); // Keep this, otherwise accents like é won't work. 
    echo "SKC MySQL database connected successfully <br>";
    return $conn;

}

function getTeams($conn): string
{
    $sql = "SELECT naam_nevobo as naam, poule, niveau, trainingstijden from `Team`";
    $result = $conn->query($sql);

    /* associative array */
    $teams = array();
    while ($team = $result->fetch_array(MYSQLI_ASSOC)) {
        // Add each team to the teams array
        $teams[] = $team;
    }
    $teams = json_encode($teams);
    return $teams;
} 


    // Close connection
    mysqli_close($conn);
 