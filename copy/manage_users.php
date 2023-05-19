<?php
require 'database.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    die("Access denied");
}

//生成token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();  // 存儲生成時間
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 9; // number of items per page
$offset = ($page > 1) ? ($page - 1) * $perPage : 0;

$stmt = $pdo->prepare("SELECT * FROM users LIMIT ? OFFSET ?");
$stmt->execute([$perPage, $offset]);
$users = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$totalUsers = $stmt->fetchColumn();

$pages = ceil($totalUsers / $perPage);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	
	//這裡先驗一下csrf_token
	if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Error");
    }
    $max_time = 60 * 60;  // token 過期時間 1 小時
    if (time() - $_SESSION['csrf_token_time'] > $max_time) {
        //die
		header("Location: logout.php");
    }
	
	
    if (isset($_POST['change_password'])) {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([password_hash($_POST['new_password'], PASSWORD_DEFAULT), $_POST['username']]);
    } elseif (isset($_POST['toggle_activation'])) {
        $stmt = $pdo->prepare("UPDATE users SET is_active = !is_active WHERE username = ?");
        $stmt->execute([$_POST['username']]);
        
        // After changes to the user's information, we fetch the users list again
        $stmt = $pdo->prepare("SELECT * FROM users LIMIT ? OFFSET ?");
        $stmt->execute([$perPage, $offset]);
        $users = $stmt->fetchAll();
    }
}

?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<title>管理使用者</title>
</head>
<body><center>
<table>
    <tr>
        <th>使用者</th>
        <th>狀態</th>
        <th>操作</th>
    </tr><tr><tr><tr><tr><tr><tr><tr><tr>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['username']) ?></td>
            <td><?= $user['is_active'] ? 'Yes' : 'No' ?></td>
            <td>
                <form method="post" action="manage_users.php" style="display: inline;">
                    <input type="hidden" name="username" value="<?= $user['username'] ?>">
                    <input type="text" name="new_password" required>
                    <input type="submit" name="change_password" value="修改密碼">
					<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                </form>
                <form method="post" action="manage_users.php" style="display: inline;">
                    <input type="hidden" name="username" value="<?= $user['username'] ?>">
                    <input type="submit" name="toggle_activation" value="<?= $user['is_active'] ? '禁用' : '啟用' ?>">
					<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                </form>
            </td>
        </tr><br>
		<!-- 可依照自己需求分格-->
    <?php endforeach; ?>
</table></br></br><input type="button" onclick="location.href='logout.php';" value="登出" /><br><br><br>
<div class="pagination">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <?php if ($i == $page): ?>
            <strong><?= $i ?></strong>  <!-- current page is shown as bold number -->
        <?php else: ?>
            <a href="?page=<?= $i ?>"><?= $i ?></a>  <!-- page links -->
        <?php endif; ?>
    <?php endfor; ?>
</div>
</center>
</body>
</html>
