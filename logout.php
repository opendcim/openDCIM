<?php
session_start();

session_unset();
$_SESSION = array();
unset($_SESSION["userid"]);
session_destroy();
session_commit();

$loginPage = true;

include('db.inc.php');
include('facilities.inc.php');

header("Location: ".redirect('index.php'));
exit;
?>
