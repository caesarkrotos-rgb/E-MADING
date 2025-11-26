<?php
session_start();
include 'config.php';

if ($_POST) {
  $username = $_POST['username'];
  $password = $_POST['password'];
  
  $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
  $result = mysqli_query($conn, $sql);
  
  if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['role'] = $user['role'];
    
    if ($user['role'] == 'admin') {
      header("Location: admin/dashboard.php");
    } elseif ($user['role'] == 'guru') {
      header("Location: guru/dashboard.php");
    } else {
      header("Location: siswa/dashboard.php");
    }
  } else {
    echo "<script>alert('Username atau password salah!'); window.location='index.php';</script>";
  }
}
?>