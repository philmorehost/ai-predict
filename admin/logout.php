<?php
require_once '../includes/session_helper.php';
session_destroy();
header('Location: index.php');
exit;
?>
