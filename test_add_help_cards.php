<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once("modules/mysql.php");
$mysql = new MYSQL();

$id_gt = intval($_GET['id_gt'] ?? 0);
if (!$id_gt) {
    die("<h1>Ошибка: не указан id_gt!</h1>");
}

echo "<h1>Добавление карт помощи в руки для игры #$id_gt</h1>";

// 1. Получаем ID всех игроков в игре
$players_q = $mysql->sql_query("SELECT id_user FROM game_players WHERE id_gt = $id_gt");
if (mysqli_num_rows($players_q) == 0) {
    die("<p>В игре #$id_gt нет игроков.</p>");
}

// 2. Определяем карты для раздачи (шмотки, классы, расы и т.д.)
$cards_q = $mysql->sql_query("SELECT id_card, c_name FROM cards WHERE c_type IN ('u_class', 'race', 'item_head', 'item_arm', 'item_body', 'item_leg', 'item_all') ORDER BY RAND() LIMIT 20");
$cards_to_deal = [];
while ($card = mysqli_fetch_assoc($cards_q)) {
    $cards_to_deal[] = $card;
}

// 3. Раздаем карты по очереди каждому игроку
$player_index = 0;
$players = [];
while ($player = mysqli_fetch_assoc($players_q)) {
    $players[] = $player['id_user'];
}

foreach ($cards_to_deal as $card) {
    $current_player_id = $players[$player_index];
    $card_id = $card['id_card'];
    
    // Добавляем карту в руку
    $mysql->sql_query("INSERT INTO cards_of_user (id_user, id_card, id_gt, place_card) VALUES ($current_player_id, $card_id, $id_gt, 20)");
    echo "<p>Игроку #$current_player_id добавлена карта '{$card['c_name']}'.</p>";
    
    // Переходим к следующему игроку по кругу
    $player_index = ($player_index + 1) % count($players);
}


echo "<hr><h2>Карты розданы!</h2>";
echo "<a href='gamemenu.php?profile=join&id=$id_gt'>Вернуться в игру</a>";

?> 