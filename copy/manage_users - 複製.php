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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['change_password'])) {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([password_hash($_POST['new_password'], PASSWORD_DEFAULT), $_POST['username']]);
    } elseif (isset($_POST['toggle_activation'])) {
        $stmt = $pdo->prepare("UPDATE users SET is_active = !is_active WHERE username = ?");
        $stmt->execute([$_POST['username']]);
    }
}

$stmt = $pdo->prepare("SELECT * FROM users");
$stmt->execute();
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<body>
    <table>
        <tr>
            <th>Username</th>
            <th>Is Active</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= $user['is_active'] ? 'Yes' : 'No' ?></td>
                <td>
                    <form method="post" action="manage_users.php" style="display: inline;">
                        <input type="hidden" name="username" value="<?= $user['username'] ?>">
                        <input type="text" name="new_password" required>
                        <input type="submit" name="change_password" value="Change Password">
                    </form>
                    <form method="post" action="manage_users.php" style="display: inline;">
                        <input type="hidden" name="username" value="<?= $user['username'] ?>">
                        <input type="submit" name="toggle_activation" value="<?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>">
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
