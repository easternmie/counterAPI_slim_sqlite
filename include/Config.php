<?php
/**
 * Database configuration
 */
define('DB_USERNAME', 'counterbygdu');
define('DB_PASSWORD', 'counterbygdu');
define('DB_HOST', 'localhost');
define('DB_NAME', 'counterbygdu');


define('USER_CREATED_SUCCESSFULLY', 0);
define('USER_CREATE_FAILED', 1);
define('USER_ALREADY_EXISTED', 2);

// this line is for processing all native php files in folder /process
// $dbh = new PDO('mysql:host=localhost;dbname=counterbygdu', 'counterbygdu', 'counterbygdu');
$dbc = new PDO('sqlite:db.sqlite3');
$dbc->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>
