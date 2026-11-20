<?php
//connection to the database
$host = getenv('MYSQLHOST');
$dbname = getenv('MYSQLDATABASE');
$username = getenv('MYSQLUSER');
$password = getenv('MYSQLPASWORD');
$port = getenv('MYSQLPORT');


try {
 $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
 $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
} catch (PDOException $e) {
 Die("Connection failed: " . $e->getMessage());
}
?>
