<?php
//生成一個64 字符的隨機string

if (isset($_GET['secret_key'])) {
    $secret_key = $_GET['secret_key'];
	
$secret_key = bin2hex(openssl_random_pseudo_bytes(32));
echo $secret_key;
}else {}
?>
