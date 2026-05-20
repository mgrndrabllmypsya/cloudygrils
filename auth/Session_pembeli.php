<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('sess_pembeli');
    session_start();
}