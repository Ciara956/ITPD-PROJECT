<?php
//connection to the database
$host = "localhost";
$dbname = "maternal_health_db"; 
$username = "root";
$password = ""; 
Try {
 $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
 $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
} catch (PDOException $e) {
 Die("Connection failed: " . $e->getMessage());
}
?>