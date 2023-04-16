<?php

/**
 * Dit script synchroniseert de Conscribo MySQL table naar de j3_users en j3_comprofiler tables van Joomla 3. 
 * 
 * Conscribo is de single source of truth. Dus: 
 * 1. Als een user (lid) bestaat in Conscribo maar niet in j3_users, voeg toe aan j3_users en daarna j3_comprofiler.
 * 2. Als een user bestaat in Conscribo én in j3_users, update dan alle velden in j3_users (BEHALVE het veld 'password', zodat user nog kan inloggen in de Joomla site). 
 * 3. Als een user niet bestaat in Conscribo maar wel in j3_users, verwijder dan de user uit j3_comprofiler en daarna j3_users. 
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     kmsch
 * @copyright  2022 kmsch
 */

//  Include Base ETL for Sentry logging, autoloader and Env variables
include('Base_ETL.php');

// How long does this run? Start time:
$start_time = microtime(true);

// Two database objects because source and target may be on different servers
// Connect to the Conscribo MySQL Table
$conn_Conscribo = authDB(
    $env->Conscribo_DB->host,
    $env->Conscribo_DB->username,
    $env->Conscribo_DB->password,
    $env->Conscribo_DB->db
);

// Connect to the Joomla 3 MySQL Table, vcontaining j3_users and j3_comprofiler
$conn_J3 = authDB(
    $env->Joomla3->host,
    $env->Joomla3->username,
    $env->Joomla3->password,
    $env->Joomla3->db
);

syncUsers();


function syncUsers()
{


    $conscriboPersonen = getConscriboPersonen();
    $joomlaUsers = getJoomlaUsers();

    // Sanitize the users first: check for missing fields, duplicates, etc.
    $conscriboPersonen = sanitizeConscriboPersonen($conscriboPersonen);

    // Join the Joomla groups on each conscriboPersonen object, for later insertion
    $conscriboPersonen = joinJoomlaGroupsOnConscriboPersonen($conscriboPersonen);

    // print_r($conscriboPersonen);


    foreach ($conscriboPersonen as $key => $persoon) {
        $found = FALSE;
        foreach ($joomlaUsers as $joomlaUser) {
            if ($persoon['email'] == $joomlaUser['email']) {
                $found = TRUE;
                // 1. A Conscribo persoon already exists in Joomla DB, so we update it's data. 
                // print_r($persoon['team_2']);
                updateJoomlaUser($persoon, $joomlaUser);
            }
        }
        if ($found == FALSE) {
            // 2. A Conscribo persoon does not yet exist in Joomla DB, so we add the user. 
            addJoomlaUser($persoon);
        }
    }

    foreach ($joomlaUsers as $key => $joomlaUser) {
        $found = FALSE;
        foreach ($conscriboPersonen as $persoon) {
            if ($joomlaUser['email'] == $persoon['email']) {
                $found = TRUE;
            }
        }
        if ($found == FALSE) {
            // 3. A Joomla user exists which has been removed in Conscribo. So remove it from Jooml a. 
            print_r($joomlaUser['email'] . ' does not exist in Conscribo, but does exist in Joomla 3, so we remove the user from Joomla. <br>');
            removeJoomlaUser($joomlaUser);
        }
    }
}

function getConscriboPersonen(): array
{
    global $conn_Conscribo;
    return runQuery($conn_Conscribo, 'SELECT * FROM  `persoon`');
}

function getJoomlaUsers(): array
{
    global $conn_J3;
    return runQuery($conn_J3, 'SELECT * FROM  `J3_users`');
}

function getJoomlaUser($user): array
{
    global $conn_J3;
    return runQuery($conn_J3, 'SELECT * FROM  `J3_users` WHERE `email` ="' . $user['email'] . '"');
}


function getJoomlaGroups(): array
{
    global $conn_J3;
    return runQuery($conn_J3, "SELECT G.id, title FROM J3_usergroups G order by title asc");
}

function addJoomlaUser($user)
{
    global $conn_J3;
    $sql = "INSERT INTO j3_users (name, username, email, registerDate, lastvisitDate, lastResetTime)
                VALUES ('" . $user['voornaam'] .  " " . $user['naam'] . "', '" . $user['username'] . "', '" . $user['email'] . "' , NOW(), NOW(), NOW())";



    runQuery($conn_J3, $sql);
    print_r("Synced Conscribo persoon " .  $user['email'] . " successfully (added)<br>");
}

function updateJoomlaUser($persoon, $joomlaUser)
{
    global $conn_J3;
    $sql = "UPDATE j3_users
        SET name = '" . $persoon['voornaam'] .  " " . $persoon['naam'] . "', username=  '" . $persoon['username'] . "', email = '" . $persoon['email'] . "' WHERE email = '" . $persoon['email'] . "' ";
    runQuery($conn_J3, $sql);


    if (isset($persoon['JoomlaGroups'])) {
        // Update groups
        foreach ($persoon['JoomlaGroups'] as $group_id) {
            $sql = 'INSERT INTO J3_user_usergroup_map (user_id, group_id) VALUES (' . $joomlaUser['id'] . ', ' . $group_id . ') 
            ON DUPLICATE KEY UPDATE group_id = ' . $group_id;
            print_r('Updated usergroup: ' . $group_id . '<br>');

            runQuery($conn_J3, $sql);
        }
    }


    print_r("Synced Conscribo persoon " .  $persoon['email'] . " successfully (updated)<br>");
}


function removeJoomlaUser($user)
{
    global $conn_J3;
    $sql = "DELETE FROM j3_users WHERE email = '" . $user['email'] . "'";

    if (runQuery($conn_J3, $sql)) {
        print_r("User " .  $user['email'] . " removed from Joomla users table successfully <br>");
    }
}

function sanitizeConscriboPersonen(array $conscriboPersonen): array
{

    foreach ($conscriboPersonen as $key => $persoon) {

        // We do not sync users who do have synchroniseren turned off, in case we need accounts in Conscribo, without them being shouldn't be in the Joomla site. 
        if (!$persoon['synchroniseren'] == 1) {
            print_r('Not syncing Conscribo persoon because synchronisation is turned off for ' . $persoon['voornaam'] . ' ' . $persoon['naam'] . '<br>');
            unset($conscriboPersonen[$key]);
        }

        // We do not sync personen without an e-mail address, because Joomla requires accounts to have an e-mail address. 
        // These persons may exist as part of Conscribo's dummy or for other reasons. They just don't get a Joomla account. 
        if ($persoon['email'] == null || $persoon['email'] == '') {
            print_r($persoon, true) . " is not synced because no e-mail address exists";
            unset($conscriboPersonen[$key]);
        }

        // We do not sync personen without a username, because Joomla requires accounts to have a username. 
        // These persons may exist as part of Conscribo's dummy or for other reasons. They just don't get a Joomla account. 
        if ($persoon['username'] == null || $persoon['username'] == '') {
            print_r('Not syncing Conscribo persoon ' . $persoon['voornaam'] . ' ' . $persoon['naam'] . ' because of missing username field.<br>');
            unset($conscriboPersonen[$key]);
        }

        // We do not sync personen with an invalid e-mail address. 
        if (!filter_var($persoon['email'], FILTER_VALIDATE_EMAIL)) {
            unset($conscriboPersonen[$key]);
        }
    }

    // We throw an exception (and Sentry notification) in case Conscribo personen have double e-mail addresses. Conscribo may accept it, but Joomla doesn't. 
    $emailAddresses = array();
    foreach ($conscriboPersonen as $persoon) {
        $emailAddresses[] = $persoon['email'];
    }
    $duplicateEmailAddresses = array_unique(array_diff_assoc($emailAddresses, array_unique($emailAddresses)));
    foreach ($conscriboPersonen as $key => $persoon) {
        foreach ($duplicateEmailAddresses as $duplicateEmailAddress) {
            if ($persoon['email'] == $duplicateEmailAddress) {
                throw new Exception("Cannot continue import! User " . $duplicateEmailAddress . " user has multiple identical e-mail addresses in Conscribo, which Joomla can't work with. Correct the mistake in Conscribo by making sure each user has just one e-mail address. ");
                unset($conscriboPersonen[$key]);
            }
        }
    }

    return $conscriboPersonen;
}


function joinJoomlaGroupsOnConscriboPersonen(array $conscriboPersonen)
{
    $joomlaGroups = getJoomlaGroups();

    foreach ($conscriboPersonen as $key => $persoon) {
        // Add the team
        $commissies = [];
        $coach_van = [];
        $trainer_van = [];
        $teams = [];

        // Add the commissies
        $persoon['commissies'] = explode(', ', strtolower($persoon['commissies']));
        $persoon['coach_van'] = explode(', ', strtolower($persoon['coach_van']));
        $persoon['trainer_van'] = explode(', ', strtolower($persoon['trainer_van']));
        $persoon['team_2'] = explode(', ', strtolower($persoon['team_2']));

        foreach ($joomlaGroups as $joomlaGroup) {

            foreach ($persoon['commissies'] as $commissie) {
                if ($commissie == strtolower($joomlaGroup['title'])) {
                    $conscriboPersonen[$key]['JoomlaGroups'][] = $joomlaGroup['id'];
                }
            }

            foreach ($persoon['team_2'] as $team) {
                if ($team == strtolower($joomlaGroup['title'])) {
                    $conscriboPersonen[$key]['JoomlaGroups'][] = $joomlaGroup['id'];
                }
            }

            foreach ($persoon['coach_van'] as $coach_van) {
                if ($coach_van == strtolower('coach van ' . $joomlaGroup['title'])) {
                    $conscriboPersonen[$key]['JoomlaGroups'][] = $joomlaGroup['id'];
                }
            }

            foreach ($persoon['trainer_van'] as $trainer_van) {
                if ($trainer_van == strtolower('Trainer van ' . $joomlaGroup['title'])) {
                    $conscriboPersonen[$key]['JoomlaGroups'][] = $joomlaGroup['id'];
                }
            }
        }
    }
    return $conscriboPersonen;
}



function runQuery(mysqli $conn, string $query): array|bool
{

    if ($result = mysqli_query($conn, $query)) {
        // If we have a result, return it as array
        if (isset($result->num_rows)) {
            $records = array();
            while ($record = $result->fetch_array(MYSQLI_ASSOC)) {
                $records[] = $record;
            }
            return $records;
        } else {
            // We have no result, but the query ran successfully, return true
            return true;
        }
    } else {
        throw new Exception("Error running query: " . $query . mysqli_error($conn));
    }
}

function authDB(string $host, string $user, string $password, string $dbname)
{
    $conn = mysqli_connect($host, $user, $password, $dbname);
    if (!$conn) {
        die("$dbname MySQL database connection failed: " . mysqli_connect_error());
    }
    $conn->set_charset('utf8'); // Keep this, otherwise accents like é won't work. 
    echo "Succesfully connected to DB: $dbname <br>";
    return $conn;
}


// Close connection
mysqli_close($conn_Conscribo);
mysqli_close($conn_J3);


$end_time = microtime(true);
$execution_time = ($end_time - $start_time);

echo "Runtime van dit script: $execution_time sec";
