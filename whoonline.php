<?php
require_once("global.php");
require_once("modules/mysql.php");

// Получаем текущее время и интервал активности (180 секунд)
$now = time();
$active_since = $now - 180;

// Запрос активных пользователей
$result = $mysql->sql_query("SELECT login, active FROM users WHERE active >= $active_since ORDER BY login ASC");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Кто онлайн</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <table class="maintable" border="0">
        <tr>
            <td colspan="2" align="center">
                <h2>Пользователи онлайн</h2>
                <hr>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <table class="authtable">
                        <tr><td><strong>Логин</strong></td><td><strong>Последняя активность</strong></td></tr>
                        <?php while ($user = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['login']) ?></td>
                                <td><?= date("H:i:s", $user['active']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <p class="statstr">Сейчас никто не онлайн.</p>
                <?php endif; ?>
                <br>
                <a class="topmenu" href="index.php">Назад на главную</a>
            </td>
        </tr>
    </table>
</body>
</html>
