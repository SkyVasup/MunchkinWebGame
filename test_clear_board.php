<?php
if (!isset($_SESSION)) {
    session_start();
}
header('Content-Type: text/html; charset=utf-8');
require_once("modules/mysql.php");
$mysql = new MYSQL();

$id_gt = intval($_GET['id_gt'] ?? 0);

if (!$id_gt) {
    die("<h1>Ошибка: не указан id_gt!</h1><p>Добавьте в адрес ?id_gt=ВАШ_ID_ИГРЫ</p>");
}

echo "<h1>Очистка стола для игры #$id_gt</h1>";
echo "<hr>";

// 1. Очистка карт на столе (монстры, проклятья и т.д.)
$mysql->sql_query("DELETE FROM cards_of_table WHERE id_gt = $id_gt");
echo "<p style='color: green;'>✓ Карты на столе очищены.</p>";

// 2. Очистка карт в руках у всех игроков этой игры
$mysql->sql_query("DELETE FROM cards_of_user WHERE id_gt = $id_gt");
echo "<p style='color: green;'>✓ Карты в руках игроков очищены.</p>";

// 3. Очистка надетых шмоток у всех игроков этой игры
// Сначала получаем ID всех игроков в данной игре
$player_ids_q = $mysql->sql_query("SELECT id_user FROM game_players WHERE id_gt = $id_gt");
$player_ids = [];
while($row = mysqli_fetch_assoc($player_ids_q)) {
    $player_ids[] = $row['id_user'];
}
if (!empty($player_ids)) {
    $in_clause = implode(',', $player_ids);
    $mysql->sql_query("DELETE FROM carried_items WHERE id_user IN ($in_clause)");
    echo "<p style='color: green;'>✓ Надетые шмотки очищены.</p>";
} else {
    echo "<p style='color: orange;'>- Шмотки не очищались (в игре нет игроков).</p>";
}

// 4. Очистка сброса для этой игры
$mysql->sql_query("DELETE FROM discards WHERE id_gt = $id_gt");
echo "<p style='color: green;'>✓ Сброс очищен.</p>";

// 5. Сброс состояния всех игроков в этой игре к начальному
$mysql->sql_query("UPDATE game_players SET level = 1, is_turn = 0, phase = 'start', door_opened = 0, treasures_to_draw = 0, race = NULL, class = NULL WHERE id_gt = $id_gt");

// 6. Назначение хода первому игроку (по ID)
$first_player_q = $mysql->sql_query("SELECT id_user FROM game_players WHERE id_gt = $id_gt ORDER BY id_user ASC LIMIT 1");
if ($first_player = mysqli_fetch_assoc($first_player_q)) {
    $mysql->sql_query("UPDATE game_players SET is_turn = 1 WHERE id_user = {$first_player['id_user']} AND id_gt = $id_gt");
    echo "<p style='color: green;'>✓ Состояние игроков сброшено, ход передан первому игроку (ID: {$first_player['id_user']}).</p>";
} else {
     echo "<p style='color: orange;'>- Ход не передан (в игре нет игроков).</p>";
}

echo "<hr>";
echo "<h2>Полная очистка завершена!</h2>";
echo "<a href='gamemenu.php?profile=join&id=$id_gt'>Вернуться в игру</a>";

?> 