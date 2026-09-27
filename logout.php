<?php
require_once __DIR__ . '/hms.php';
hms_start_session();
session_unset();
session_destroy();
hms_redirect('index.php');
?>
