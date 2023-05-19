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

$editing = null;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
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

$stmt = $pdo->prepare("SELECT * FROM codes");
$stmt->execute();
$codes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<body>
    <?php if ($editing): ?>
        <form method="post" action="manage_codes.php">
            <input type="hidden" name="save" value="<?= $editing['id'] ?>">
            <label for="code">Code:</label><br>
            <input type="text" id="code" name="code" value="<?= htmlspecialchars($editing['code']) ?>" required><br>
            <label for="duration">Duration:</label><br>
            <input type="text" id="duration" name="duration" value="<?= htmlspecialchars($editing['duration']) ?>" required><br>
            <input type="submit" value="Save">
        </form>
    <?php else: ?>
        <table>
            <tr>
                <th>Code</th>
                <th>Duration</th>
                <th>Used By</th>
                <th>Is Active</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($codes as $code): ?>
                <tr>
                    <td><?= htmlspecialchars($code['code']) ?></td>
                    <td><?= htmlspecialchars($code['duration']) ?></td>
                    <td><?= htmlspecialchars($code['used_by']) ?></td>
                    <td><?= $code['is_active'] ? 'Yes' : 'No' ?></td>
                    <td>
                        <form method="post" action="manage_codes.php" style="display: inline;">
                            <input type="hidden" name="toggle" value="<?= $code['id'] ?>">
                            <input type="submit" value="<?= $code['is_active'] ? 'Deactivate' : 'Activate' ?>">
                        </form>
                        <form method="post" action="manage_codes.php" style="display: inline;">
                            <input type="hidden" name="edit" value="<?= $code['id'] ?>">
                            <input type="submit" value="Edit">
                        </form>
                        <form method="post" action="manage_codes.php" style="display: inline;">
                            <input type="hidden" name="delete" value="<?= $code['id'] ?>">
                            <input type="submit" value="Delete">
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>

