<?php
/**
 * Logout Logic
 */
require_once '../config/functions.php';
session_destroy();
redirect('login.php');
?>
