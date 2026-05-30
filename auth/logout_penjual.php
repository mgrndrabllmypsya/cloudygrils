<?php
require_once 'session_penjual.php';
session_destroy();
header("Location: ../auth/login.php"); exit;