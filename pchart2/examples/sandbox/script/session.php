<?php
/*
 session.php - Sandbox sesson manager

 Version     : 1.1.0
 Made by     : Jean-Damien POGOLOTTI
 Last Update : 18/01/11

 This file can be distributed under the license you can find at :

				   http://www.pchart.net/license

 You can find the whole class documentation on the pChart web site.
*/

session_start();

// PHP session storage is shared across cookie names. Never write application keys
// or expose the application's session when a caller reuses its session ID here.
foreach ($_GET as $Key => $Value) {
    if (is_string($Value) && preg_match('/^(?:(?:g|d|s|c|l|t|sl|p)_[a-z0-9_]+|data[012]|absissa)$/D', $Key)) {
        $_SESSION['PCHART_SANDBOX'][$Key] = $Value;
    }
}

header('Cache-Control: no-store');
print_r($_SESSION['PCHART_SANDBOX'] ?? []);

usleep(200000);

?>