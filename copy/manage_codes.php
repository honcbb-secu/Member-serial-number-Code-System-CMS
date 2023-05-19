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

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 9; // number of items per page
$offset = ($page > 1) ? ($page - 1) * $perPage : 0;

$stmt = $pdo->prepare("SELECT * FROM codes LIMIT ? OFFSET ?");
$stmt->execute([$perPage, $offset]);
$codes = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM codes");
$stmt->execute();
$totalCodes = $stmt->fetchColumn();

$pages = ceil($totalCodes / $perPage);

$editing = null;
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
	
    if (isset($_POST['delete'])) {
        // 先獲取序號
        $stmt = $pdo->prepare("SELECT * FROM codes WHERE id = ?");
        $stmt->execute([$_POST['delete']]);
        $code = $stmt->fetch();

        // 假設使用了其他序號，則應將這些序號重新計算並更新
        if ($code && $code['used_by']) {
            // 計算新的到期日期
            $stmt = $pdo->prepare("
                SELECT DATE_ADD(MIN(used_date), INTERVAL SUM(duration) DAY) as new_expiration_date
                FROM codes
                WHERE used_by = ? AND id <> ? AND is_active = 0
            ");
            $stmt->execute([$code['used_by'], $code['id']]);
            $new_expiration_date = $stmt->fetchColumn();

            if ($new_expiration_date) {
                $stmt = $pdo->prepare("UPDATE users SET expiration_date = ? WHERE username = ?");
                $stmt->execute([$new_expiration_date, $code['used_by']]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET expiration_date = NULL WHERE username = ?");
                $stmt->execute([$code['used_by']]);
            }
        }

        // 刪除序號
        $stmt = $pdo->prepare("DELETE FROM codes WHERE id = ?");
        $stmt->execute([$_POST['delete']]);
    } elseif (isset($_POST['toggle'])) {
        $stmt = $pdo->prepare("UPDATE codes SET is_active = !is_active WHERE id = ?");
        $stmt->execute([$_POST['toggle']]);
    } elseif (isset($_POST['edit'])) {
        $stmt = $pdo->prepare("SELECT * FROM codes WHERE id = ?");
        $stmt->execute([$_POST['edit']]);
        $editing = $stmt->fetch();
    } elseif (isset($_POST['save'])) {
    // 先獲取序號
    $stmt = $pdo->prepare("SELECT * FROM codes WHERE id = ?");
    $stmt->execute([$_POST['save']]);
    $code = $stmt->fetch();

    // 更新序號
    $stmt = $pdo->prepare("UPDATE codes SET code = ?, duration = ? WHERE id = ?");
    $stmt->execute([$_POST['code'], $_POST['duration'], $_POST['save']]);

    // 如果序號已被使用，則重新計算使用者的到期日期
    if ($code && $code['used_by']) {
        // 計算新的到期日期
        $stmt = $pdo->prepare("
            SELECT DATE_ADD(MIN(used_date), INTERVAL SUM(duration) DAY) as new_expiration_date
            FROM codes
            WHERE used_by = ? AND is_active = 0
        ");
        $stmt->execute([$code['used_by']]);
        $new_expiration_date = $stmt->fetchColumn();

        // 更新使用者的到期日期
        if ($new_expiration_date) {
            $stmt = $pdo->prepare("UPDATE users SET expiration_date = ? WHERE username = ?");
            $stmt->execute([$new_expiration_date, $code['used_by']]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET expiration_date = NULL WHERE username = ?");
            $stmt->execute([$code['used_by']]);
        }
    }
}
}


?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<title>管理序號</title>
</head>
<body>
<center>
    <?php if ($editing): ?>
        <form method="post" action="manage_codes.php">
            <input type="hidden" name="save" value="<?= $editing['id'] ?>">
			<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <label for="code">序號:</label><br>
            <input type="text" id="code" name="code" value="<?= htmlspecialchars($editing['code']) ?>" required><br>
            <label for="duration">天數:</label><br>
            <input type="text" id="duration" name="duration" value="<?= htmlspecialchars($editing['duration']) ?>" required><br>
            <input type="submit" value="Save">
        </form>
    <?php else: ?>
        <table>
            <tr>
                <th>序號</th>
                <th>使用狀態</th>
                <th>使用者</th>
                <th>激活狀態</th>
                <th>操作</th>
            </tr><tr><tr><tr><tr><tr><tr><tr><tr>
            <?php foreach ($codes as $code): ?>
                <tr>
                    <td><?= htmlspecialchars($code['code']) ?></td>
                    <td><?= htmlspecialchars($code['duration']) ?></td>
                    <td><?= htmlspecialchars($code['used_by']) ?></td>
                    <td><?= $code['is_active'] ? 'Yes' : 'No' ?></td>
                    <td>
                        <form method="post" action="manage_codes.php" style="display: inline;">
                            <input type="hidden" name="toggle" value="<?= $code['id'] ?>">
							<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="submit" value="<?= $code['is_active'] ? '禁用' : '啟用' ?>">
                        </form>
                        <form method="post" action="manage_codes.php" style="display: inline;">
                            <input type="hidden" name="edit" value="<?= $code['id'] ?>">
							<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="submit" value="編輯">
                        </form>
                        <form method="post" action="manage_codes.php" style="display: inline;">
                            <input type="hidden" name="delete" value="<?= $code['id'] ?>">
							<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="submit" value="刪除">
                        </form>
                    </td>
                </tr>
				<tr><td colspan="5">&nbsp;</td></tr>  
            <?php endforeach; ?>
        </table></br></br><input type="button" onclick="location.href='logout.php';" value="登出" /><br><br><br>
        <div class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <?php if ($i == $page): ?>
                    <strong><?= $i ?></strong> 
                <?php else: ?>
                    <a href="?page=<?= $i ?>"><?= $i ?></a> <!--頁面連結-->
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
	</center>
</body>
</html>


