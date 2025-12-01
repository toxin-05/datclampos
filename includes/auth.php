<?php
// Check if user is logged in
if (!isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'index.php') {
    redirect('index.php');
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('index.php');
}
?>