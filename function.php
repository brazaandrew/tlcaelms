<?php
function logoutUser() {
    session_start();
    session_unset();
    session_destroy();
    header("Location: login.PHP"); // or wherever you want to redirect
    exit;
}
?>