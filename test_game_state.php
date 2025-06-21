<?php
session_start();
require_once("modules/mysql.php");
$mysql = new MYSQL();

$id_gt = isset($_GET['id_gt']) ? intval($_GET['id_gt']) : 1; // ID игры из URL или по умолчанию

echo "<h2>Проверка состояния игры #{$id_gt}</h2>";

// 1. Проверяем игроков
echo "<h3>1. Игроки в игре:</h3>";
$players_q = $mysql->sql_query("SELECT id_user, login, level, is_turn, phase FROM game_players WHERE id_gt = $id_gt ORDER BY id_user ASC");
$players = [];
while ($player = mysqli_fetch_assoc($players_q)) {
    $players[] = $player;
    $turn_status = $player['is_turn'] ? " (АКТИВНЫЙ)" : "";
    echo "<p><b>{$player['login']}</b> - Уровень: {$player['level']}, Фаза: {$player['phase']}{$turn_status}</p>";
}

if (empty($players)) {
    echo "<p style='color: red;'>Ошибка: Нет игроков в игре!</p>";
    exit;
}

// 2. Проверяем карты на столе
echo "<h3>2. Карты на столе:</h3>";
$table_cards_q = $mysql->sql_query("SELECT c.id_card, c.c_name, c.c_type, c.c_force, cot.place_card 
                                   FROM cards_of_table cot 
                                   JOIN cards c ON cot.id_card = c.id_card 
                                   WHERE cot.id_gt = $id_gt 
                                   ORDER BY cot.place_card ASC");
$monster_cards = [];
$help_cards = [];
while ($card = mysqli_fetch_assoc($table_cards_q)) {
    if ($card['place_card'] == 10) {
        $monster_cards[] = $card;
    } elseif ($card['place_card'] == 11) {
        $help_cards[] = $card;
    }
}

echo "<h4>Монстры на столе (place_card = 10):</h4>";
if (empty($monster_cards)) {
    echo "<p style='color: orange;'>Нет монстров на столе</p>";
} else {
    foreach ($monster_cards as $card) {
        $strength = $card['c_force'] ? " (Сила: {$card['c_force']})" : "";
        echo "<p>- {$card['c_name']} ({$card['c_type']}){$strength}</p>";
    }
}

echo "<h4>Карты помощи на столе (place_card = 11):</h4>";
if (empty($help_cards)) {
    echo "<p style='color: orange;'>Нет карт помощи на столе</p>";
} else {
    foreach ($help_cards as $card) {
        echo "<p>- {$card['c_name']} ({$card['c_type']})</p>";
    }
}

// 3. Проверяем карты в руках игроков
echo "<h3>3. Карты в руках игроков:</h3>";
foreach ($players as $player) {
    echo "<h4>{$player['login']}:</h4>";
    
    $hand_cards_q = $mysql->sql_query("SELECT c.id_card, c.c_name, c.c_type, c.c_force 
                                      FROM cards_of_user cou 
                                      JOIN cards c ON cou.id_card = c.id_card 
                                      WHERE cou.id_user = {$player['id_user']} AND cou.id_gt = $id_gt 
                                      AND cou.place_card = 20 
                                      ORDER BY c.c_type ASC");
    
    $monsters = [];
    $classes = [];
    $races = [];
    $items = [];
    $others = [];
    
    while ($card = mysqli_fetch_assoc($hand_cards_q)) {
        switch ($card['c_type']) {
            case 'monster':
                $monsters[] = $card;
                break;
            case 'u_class':
                $classes[] = $card;
                break;
            case 'race':
                $races[] = $card;
                break;
            case 'item':
                $items[] = $card;
                break;
            default:
                $others[] = $card;
                break;
        }
    }
    
    if (!empty($monsters)) {
        echo "<p><b>Монстры:</b></p>";
        foreach ($monsters as $card) {
            $strength = $card['c_force'] ? " (Сила: {$card['c_force']})" : "";
            echo "<p style='margin-left: 20px;'>- {$card['c_name']} (ID: {$card['id_card']}){$strength}</p>";
        }
    }
    
    if (!empty($classes)) {
        echo "<p><b>Классы:</b></p>";
        foreach ($classes as $card) {
            echo "<p style='margin-left: 20px;'>- {$card['c_name']} (ID: {$card['id_card']})</p>";
        }
    }
    
    if (!empty($races)) {
        echo "<p><b>Расы:</b></p>";
        foreach ($races as $card) {
            echo "<p style='margin-left: 20px;'>- {$card['c_name']} (ID: {$card['id_card']})</p>";
        }
    }
    
    if (!empty($items)) {
        echo "<p><b>Шмотки:</b></p>";
        foreach ($items as $card) {
            $strength = $card['c_force'] ? " (Бонус: {$card['c_force']})" : "";
            echo "<p style='margin-left: 20px;'>- {$card['c_name']} (ID: {$card['id_card']}){$strength}</p>";
        }
    }
    
    if (!empty($others)) {
        echo "<p><b>Другие:</b></p>";
        foreach ($others as $card) {
            echo "<p style='margin-left: 20px;'>- {$card['c_name']} ({$card['c_type']}, ID: {$card['id_card']})</p>";
        }
    }
    
    if (empty($monsters) && empty($classes) && empty($races) && empty($items) && empty($others)) {
        echo "<p style='color: red;'>Нет карт в руке!</p>";
    }
}

// 4. Проверяем экипировку игроков
echo "<h3>4. Экипировка игроков:</h3>";
foreach ($players as $player) {
    echo "<h4>{$player['login']}:</h4>";
    
    $equipment_q = $mysql->sql_query("SELECT c.id_card, c.c_name, c.c_force 
                                     FROM carried_items ci 
                                     JOIN cards c ON ci.id_card = c.id_card 
                                     WHERE ci.id_user = {$player['id_user']}");
    
    $equipment = [];
    $total_bonus = 0;
    while ($item = mysqli_fetch_assoc($equipment_q)) {
        $equipment[] = $item;
        if ($item['c_force']) {
            $total_bonus += intval($item['c_force']);
        }
    }
    
    if (empty($equipment)) {
        echo "<p style='color: orange;'>Нет экипировки</p>";
    } else {
        foreach ($equipment as $item) {
            $bonus = $item['c_force'] ? " (Бонус: {$item['c_force']})" : "";
            echo "<p style='margin-left: 20px;'>- {$item['c_name']} (ID: {$item['id_card']}){$bonus}</p>";
        }
        echo "<p><b>Общий бонус к силе: $total_bonus</b></p>";
    }
}

echo "<hr>";
echo "<h3>Ссылки для тестирования:</h3>";
echo "<p><a href='test_add_monsters.php' target='_blank'>Добавить монстров в руки</a></p>";
echo "<p><a href='test_add_help_cards.php' target='_blank'>Добавить карты помощи</a></p>";
echo "<p><a href='gamemenu.php?profile=join&id=$id_gt' target='_blank'>Открыть игру</a></p>";
?> 