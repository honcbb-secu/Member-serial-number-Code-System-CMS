<?php
require 'api/vendor/autoload.php';
require 'database.php';

session_start();

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

if ($user['otp_secret']) {
    // 如果已經設置了OTP，則重定向到驗證頁面
    header("Location: otp_check.php");
    exit();
}

// 若 session 中沒有 secret 則生成一個新的 secret
if (!isset($_SESSION['temp_secret'])) {
    $_SESSION['temp_secret'] = $ga->createSecret();
}

$qrCodeUrl = $ga->getQRCodeGoogleUrl($_SESSION['username'], $_SESSION['temp_secret']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($ga->verifyCode($_SESSION['temp_secret'], $_POST['otp'], 2)) {    
        // 如果OTP驗證成功，將secret儲存到資料庫並清除 session 中的 temp_secret
        $stmt = $pdo->prepare("UPDATE users SET otp_secret = ? WHERE username = ?");
        $stmt->execute([$_SESSION['temp_secret'], $_SESSION['username']]);
        unset($_SESSION['temp_secret']);
        header("Location: otp_check.php");
        exit();
    } else {
        // 如果OTP驗證失敗，提醒
        $otp_error = "錯誤的 OTP，請重新嘗試。";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<title>設置 OTP</title>
</head>
<body>
<center>
<h1>請掃描以下的QR Code以設置您的OTP：</h1>
<img src="<?= htmlspecialchars($qrCodeUrl) ?>"><br>
<?php
if ($otp_error) {
    echo '<p style="color:red;">' . $otp_error . '</p>';
}
?><br>
<form method="post" action="otp_setup.php">
    <label for="otp">請輸入 OTP：</label>
    <input type="text" id="otp" name="otp" required>
    <input type="submit" value="提交">
</form>
</center>
</body>
</html>
