<?php
session_start();
session_unset();
session_destroy();
header("Location: login.php");
exit; //結束,防止迴圈
?>
