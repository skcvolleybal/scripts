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

    // Sanitize the personen first: check for missing fields, duplicates, etc.
    $conscriboPersonen = sanitizeConscriboPersonen($conscriboPersonen);

    // Join the Joomla groups on each conscriboPersoon, for later insertion in J3_user_usergroup_map table
    $conscriboPersonen = joinJoomlaGroupsOnConscriboPersonen($conscriboPersonen);

    // Rename the fields of each conscriboPersoon for later insertion in J3_comprofiler DB 
    $conscriboPersonen = renameConscriboFieldsToJoomlaFields($conscriboPersonen);


    foreach ($conscriboPersonen as $key => $conscriboPersoon) {
        $found = FALSE;
        foreach ($joomlaUsers as $joomlaUser) {
            if ($conscriboPersoon['email'] == $joomlaUser['email']) {
                $found = TRUE;
                // 1. A Conscribo persoon already exists in Joomla DB, so we update it's data. 
                // print_r($conscriboPersoon['team_2']);
                updateJoomlaUser($conscriboPersoon, $joomlaUser);
            }
        }
        if ($found == FALSE) {
            // 2. A Conscribo persoon does not yet exist in Joomla DB, so we add the user. 
            $userId = addJoomlaUser($conscriboPersoon);
            // After adding, extra fields have to be added such as J3_comprofiler and J3_user_usergroup_map. So we retrieve the user and update it. 
            $newJoomlaUser = getJoomlaUserById($userId);
            updateJoomlaUser($conscriboPersoon, $newJoomlaUser);
            
        }
    }

    foreach ($joomlaUsers as $key => $joomlaUser) {
        $found = FALSE;
        foreach ($conscriboPersonen as $conscriboPersoon) {
            if ($joomlaUser['email'] == $conscriboPersoon['email']) {
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

function getJoomlaUserById(string $userId): array
{
    global $conn_J3;
    return runQuery($conn_J3, 'SELECT * FROM  `J3_users` WHERE `id` ="' . $userId . '"');
}


function getJoomlaGroups(): array
{
    global $conn_J3;
    return runQuery($conn_J3, "SELECT G.id, title FROM J3_usergroups G order by title asc");
}

function addJoomlaUser($conscriboPersoon): string
{
    global $conn_J3;
    $sql = "INSERT INTO j3_users (name, username, email, registerDate, lastvisitDate, lastResetTime)
        VALUES (
        '" . $conscriboPersoon['voornaam'] .  " " . $conscriboPersoon['naam'] . "',
        '" . $conscriboPersoon['username'] . "',
        '" . $conscriboPersoon['email'] . "' ,
        NOW(),
        NOW(),
        NOW())
                ";

    // Insert the user
    runQuery($conn_J3, $sql);

    // Return the last inserted ID
    $sql = "SELECT LAST_INSERT_ID()";
    $result = runQuery($conn_J3, $sql);
    $userId = $result[0]["LAST_INSERT_ID()"];

    print_r("Synced Conscribo persoon " .  $conscriboPersoon['email'] . " successfully (added)<br>");

    return $userId;
}

function updateJoomlaUser($conscriboPersoon, $joomlaUser)
{
    global $conn_J3;
    $sql = "UPDATE j3_users
        SET 
        name = 
        '" . $conscriboPersoon['voornaam'] .  " " . $conscriboPersoon['naam'] . "',
        username=  '" . $conscriboPersoon['username'] . "',
        email = '" . $conscriboPersoon['email'] . "'
        WHERE email = '" . $conscriboPersoon['email'] . "' ";
    runQuery($conn_J3, $sql);


    // If the Conscribo persoon has Joomla Groups to be added, insert or update them 
    if (isset($conscriboPersoon['JoomlaGroups'])) {
        // Insert or update groups
        foreach ($conscriboPersoon['JoomlaGroups'] as $group_id) {
            $sql = 'INSERT INTO J3_user_usergroup_map (user_id, group_id) VALUES (' . $joomlaUser['id'] . ', ' . $group_id . ') 
            ON DUPLICATE KEY UPDATE user_id = ' . $joomlaUser['id'] . ', group_id = ' . $group_id;
            print_r('Updated usergroup: ' . $group_id . '<br>');

            runQuery($conn_J3, $sql);
        }
    }

    // Update or insert 

    if (isset($conscriboPersoon['Communitybuilder_fields'])) {
        // Insert or update CB fields
        foreach ($conscriboPersoon['Communitybuilder_fields'] as $field) {
            // $sql = 'INSERT INTO J3_comprofiler (user_id, cb_lengte, cb_rugnummer, cb_scheidsrechterscode, cb_telephone) 
            // VALUES (
            // ' . $joomlaUser['id'] . ',
            // ' . $group_id . '
            
            // ) 
            // ON DUPLICATE KEY UPDATE user_id = ' . $user_id;
            // print_r('Updated usergroup: ' . $user_id . '<br>');

            // runQuery($conn_J3, $sql);
        }
    }


    print_r("Synced Conscribo persoon " .  $conscriboPersoon['email'] . " successfully (updated)<br>");
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

    foreach ($conscriboPersonen as $key => $conscriboPersoon) {

        // We do not sync users who do have synchroniseren turned off, in case we need accounts in Conscribo, without them being shouldn't be in the Joomla site. 
        if (!$conscriboPersoon['synchroniseren'] == 1) {
            print_r('Not syncing Conscribo persoon because synchronisation is turned off for ' . $conscriboPersoon['voornaam'] . ' ' . $conscriboPersoon['naam'] . '<br>');
            unset($conscriboPersonen[$key]);
        }

        // We do not sync personen without an e-mail address, because Joomla requires accounts to have an e-mail address. 
        // These persons may exist as part of Conscribo's dummy or for other reasons. They just don't get a Joomla account. 
        if ($conscriboPersoon['email'] == null || $conscriboPersoon['email'] == '') {
            print_r($conscriboPersoon, true) . " is not synced because no e-mail address exists";
            unset($conscriboPersonen[$key]);
        }

        // We do not sync personen without a username, because Joomla requires accounts to have a username. 
        // These persons may exist as part of Conscribo's dummy or for other reasons. They just don't get a Joomla account. 
        if ($conscriboPersoon['username'] == null || $conscriboPersoon['username'] == '') {
            print_r('Not syncing Conscribo persoon ' . $conscriboPersoon['voornaam'] . ' ' . $conscriboPersoon['naam'] . ' because of missing username field.<br>');
            unset($conscriboPersonen[$key]);
        }

        // We do not sync personen with an invalid e-mail address. 
        if (!filter_var($conscriboPersoon['email'], FILTER_VALIDATE_EMAIL)) {
            unset($conscriboPersonen[$key]);
        }
    }

    // We throw an exception (and Sentry notification) in case Conscribo personen have double e-mail addresses. Conscribo may accept it, but Joomla doesn't. 
    $emailAddresses = array();
    foreach ($conscriboPersonen as $conscriboPersoon) {
        $emailAddresses[] = $conscriboPersoon['email'];
    }
    $duplicateEmailAddresses = array_unique(array_diff_assoc($emailAddresses, array_unique($emailAddresses)));
    foreach ($conscriboPersonen as $key => $conscriboPersoon) {
        foreach ($duplicateEmailAddresses as $duplicateEmailAddress) {
            if ($conscriboPersoon['email'] == $duplicateEmailAddress) {
                throw new Exception("Cannot continue import! User " . $duplicateEmailAddress . " user has multiple identical e-mail addresses in Conscribo, which Joomla can't work with. Correct the mistake in Conscribo by making sure each user has just one e-mail address. ");
                unset($conscriboPersonen[$key]);
            }
        }
    }

    return $conscriboPersonen;
}


function joinJoomlaGroupsOnConscriboPersonen(array $conscriboPersonen)
{

    // First, we get all the Joomla groups (ID and title, for example ID 49, title Webcie, etc)
    // Then, for example, if the Conscribo persoon is member of the Webcie commissie, we add the Joomla Group ID (49) to the Conscribo persoon
    // We do this for all committees, teams, etc
    // So that later, we have a Conscribo persoon with all the required Joomla group IDs to insert into the DB

    $joomlaGroups = getJoomlaGroups();

    foreach ($conscriboPersonen as $key => $conscriboPersoon) {

        $conscriboIterableFields = ['commissies', 'coach_van', 'trainer_van', 'team_2'];

        // We may have personen with multiple commissies, coaches, trainers or teams. Conscribo splits these with commas.
        // So we split each field on commas as well, so we have arrays
        foreach ($conscriboIterableFields as $conscriboField) {
            $conscriboPersoon[$conscriboField] = explode(', ', strtolower($conscriboPersoon[$conscriboField]));
        }

        foreach ($joomlaGroups as $joomlaGroup) {

            foreach ($conscriboPersoon['commissies'] as $commissie) {
                if ($commissie == strtolower($joomlaGroup['title'])) {
                    $conscriboPersonen[$key]['JoomlaGroups'][] = $joomlaGroup['id'];
                }
            }

            foreach ($conscriboPersoon['team_2'] as $team) {
                if ($team == strtolower($joomlaGroup['title'])) {
                    $conscriboPersonen[$key]['JoomlaGroups'][] = $joomlaGroup['id'];
                }
            }

            foreach ($conscriboPersoon['coach_van'] as $coach_van) {
                if (strtolower('coach ' . $coach_van) == strtolower($joomlaGroup['title'])) {
                    $conscriboPersonen[$key]['JoomlaGroups'][] = $joomlaGroup['id'];
                }
            }

            foreach ($conscriboPersoon['trainer_van'] as $trainer_van) {
                if (strtolower('trainer ' . $trainer_van) == strtolower($joomlaGroup['title'])) {
                    $conscriboPersonen[$key]['JoomlaGroups'][] = $joomlaGroup['id'];
                }
            }
        }
    }
    return $conscriboPersonen;
}


function renameConscriboFieldsToJoomlaFields(array $conscriboPersonen): array
{
    // Key of this array is the Conscribo field, value is Joomla Community Builder field
    $renameFields = array(
        "lengte__cm_" => "cb_lengte",
        "rugnummer" => "cb_rugnummer",
        "scheidsrechterscode" => "cb_scheidsrechterscode",
        "telefoon" => "cb_telephone"
        // "positie" => "cb_positie"
    );

    foreach ($conscriboPersonen as $key => $conscriboPersoon) {
        foreach ($renameFields as $conscriboField => $joomlaField) {
            // We want to rename the Conscribo fields to fieldnames used in the Joomla DB
            // To each Conscribo persoon, we add the new Joomla field so that the field has the correct name
            $conscriboPersonen[$key]["Communitybuilder_fields"][$joomlaField] = $conscriboPersoon[$conscriboField];
            
            // We remove the old fieldname to clean up 
            unset($conscriboPersonen[$key][$conscriboField]);
        }
    }

    return $conscriboPersonen;
}


function runQuery(mysqli $conn, string $query): array|bool
{
    // Generic function to run queries. 

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
    // Generic function for MySQL database authorization.

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
