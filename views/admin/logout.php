<?php
/**
 * logout handler
 */
session_start();

// clear session variables
$_SESSION = [];

// clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// kill session
session_destroy();

// send to login
header("Location: ../../login.php");
exit;
