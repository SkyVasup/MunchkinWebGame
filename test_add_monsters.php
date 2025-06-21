<?php
session_start();
require_once("modules/mysql.php");
$mysql = new MYSQL();

// Тестовые данные
$id_gt = isset($_GET['id_gt']) ? intval($_GET['id_gt']) : 1; // ID игры из URL или по умолчанию
$test_monsters = [7, 83, 53]; // ID монстров для добавления

echo "<h2>Добавление монстров в руки игроков для игры #{$id_gt}</h2>";

// Получаем всех игроков в игре
$players_q = $mysql->sql_query("SELECT id_user, login FROM game_players WHERE id_gt = $id_gt");
$players = [];
while ($player = mysqli_fetch_assoc($players_q)) {
    $players[] = $player;
}

if (empty($players)) {
    echo "<p style='color: red;'>Ошибка: Нет игроков в игре!</p>";
    exit;
}

echo "<p>Найдено игроков: " . count($players) . "</p>";

// Добавляем монстров каждому игроку
foreach ($players as $player) {
    echo "<h3>Игрок: {$player['login']} (ID: {$player['id_user']})</h3>";
    
    foreach ($test_monsters as $monster_id) {
        // Проверяем, есть ли уже эта карта у игрока
        $existing_q = $mysql->sql_query("SELECT COUNT(*) as count FROM cards_of_user 
                                         WHERE id_user = {$player['id_user']} AND id_gt = $id_gt 
                                         AND id_card = $monster_id");
        $existing = mysqli_fetch_assoc($existing_q);
        
        if ($existing['count'] == 0) {
            // Добавляем монстра в руку
            $mysql->sql_query("INSERT INTO cards_of_user (id_user, id_gt, id_card, place_card) 
                             VALUES ({$player['id_user']}, $id_gt, $monster_id, 20)");
            
            // Получаем название монстра
            $monster_name_q = $mysql->sql_query("SELECT c_name FROM cards WHERE id_card = $monster_id");
            $monster_name = mysqli_fetch_assoc($monster_name_q);
            echo "<p style='color: green;'>✓ Добавлен монстр: {$monster_name['c_name']} (ID: $monster_id)</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Монстр с ID $monster_id уже есть у игрока</p>";
        }
    }
}

echo "<hr>";
echo "<h3>Проверка карт в руках игроков:</h3>";

foreach ($players as $player) {
    echo "<h4>{$player['login']}:</h4>";
    $cards_q = $mysql->sql_query("SELECT c.id_card, c.c_name, c.c_type FROM cards_of_user cou 
                                 JOIN cards c ON cou.id_card = c.id_card 
                                 WHERE cou.id_user = {$player['id_user']} AND cou.id_gt = $id_gt 
                                 AND cou.place_card = 20");
    
    $card_count = 0;
    while ($card = mysqli_fetch_assoc($cards_q)) {
        echo "<p>- {$card['c_name']} ({$card['c_type']}, ID: {$card['id_card']})</p>";
        $card_count++;
    }
    
    if ($card_count == 0) {
        echo "<p style='color: red;'>Нет карт в руке!</p>";
    }
}

echo "<hr>";
echo "<p><a href='gamemenu.php?profile=join&id=$id_gt' target='_blank'>Открыть игру для тестирования</a></p>";
?> 