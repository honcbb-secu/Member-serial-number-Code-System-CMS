<?php
session_start();


// 清除 session 資訊
session_unset();
session_destroy();
header("Location: login.php");
exit; //結束,防止迴圈
?>
