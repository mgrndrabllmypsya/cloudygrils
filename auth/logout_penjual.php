<?php
session_name('session_penjual');
session_start();
session_destroy();
header("Location: ../auth/login.php"); exit;