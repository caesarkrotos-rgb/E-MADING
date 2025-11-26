<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'emading_db';

// Koneksi ke MySQL server dulu
$conn = mysqli_connect($host, $username, $password);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Pilih database
$db_selected = mysqli_select_db($conn, $database);
if (!$db_selected) {
    // Jika database tidak ada, buat database
    mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
    mysqli_select_db($conn, $database);
}
?>