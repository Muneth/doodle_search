<?php
// ob_start();

// try {

//     $con = new PDO("mysql:dbname=doodle;host=localhost", "root", "");
//     $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
// }
// catch(PDOException $e) {
//     echo "Connection failed: " . $e->getMessage();

// }

$url = parse_url(getenv("CLEARDB_DATABASE_URL"));

$server = $url["eu-cdbr-west-03.cleardb.net"];
$username = $url["b26eaa157f67ab"];
$password = $url["af8f126a"];
$db = substr($url["path"], 1);

$conn = new mysqli($server, $username, $password, $db);

?>