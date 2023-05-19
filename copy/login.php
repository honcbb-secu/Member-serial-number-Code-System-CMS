<?php
require 'database.php';
session_start();

// 檢查是否已經登入
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit;
}


//自定義
$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	// 先檢查user reCAPTCHA 狀態
	/*$recaptchaResponse = $_POST['g-recaptcha-response'];
    $secretKey = "6LdIeBImAAAAAI5oWMCObVVtuRzpngembGyPB7ey"; //reCAPTCHA 密鑰
    $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptchaResponse");
    $responseData = json_decode($response);
    if (!$responseData->success) {
        echo "reCAPTCHA verification failed. Please try again.";
        return;
    }*/
	
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['is_active'] = $user['is_active'];  // 新增這行
        header("Location: dashboard.php");
        exit; //防止重定向後出現迴圈錯誤
    } else {
        $error = "帳號或密碼錯誤";
    }
}


?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<title>登入</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="icon" type="image/png" href="images/icons/favicon.ico" />
<link rel="stylesheet" type="text/css" href="style/css/bootstrap/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="style/css/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" type="text/css" href="style/css/fonts/Linearicons-Free-v1.0.0/icon-font.min.css">
<link rel="stylesheet" type="text/css" href="style/css/animate.css">
<link rel="stylesheet" type="text/css" href="style/css/hamburgers.min.css">
<link rel="stylesheet" type="text/css" href="style/css/animsition.min.css">
<link rel="stylesheet" type="text/css" href="style/css/select2.min.css">
<link rel="stylesheet" type="text/css" href="style/css/daterangepicker.css">
<link rel="stylesheet" type="text/css" href="style/css/util.css">
<link rel="stylesheet" type="text/css" href="style/css/main.css">
<script src='https://www.google.com/recaptcha/api.js'></script>
</head>

<div class="limiter">
<div class="container-login100">
<div class="wrap-login100 p-l-85 p-r-85 p-t-55 p-b-55">
<form class="login100-form validate-form flex-sb flex-w" method="post" action="login.php">
<span class="login100-form-title p-b-32">
登入
</span>

<span class="txt1 p-b-11">
帳號：
</span>

<div class="wrap-input100 validate-input m-b-36" data-validate="Username is required">
<input class="input100" type="text" id="username" name="username" required>
<span class="focus-input100"></span>
</div>

<span class="txt1 p-b-11">
密碼：
</span>

<div class="wrap-input100 validate-input m-b-12" data-validate="Password is required">
<span class="btn-show-pass">
<i class="fa fa-eye"></i>
</span>

<input class="input100" id="password" type="password" name="password" required>
<span class="focus-input100"></span>
</div>
<div class="flex-sb-m w-full p-b-48">
<div class="contact100-form-checkbox">
<input class="input-checkbox100" id="ckb1" type="checkbox" name="remember-me">

</div>
<div>
<!--<a href="#" class="txt3">
Forgot Password?
</a>-->

<a href="register.php" class="txt3">
註冊
</a>

</div>
</div>
<?php
if ($error) {
    echo '<p style="color:red;">' . $error . '</p>';
}
?></br></br>
<!--<div class="g-recaptcha" style="transform: scale(0.9); -webkit-transform: scale(0.9); transform-origin: 0 0; -webkit-transform-origin: 0 0;" data-theme="light"  data-sitekey="6LdIeBImAAAAAOC0-oFpthyzpKiwdU7rQhToEDBD"></div></br></br>-->
<div class="container-login100-form-btn">
<button type="submit" value="Submit" class="login100-form-btn">
登入
</button>

</div>
</form>
</div>
</div>
</div>