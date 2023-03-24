<?php

/**
 * Dit script extract alle SKC leden en teams uit Conscribo en plaatst ze 1-op-1 in een MySQL database, lokaal of bij de SKC webhost (SKC . database 
 * Alleen bankgegevens worden standaard niet meegenomen, lijkt nu niet nodig. 
 * Doel hiervan is dat de MySQL database gequeried kan worden door bijvoorbeeld Team-Portal ipv Conscribo om zo de load op de Conscribo te verminderen. De data is ook gedenormaliseerd voor simpliciteit. 
 * Gebruik dit script met een cron job om het bijvoorbeeld elk uur laten draaien.
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     kmsch
 * @copyright  2022 kmsch
 */

//  Include Base ETL for Sentry logging, autoloader and Env variables
include ('Base_ETL.php');

// How long does this run? Start time:
$start_time = microtime(true);

// 1 Authenticate at SKC's MySQL Database
$conn = authDB($env);

// 2 Authenticate at Conscribo API and set Conscribo Session ID
$cSessionID = authConscribo(
    $env->Conscribo_API->username, 
    $env->Conscribo_API->password, 
    $env->Conscribo_API->accountname
);

// 3 Get Conscribo Personen
$cPersonen = extractCPersonen($cSessionID, $env);

// 4 Get Conscribo Teams
$cTeams = extractCTeams($cSessionID, $env);

// 5 Get Conscribo Commissies
$cCommissies = extractCCommissies($cSessionID, $env);

// Drop Personen, Teams and Commissies Tables. Recreate tables. 
dropTables($conn);
createTables($conn);

// 6 Update Personen DB
loadcPersonenToSQL($cPersonen, $conn);

// 7 Update Teams DB
loadcTeamsToSQL($cTeams, $conn);

// 8 Update Commissies DB
loadcCommissiesToSQL($cCommissies, $conn);


// ///// Done.

function authDB($env)
{
    $credentials = $env->Conscribo_DB;

    $conn = mysqli_connect(
        $credentials->host,
        $credentials->user,
        $credentials->password,
        $credentials->dbname
    );
    if (!$conn)
    {
        throw new Exception("SKC MySQL database connection failed: " . mysqli_connect_error());
    }
    $conn->set_charset('utf8'); // Keep this, otherwise accents like é won't work. 
    echo "Succesfully connected to DB: $dbname <br>";
    return $conn;

}

function authConscribo(string $cUsername, string $cPassword, string $cAccountName): string
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://secure.conscribo.nl/' . $cAccountName . '/request.json',
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{"request":{"command":"authenticateWithUserAndPass","userName":"' . $cUsername . '","passPhrase":"' . $cPassword . '"}}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json;charset=UTF-8'
            ) ,
            CURLOPT_RETURNTRANSFER => TRUE
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response);

        // Check if we're authenticated
        if (!isset($response->result))
        {
            throw new Exception("Conscribo connection failed, got no valid response: " . print_r($response));

        }
        if ($response->result->success != 1)
        {
            throw new Exception("Conscribo authentication failed: " . print_r($response));
        }
        echo "Conscribo account: $cAccountName connection successful <br>";
        return $response->result->sessionId;
    }

    function extractCPersonen(string $cSessionID, $env)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://secure.conscribo.nl/' . $env->Conscribo_API->accountname . '/request.json',
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{"request": {
                "command": "listRelations",
                "requestedFields": {
                    "fieldName" : [
                        "lidmaatschap",
                        "selector", 
                        "voor_en_achternaam",
                        "code", 
                        "voornaam",
                        "tussenvoegsel",
                        "naam",
                        "adres",
                        "postcode",
                        "straat",
                        "huisnr",
                        "huisnr_toev",
                        "plaats",
                        "telefoon",
                        "email",
                        "synchroniseren",
                        "username",
                        "lengte__cm_",
                        "rugnummer",
                        "scheidsrechterscode",
                        "startdatum_lid",
                        "einddatum_lid",
                        "team_2",
                        "commissies",
                        "coach_van",
                        "trainer_van"
                    ]
                },
            "entityType": "persoon"
            }
            }
            ',
            CURLOPT_HTTPHEADER => array(
                'X-Conscribo-SessionId: ' . $cSessionID . '',
                'Content-Type: application/json;charset=UTF-8'
            ) ,
            CURLOPT_RETURNTRANSFER => TRUE
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        // Remove all apostrophes
        $response = str_replace('\'', '', $response);
        $response = json_decode($response);


        if ($response->result->success != 1)
        {
            throw new Exception("Failed to extract personen from Conscribo: " . $response);
        }
        

        return $response;

    }


    function extractCTeams(string $cSessionID, $env)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://secure.conscribo.nl/' . $env->Conscribo_API->accountname . '/request.json',
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{"request": {
                "command": "listRelations",
                "requestedFields": {
                    "fieldName" : [
                        "code",
                        "leden",
                        "poule",
                        "niveau",
                        "trainingstijden",
                        "naam_nevobo",
                        "naam",
                        "weergavenaam",
                        "selector",
                        "coaches",
                        "trainers"
                    ]
                },
            "entityType": "team"
            }
            }
            
            ',
            CURLOPT_HTTPHEADER => array(
                'X-Conscribo-SessionId: ' . $cSessionID . '',
                'Content-Type: application/json;charset=UTF-8'
            ) ,
            CURLOPT_RETURNTRANSFER => TRUE
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response);

        if ($response->result->success != 1)
        {
            throw new Exception("Failed to extract teams from Conscribo: " . $response);
        }

        return $response;

    }

    function extractCCommissies(string $cSessionID, $env)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://secure.conscribo.nl/' . $env->Conscribo_API->accountname . '/request.json',
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{"request": {
                "command": "listRelations",
                "requestedFields": {
                    "fieldName" : [
                        "code",
                        "naam",
                        "weergavenaam",
                        "commissieleden"
                    ]
                },
            "entityType": "commissie"
            }
            }
            
            ',
            CURLOPT_HTTPHEADER => array(
                'X-Conscribo-SessionId: ' . $cSessionID . '',
                'Content-Type: application/json;charset=UTF-8'
            ) ,
            CURLOPT_RETURNTRANSFER => TRUE
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response);

        if ($response->result->success != 1)
        {
            throw new Exception("Failed to extract commissies from Conscribo: " . $response);
        }

        return $response;

    }



    function dropTables($conn) {
        $sql = "DROP TABLE IF EXISTS `Persoon`, `Team`, `Commissie`";
        if (mysqli_query($conn, $sql))
        {
            echo "Tables dropped succesfully  <br>";
        }
        else
        {
            throw new Exception ("Error: " . $sql . "<br>" . mysqli_error($conn));
        }

    }

    function createTables($conn) {
        $sql = "CREATE TABLE Persoon (
            code int NULL,
            lidmaatschap VARCHAR(255) NULL,
            selector VARCHAR(255) NULL,
            voor_en_achternaam VARCHAR(255) NULL,
            voornaam VARCHAR(255) NULL,
            tussenvoegsel VARCHAR(255) NULL,
            naam VARCHAR(255) NULL, 
            adres VARCHAR(255) NULL,
            postcode VARCHAR(255) NULL,
            straat VARCHAR(255) NULL,
            huisnr VARCHAR(255) NULL,
            huisnr_toev VARCHAR(255) NULL,
            plaats VARCHAR(255) NULL,
            telefoon VARCHAR(255) NULL,
            email VARCHAR(255) NULL,
            synchroniseren VARCHAR(255) NULL,
            username VARCHAR(255) NULL,
            lengte__cm_ VARCHAR(255) NULL,
            rugnummer VARCHAR(255) NULL,
            scheidsrechterscode VARCHAR(255) NULL,
            startdatum_lid VARCHAR(255) NULL,
            einddatum_lid VARCHAR(255) NULL,
            commissies VARCHAR(255) NULL,
            coach_van VARCHAR(255) NULL,
            trainer_van VARCHAR(255) NULL,
            team_2 VARCHAR(255) NULL,
            PRIMARY KEY (code))
          ";
        if (mysqli_query($conn, $sql))
        {
            echo "Persoon table created succesfully  <br>";
        }
        else
        {
            throw new Exception("Error: " . $sql . "<br>" . mysqli_error($conn));
        }

        $sql = "CREATE TABLE Team (
            code int NULL,
            leden VARCHAR(255) NULL,
            poule VARCHAR(255) NULL,
            niveau VARCHAR(255) NULL,
            trainingstijden VARCHAR(255) NULL,
            naam_nevobo VARCHAR(255) NULL,
            naam VARCHAR(255) NULL,
            weergavenaam VARCHAR (255) NULL, 
            selector VARCHAR(255) NULL,
            coaches VARCHAR(255) NULL,
            trainers VARCHAR(255) NULL,
            PRIMARY KEY (code)
          )
          ";
        if (mysqli_query($conn, $sql))
        {
            echo "Team table created succesfully  <br>";
        }
        else
        {
            throw new Exception("Error: " . $sql . "<br>" . mysqli_error($conn));
        }

        $sql = "CREATE TABLE Commissie (
            code int NULL,
            naam VARCHAR(255) NULL,
            weergavenaam VARCHAR(255) NULL,
            commissieleden VARCHAR(255) NULL,
            PRIMARY KEY (code)
          )
          ";
        if (mysqli_query($conn, $sql))
        {
            echo "Commissie table created succesfully  <br>";
        }
        else
        {
            throw new Exception("Error: " . $sql . "<br>" . mysqli_error($conn));
        }



    }



    function loadcPersonenToSQL($cPersonen, $conn) {
        // Loop through data and insert into Persoon table
        foreach ($cPersonen->result->relations as $persoon)

        {
            $sql = "INSERT INTO Persoon (
            lidmaatschap,
            code,
             voornaam,
             tussenvoegsel,
             naam,
             adres,
             postcode,
             straat,
             huisnr,
             huisnr_toev,
             plaats,
             telefoon,
             email,
             synchroniseren,
             username,
             lengte__cm_,
             rugnummer,
             scheidsrechterscode,
             startdatum_lid,
             einddatum_lid,
             team_2,
             commissies,
             coach_van,
             trainer_van
             ) 
            
            VALUES (
             '" . $persoon->lidmaatschap . "',
             '" . $persoon->code . "',
             '" . $persoon->voornaam . "',
             '" . $persoon->tussenvoegsel . "',
             '" . $persoon->naam . "',
             '" . $persoon->adres . "',
             '" . $persoon->postcode . "',
             '" . $persoon->straat . "',
             '" . $persoon->huisnr . "',
             '" . $persoon->huisnr_toev . "',
             '" . $persoon->plaats . "',
             '" . $persoon->telefoon . "',
             '" . $persoon->email . "',
             '" . $persoon->synchroniseren . "',
             '" . $persoon->username . "',
             '" . $persoon->lengte__cm_ . "',
             '" . $persoon->rugnummer . "',
             '" . $persoon->scheidsrechterscode . "',
             '" . $persoon->startdatum_lid . "',
             '" . $persoon->einddatum_lid . "',
             '" . $persoon->team_2 . "',
             '" . $persoon->commissies . "',
             '" . $persoon->coach_van . "',
             '" . $persoon->trainer_van . "'
             )";
            if (mysqli_query($conn, $sql))
            {
                echo "New record $persoon->voornaam created in Persoon table successfully <br>";
            }
            else
            {
                throw new Exception("Error: " . $sql . "<br>" . mysqli_error($conn));
            }
        }

    }


    function loadcTeamsToSQL($cPersonen, $conn) {
        // Loop through data and insert into Persoon table
        foreach ($cPersonen->result->relations as $team)

        
        {
            $sql = "INSERT INTO Team (
            code, 
            leden, 
            poule, 
            niveau, 
            trainingstijden, 
            naam_nevobo, 
            weergavenaam, 
            selector, 
            coaches, 
            trainers
            ) 
            VALUES (
             '" . $team->code . "',
             '" . $team->leden . "',
             '" . $team->poule . "',
             '" . $team->niveau . "',
             '" . $team->trainingstijden . "',
             '" . $team->naam_nevobo . "',
             '" . $team->weergavenaam . "',
             '" . $team->selector . "',
             '" . $team->coaches . "',
             '" . $team->trainers . "')";
            if (mysqli_query($conn, $sql))
            {
                echo "New record $team->naam_nevobo created in Team table successfully <br>";
            }
            else
            {
                throw new Exception("Error: " . $sql . "<br>" . mysqli_error($conn));
            }
        }

    }

    function loadcCommissiesToSQL($cCommissies, $conn) {
        // Loop through data and insert into Commissie table
        foreach ($cCommissies->result->relations as $commissie)

        {
            $sql = "INSERT INTO Commissie (code, naam, weergavenaam, commissieleden) 
                    VALUES (
                    '" . $commissie->code . "',
                    '" . $commissie->naam . "',
                    '" . $commissie->weergavenaam . "',
                    '" . $commissie->commissieleden . "')";

            if (mysqli_query($conn, $sql))
            {
                echo "New record $commissie->naam created in Commissie table successfully <br>";
            }
            else
            {
                throw new Exception("Error: " . $sql . "<br>" . mysqli_error($conn));
            }
        }

    }

    // Close connection
    mysqli_close($conn);

    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time);

    echo "Runtime van dit script: $execution_time sec";
    