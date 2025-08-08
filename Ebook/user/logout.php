<?php
session_start();

// Clear admin session
unset($_SESSION['is_admin']);

// Destroy the entire session
session_destroy();

// Redirect back to books page
header('Location: index.php');
exit;