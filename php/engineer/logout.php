<?php
session_start();
session_unset();
session_destroy();
header("Location: ../../login/login_engineer.html");
exit();
?>
