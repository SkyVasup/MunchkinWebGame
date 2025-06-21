<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once("modules/mysql.php");
$mysql = new MYSQL();

$id_gt = intval($_GET['id_gt'] ?? 0);
$id_user_to_set = intval($_GET['id_user'] ?? 0);

if (!$id_gt || !$id_user_to_set) {
    die("<h1>Ошибка: не указаны id_gt или id_user!</h1><p>Добавьте в адрес, например: ?id_gt=1&id_user=123</p>");
}

// 1. Сбрасываем ход у ВСЕХ игроков в этой игре
$mysql->sql_query("UPDATE game_players SET is_turn = 0 WHERE id_gt = $id_gt");

// 2. Устанавливаем ход и начальную фазу конкретному игроку
$result = $mysql->sql_query("UPDATE game_players SET is_turn = 1, phase = 'start' WHERE id_gt = $id_gt AND id_user = $id_user_to_set");

echo "<h1>Передача хода для игры #$id_gt</h1>";
echo "<hr>";

if ($mysql->affected_rows() > 0) {
    echo "<p style='color: green;'>✓ Ход успешно передан игроку с ID: <b>$id_user_to_set</b>.</p>";
} else {
    echo "<p style='color: red;'>✗ Не удалось передать ход. Возможно, игрока с ID $id_user_to_set нет в этой игре.</p>";
}

echo "<hr>";
echo "<a href='gamemenu.php?profile=join&id=$id_gt'>Вернуться в игру</a>";

?> 