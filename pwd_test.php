<?php
if (isset($_GET['pwd'])) {
    $pwd = $_GET['pwd'];
    $hash = password_hash($pwd, PASSWORD_DEFAULT);

    echo $hash;
} else {
}
?>
