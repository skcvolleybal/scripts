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
include ('Base_ETL.php');

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

syncUsers($conn_Conscribo, $conn_J3);


function syncUsers ($conn_Conscribo, $conn_J3) {
    $conscriboPersonen = getConscriboPersonen($conn_Conscribo);
    $joomla3Users = getJoomla3Users ($conn_J3);

    // We do not sync personen without an e-mail address, because Joomla requires accounts to have an e-mail address. 
    // These persons may exist as part of Conscribo's dummy or for other reasons. They just don't get a Joomla account. 
    foreach ($conscriboPersonen as $key => $persoon) {
        if ($persoon['email'] == null || $persoon['email'] == '') {
            print_r('Removed key: ' . $key . ', ');
            unset($conscriboPersonen[$key]);
        }
    }

    // We do not sync personen without a username, because Joomla requires accounts to a username. 
    // These persons may exist as part of Conscribo's dummy or for other reasons. They just don't get a Joomla account. 
    foreach ($conscriboPersonen as $key => $persoon) {
        if ($persoon['username'] == null || $persoon['username'] == '') {
            print_r('Removed key: ' . $key . ', ');
            unset($conscriboPersonen[$key]);
        }
    }

    // We do not sync personen with an invalid e-mail address. 
    foreach ($conscriboPersonen as $key => $persoon) {
        if (!filter_var($persoon['email'], FILTER_VALIDATE_EMAIL)) {
            unset($conscriboPersonen[$key]);
          }
    }

    // We do not sync users who do have synchroniseren turned off, in case we need accounts in Conscribo, without them being shouldn't be in the Joomla site. 
    foreach ($conscriboPersonen as $key => $persoon) {
        if (! $persoon['synchroniseren'] == 1) {
            print_r('Not processing persoon due to synchronising checkbox: ' . $key . ', ');
            unset($conscriboPersonen[$key]);
        }
    }
    

    // We throw an exception (and Sentry notification) in case Conscribo personen have double e-mail addresses. Conscribo may accept it, but Joomla doesn't. 
    $emailAddresses = [];
    foreach ($conscriboPersonen as $persoon) {
        $emailAddresses[] = $persoon['email']; 
    }
    $duplicates = array_unique( array_diff_assoc( $emailAddresses, array_unique( $emailAddresses ) ) );
    foreach ($conscriboPersonen as $key => $persoon) {
        foreach ($duplicates as $duplicate) {
            if ($persoon['email'] == $duplicate) {
                throw new Exception("Cannot continue import! User " . $duplicate . " user has multiple identical e-mail addresses in Conscribo, which Joomla can't work with. Correct the mistake in Conscribo by making sure each user has just one e-mail address. ");
                unset($conscriboPersonen[$key]);
            }
        }
    }


    // We do all this in PHP instead of SQL, because we can't compare or join the Conscribo table with the j3_users table based on index keys. 
    // Conscribo has its own index field (relatienummer), which is different from Joomla's id-field. 
    // We could compare the two in SQL by setting the Joomla email field as unique key, however, Joomla won't let you.  
    // So we do it all here manually.  
    
    // We use nested foreaches because, somehow, array_key_exists() won't work. 
    foreach ($conscriboPersonen as $key => $persoon) {
        $found = FALSE;
        foreach ($joomla3Users as $user) {
            if ($persoon['email'] == $user['email']) {
                print_r($persoon['email'] . ' exists in both Conscribo and Joomla 3 users, so we update its Joomla data <br>');
                $found = TRUE;
                // 1. A Conscribo persoon already exists in Joomla DB, so we update it's data. 
                // Update the user. 
                updateJoomlaUser($persoon, $conn_J3);

            }
        }
        if ($found == FALSE) {
            print_r($persoon['email'] . ' does exist in Conscribo but does not yet exist in Joomla 3 users, so we add the user to Joomla<br>');
                // 2. A Conscribo persoon does not yet exist in Joomla DB, so we add the user. 

                addJoomlaUser($persoon, $conn_J3);
             
        }

    }

    foreach ($joomla3Users as $key => $user) {
        $found = FALSE;
        foreach ($conscriboPersonen as $persoon) {
                if ($user['email'] == $persoon ['email']) {
                    $found = TRUE;
                }
            }
        if ($found == FALSE) {
            // 3. A Joomla user exists which has been removed in Conscribo. So remove it from Jooml a. 
            print_r($user['email'] . ' does not exist in Conscribo, but does exist in Joomla 3, so we remove the user from Joomla. <br>');

                removeJoomlaUser($user, $conn_J3);

        }
        
    }


    
}

    function addJoomlaUser ($user, $conn_J3) {
        
        $sql = "INSERT INTO j3_users (name, username, email)
                VALUES ('" . $user['voornaam'] .  " " . $user['naam'] . "', '" . $user['username'] . "', '" . $user['email'] . "')";

        if (mysqli_query($conn_J3, $sql))
        {
            print_r("New record ." .  $user['email'] . " created in Persoon table successfully <br>");
        }
        else
        {
            throw new Exception("Could not add user to Joomla database: " . $sql . "<br>" . mysqli_error($conn_J3));
        }
    
    }

    function updateJoomlaUser($user, $conn_J3) {

        // $name = $

        $sql = "UPDATE j3_users
        SET name = '" . $user['voornaam'] .  " " . $user['naam'] . "', username=  '" . $user['username'] . "', email = '" . $user['email'] . "'
        WHERE email = '" . $user['email'] . "' ";


        if (mysqli_query($conn_J3, $sql))
        {
            print_r("Existing Joomla user " .  $user['email'] . " updated successfully <br>");
        }
        else
        {
            throw new Exception("Could not update user in Joomla db: " . $sql . "<br>" . mysqli_error($conn_J3));
        }

    
    }


    function removeJoomlaUser($user, $conn_J3) {
        $sql = "DELETE FROM j3_users WHERE email = '" . $user['email'] . "'";

        if (mysqli_query($conn_J3, $sql))
        {
        print_r("User " .  $user['email'] . " removed from Joomla users table successfully <br>");
        }
        else
        {
        throw new Exception("Could not remove Joomla user: " . $sql . "<br>" . mysqli_error($conn_J3));
        }

        

    }



function getConscriboPersonen ($conn_Conscribo) {
    // Get all users from the Conscribo staging DB 

    $sql = "SELECT * FROM  `persoon`";
    $result = $conn_Conscribo->query($sql);

    /* associative array */
    $personen = array();
    while ($persoon = $result->fetch_array(MYSQLI_ASSOC)) {
        // Add each persoon to the personen array
        $personen[] = $persoon;
    }
    return $personen;
}

function getJoomla3Users ($conn_J3) {
    // Get all users from the j3_users table

    $sql = "SELECT email FROM  `j3_users`";
    $result = $conn_J3->query($sql);

    /* associative array */
    $users = array();
    while ($user = $result->fetch_array(MYSQLI_ASSOC)) {
        // Add each user to the users array
        $users[] = $user;
    }
    return $users;
}


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
mysqli_close($conn_Conscribo);
mysqli_close($conn_J3);
 