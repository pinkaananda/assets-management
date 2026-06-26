<?php
    $server = "localhost";
    $user = "root";
    $pass = "";
    $db = "u299101980_db_depumi";

    $conn = mysqli_connect($server, $user, $pass, $db);

    if (!$conn) {
        die("Koneksi gagal: " . mysqli_connect_error());
    }
?>
