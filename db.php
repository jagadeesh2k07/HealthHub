<?php
$host     = "sql313.infinityfree.com";
$user     = "if0_42297065";
$password = "4L1uhbr1hk5";
$database = "if0_42297065_healthhub";
$conn = mysqli_connect($host, $user, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
