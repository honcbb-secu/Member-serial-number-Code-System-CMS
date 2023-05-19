<?php
//生成一個64 字符的隨機string
$secret_key = bin2hex(openssl_random_pseudo_bytes(32));
echo $secret_key;
?>
