<?php
require 'api/vendor/autoload.php';
require 'database.php';

session_start();

//自定義
$otp_error = '';

$ga = new \PHPGangsta_GoogleAuthenticator();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    //die("權限不足");, ,以防直接太明顯被知道（安全資訊洩漏問題）
	header('HTTP/1.1 404 Not Found');
	exit();
}

$stmt = $pdo->prepare("SELECT otp_secret FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$user = $stmt->fetch();

if (!$user['otp_secret']) {
    // 如果還沒有設置 OTP，就重定向到 OTP 設置頁面
    header("Location: otp_setup.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($ga->verifyCode($user['otp_secret'], $_POST['otp'], 2)) {    // 2 = 2*30sec clock tolerance
        $_SESSION['otp_verified'] = true;
        if (isset($_SESSION['redirect_url'])) {
            $redirect_url = $_SESSION['redirect_url'];
            unset($_SESSION['redirect_url']); 
            header("Location: " . $redirect_url);
        } else {
            header("Location: /");  // 假設在session沒有找到url ，就默認
        }
        exit();
    } else {
        //echo "錯誤的 OTP，請重新嘗試。";
        $otp_error = "錯誤的 OTP，請重新嘗試。";
    }
}

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<title>OTP 驗證</title>
</head>
<body>
<center>
<h1>請輸入您的 OTP：</h1>
<br>
<?php
if ($otp_error) {
    echo '<p style="color:red;">' . $otp_error . '</p>';
}
?><br><br>
<form method="post">
    <input type="text" name="otp" required>
    <input type="submit" value="提交">
</form></center>
</body>
</html>
