<?php
//session_start(); // Уже стартовала в global.php
require_once("global.php");
//require_once("modules/mysql.php"); // Уже есть в global.php

//$mysql = new MySQL(); // Используем глобальный $mysql
$id_user = $_SESSION['id_user'] ?? 0;

// Выбираем все столы, где есть свободные места и за которыми не сидит текущий пользователь.
// Это предотвратит отображение стола, за которым вы уже находитесь.
$query = "SELECT gt.*, 
          (SELECT GROUP_CONCAT(u.login SEPARATOR ', ') FROM users u WHERE u.id_gt = gt.id_gt) as players
          FROM game_tables gt
          WHERE gt.num_user < gt.limit_user 
          AND gt.id_gt NOT IN (SELECT id_gt FROM users WHERE id_user = {$id_user} AND id_gt IS NOT NULL)";

$result = $mysql->sql_query($query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "<p>Нет доступных игр для присоединения.</p>";
    exit;
}
?>
<table border="1" style="border-collapse: collapse; width: 100%;">
    <thead>
        <tr>
            <th>Название</th>
            <th>Игроки</th>
            <th>Мест</th>
            <th>Действие</th>
        </tr>
    </thead>
    <tbody>
    <?php
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>
            <td>" . htmlspecialchars($row['name']) . "</td>
            <td>" . htmlspecialchars($row['players'] ?? 'Пусто') . "</td>
            <td>{$row['num_user']} / {$row['limit_user']}</td>
            <td align='center'>
                <a href='join_table.php?id={$row['id_gt']}' title='Присоединиться'>
                    <img src='picture/joingame.png' alt='Присоединиться' style='width: 32px;'/>
                </a>
            </td>
        </tr>";
    }
    ?>
    </tbody>
</table>
