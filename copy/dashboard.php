<?php
require 'database.php';
// 設置 session 過期時間
$expireTime = 1800; // 30分鐘

// 開啟 session
ini_set('session.gc_maxlifetime', $expireTime);
session_set_cookie_params($expireTime);
session_start();

// 如果 session 已過期，清除 session
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $expireTime)) {
    session_unset();   
    session_destroy();  
}
$_SESSION['LAST_ACTIVITY'] = time(); //更新上次活動時間


//檢查使用者以及"狀態"
if (!isset($_SESSION['username']) || !$_SESSION['is_active']) {
    session_destroy();
    header("Location: login.php");
    exit;
}

//生成token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();  // 存儲生成時間
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$user = $stmt->fetch();

$msg = '';
$code_msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	 if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Error");
    }
    $max_time = 60 * 60;  // token 過期時間 1 小時
    if (time() - $_SESSION['csrf_token_time'] > $max_time) {
        //die
		header("Location: logout.php");
    }
	
    if (isset($_POST['code'])) {
        $code_input = $_POST['code'];

        // 搜尋序號
        $stmt = $pdo->prepare("SELECT * FROM codes WHERE code = ? AND is_active = 1");
        $stmt->execute([$code_input]);
        $code = $stmt->fetch();

        if ($code) {
            // 更新用戶的到期日期
            $new_expiration_date = $user['expiration_date'] ? strtotime($user['expiration_date']) : time();
            $new_expiration_date = date('Y-m-d', strtotime("+" . $code['duration'] . " days", $new_expiration_date));
            $stmt = $pdo->prepare("UPDATE users SET expiration_date = ? WHERE username = ?");
            $stmt->execute([$new_expiration_date, $_SESSION['username']]);

            // 更新序號的狀態和使用者
           $stmt = $pdo->prepare("UPDATE codes SET used_by = ?, is_active = 0, used_date = CURRENT_DATE WHERE code = ?");
           $stmt->execute([$_SESSION['username'], $code_input]);

            $msg = "序號已成功兌換!";
        } else {
            $msg = "無效的序號，或序號已被使用。";
        }
    } elseif ($_SESSION['is_admin'] && isset($_POST['duration'])) {
        $duration = $_POST['duration'];
        $code = bin2hex(random_bytes(10));

        $stmt = $pdo->prepare("INSERT INTO codes (code, duration) VALUES (?, ?)");
        $stmt->execute([$code, $duration]);

        $code_msg = "創建一組新的序號: " . $code;
    }
    
    // 重新獲取用戶信息以更新到期日期
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_SESSION['username']]);
    $user = $stmt->fetch();
}

?>


<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<title>主控台</title>
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

<form class="login100-form validate-form flex-sb flex-w" method="post" action="dashboard.php">
<span class="login100-form-title p-b-32">
<?php echo "Welcome, " . $_SESSION['username'] . "!<br>";?>
</span>

<span class="login100-form-title p-b-32">
<a href="logout.php">登出</a>
</span>

<span class="txt1 p-b-11">
<?php if ($user['expiration_date'] && strtotime($user['expiration_date']) > time()) {
    echo "您的會員有效期限至: " . $user['expiration_date'] . "<br>";
} else {
   // echo "您目前不是付費會員<br>";
}?>
</span></br></br>

<h2>
輸入序號：
<h2><br>

<div class="wrap-input100 validate-input m-b-36">
<input class="input100" type="text" id="code" name="code"  required>
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
<span class="focus-input100"></span>
</div>



<span class="txt1 p-b-11">
<?php
if ($msg) {
    echo '<p style="color:red;">' . $msg . '</p>';
}
?></span>

<br><br>

<div class="container-login100-form-btn">
<button type="submit" value="提交" class="login100-form-btn">
提交
</button><br>

<br>
</form>

<br><br>
<?php
if ($_SESSION['is_admin']) {
	$errorMsg = isset($code_msg) ? '<p style="color:red;">' . $code_msg . '</p>' : '';
    echo '<form method="post" action="dashboard.php">
	
        <h2>幾天的序號：</h2><br>
		<div class="wrap-input100 validate-input m-b-12">
        <span class="btn-show-pass">
        <i class="fa fa-eye"></i>
       </span>
       <input class="input100" type="number" id="duration" name="duration" required>
       <span class="focus-input100"></span>
       </div>
	   <input type="hidden" name="csrf_token" value="'.$_SESSION['csrf_token'].'"> 
	   </br>
       ' . $errorMsg . '</br>
        <div class="container-login100-form-btn">
        <button type="submit" value="提交" class="login100-form-btn">
       產生
      </button><br> 

    </form>';
	



}
?>

<div class="flex-sb-m w-full p-b-48">

<div>
<!--<a href="#" class="txt3">
Forgot Password?
</a>-->


</div>
</div>
</br>
</br></br>


</div></div></div>
</form>
</div>
</div>
</div>

