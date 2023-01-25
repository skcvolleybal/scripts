<?php

/**
 * Dit script synchroniseert de Conscribo MySQL table naar de j3_users en j3_comprofiler tables van Joomla 3. 
 * 
 * Conscribo is the single source of truth. Dus: 
 * 1. Als een user (lid) bestaat in Conscribo maar niet in j3_users, voeg toe aan j3_users en daarna j3_comprofiler.
 * 2. Als een user bestaat in Conscribo én in j3_users, update dan alle velden in j3_users (BEHALVE het veld 'password', zodat user nog kan inloggen in de Joomla site). 
 * 3. Als een user niet bestaat in Conscribo maar wel in j3_users, verwijder dan de user uit j3_comprofiler en daarna j3_users. 
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     kmsch
 * @copyright  2022 kmsch
 */


// 0 Get and set environment variables
$env = json_decode(file_get_contents("../../../env.json"));
$envC = $env->Staging_DB_Conscribo; // Conscribo DB
$envJ3 = $env->Joomla3; // Joomla3 DB

// Connect to the Conscribo MySQL Table
$conn_Conscribo = authDB(
    $envC->$host,
    $envC->$username,
    $envC->$password,
    $envC->$dbname
);

// Connect to the Joomla 3 MySQL Table, vcontaining j3_users and j3_comprofiler
$conn_J3 = authDB(
    $envJ3->$host,
    $envJ3->$username,
    $envJ3->$password,
    $envJ3->$dbname
);


function authDB(string $host, string $user, string $password, string $dbname)
{
    $conn = mysqli_connect($host, $user, $password, $dbname);
    if (!$conn)
    {
        die("$dbname MySQL database connection failed: " . mysqli_connect_error());
    }
    $conn->set_charset('utf8'); // Keep this, otherwise accents like é won't work. 
    echo "Succesfully connected to DB: $dbname <br>";
    return $conn;

}


// Close connection
mysqli_close($conn);
 