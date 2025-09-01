<?php
session_start();

// Destroy semua session data
session_destroy();

// Redirect ke halaman login dengan mesej
header('Location: login.php?logged_out=1');
exit();
?>