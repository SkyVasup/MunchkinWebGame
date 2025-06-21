<?php
if (!isset($_SESSION)) {
    session_start();
}
header('Content-Type: application/json');

require_once("modules/mysql.php");
$mysql = new MYSQL();

$response = [
    'status' => 'error',
    'message' => 'Unknown error',
    'data' => null
];

$id_user = $_SESSION['id_user'] ?? null;
$id_gt = null;
if (isset($_GET['id_gt'])) {
    $id_gt = intval($_GET['id_gt']);
} elseif (isset($_SESSION['id_gt'])) {
    $id_gt = intval($_SESSION['id_gt']);
}

if (!$id_user || !$id_gt) {
    $response['message'] = 'User or game not found.';
    echo json_encode($response);
    exit;
}

// === ПОЛУЧЕНИЕ ДАННЫХ ===

// 1. Чей ход и текущая фаза
$turn_q = $mysql->sql_query("SELECT id_user, phase FROM game_players WHERE id_gt = $id_gt AND is_turn = 1");
$turn_info = mysqli_fetch_assoc($turn_q);

// FIX: Если ход никому не назначен, назначаем его первому игроку
if (!$turn_info) {
    $firstPlayerQ = $mysql->sql_query("SELECT id_user FROM game_players WHERE id_gt = $id_gt ORDER BY id_user ASC LIMIT 1");
    if ($firstPlayer = mysqli_fetch_assoc($firstPlayerQ)) {
        $firstPlayerId = $firstPlayer['id_user'];
        $mysql->sql_query("UPDATE game_players SET is_turn = 1, phase = 'start' WHERE id_gt = $id_gt AND id_user = " . $firstPlayerId);
        // Повторно запрашиваем инфо о ходе, чтобы отправить клиенту актуальные данные
        $turn_q = $mysql->sql_query("SELECT id_user, phase FROM game_players WHERE id_gt = $id_gt AND is_turn = 1");
        $turn_info = mysqli_fetch_assoc($turn_q);
    }
}

// 2. Все игроки и их данные
$players_data = [];
$players_q = $mysql->sql_query("SELECT id_user, login, level, race, class, is_turn FROM game_players WHERE id_gt = $id_gt ORDER BY id_user ASC");
while ($player = mysqli_fetch_assoc($players_q)) {
    $hand_cards_data = [];
    if ($player['id_user'] == $id_user) {
        // Для текущего игрока отправляем полную информацию о картах
        $hand_cards_q = $mysql->sql_query("SELECT c.id_card, c.pic, c.c_name FROM cards_of_user cou JOIN cards c ON cou.id_card = c.id_card WHERE cou.id_gt = $id_gt AND cou.id_user = {$player['id_user']} AND cou.place_card = 20");
        while($card = mysqli_fetch_assoc($hand_cards_q)) {
            $hand_cards_data[] = $card;
        }
    } else {
        // Для других игроков - только количество карт
        $hand_cards_q = $mysql->sql_query("SELECT COUNT(*) as cnt FROM cards_of_user WHERE id_gt = $id_gt AND id_user = {$player['id_user']} AND place_card = 20");
        $count_result = mysqli_fetch_assoc($hand_cards_q);
        $hand_cards_data = ['count' => $count_result['cnt']];
    }
    
    // Надетые шмотки и подсчет силы
    $carried_items_q = $mysql->sql_query("SELECT c.id_card, c.pic, c.c_name, c.c_force FROM carried_items ci JOIN cards c ON ci.id_card = c.id_card WHERE ci.id_user = {$player['id_user']}");
    $carried_items = [];
    $bonus_strength = 0;
    while($item = mysqli_fetch_assoc($carried_items_q)) {
        $carried_items[] = $item;
        if (isset($item['c_force'])) {
            $bonus_strength += intval($item['c_force']);
        }
    }

    $players_data[] = [
        'id_user' => $player['id_user'],
        'login' => $player['login'],
        'level' => $player['level'],
        'combat_strength' => intval($player['level']) + $bonus_strength,
        'race' => $player['race'],
        'class' => $player['class'],
        'is_turn' => $player['is_turn'] == 1,
        'hand' => $hand_cards_data,
        'items' => $carried_items
    ];
}

// 3. Карты на столе
$table_cards_q = $mysql->sql_query("SELECT c.id_card, c.pic, c.c_name, c.c_type, c.c_force, cot.place_card FROM cards_of_table cot JOIN cards c ON cot.id_card = c.id_card WHERE cot.id_gt = $id_gt");
$monster_cards = [];
$help_cards = [];
while ($card = mysqli_fetch_assoc($table_cards_q)) {
    if ($card['place_card'] == 10) { // Монстры
        $monster_cards[] = $card;
    } elseif ($card['place_card'] == 11) { // Карты помощи
        $help_cards[] = $card;
    }
}

// 4. Количество карт в колодах и сбросах
$door_deck_q = $mysql->sql_query("SELECT COUNT(*) as cnt FROM cards_of_door WHERE id_gt = $id_gt");
$door_deck_count = mysqli_fetch_assoc($door_deck_q)['cnt'];

$loot_deck_q = $mysql->sql_query("SELECT COUNT(*) as cnt FROM cards_of_loot WHERE id_gt = $id_gt");
$loot_deck_count = mysqli_fetch_assoc($loot_deck_q)['cnt'];

$door_discard_q = $mysql->sql_query("SELECT COUNT(d.id_discard) as cnt FROM discards d JOIN cards c ON d.id_card = c.id_card WHERE d.id_gt = $id_gt AND c.card_type = 'door'");
$door_discard_count = mysqli_fetch_assoc($door_discard_q)['cnt'];

$loot_discard_q = $mysql->sql_query("SELECT COUNT(d.id_discard) as cnt FROM discards d JOIN cards c ON d.id_card = c.id_card WHERE d.id_gt = $id_gt AND c.card_type = 'loot'");
$loot_discard_count = mysqli_fetch_assoc($loot_discard_q)['cnt'];


// === СБОРКА ОТВЕТА ===
$response['status'] = 'success';
$response['message'] = 'Game state updated.';
$response['data'] = [
    'current_player_id' => $id_user,
    'turn_info' => [
        'active_player_id' => $turn_info ? $turn_info['id_user'] : null,
        'phase' => $turn_info ? $turn_info['phase'] : 'start'
    ],
    'players' => $players_data,
    'table_cards' => [
        'monster_cards' => $monster_cards,
        'help_cards' => $help_cards
    ],
    'decks' => [
        'door_count' => $door_deck_count,
        'loot_count' => $loot_deck_count
    ],
    'discards' => [
        'door_count' => $door_discard_count,
        'loot_count' => $loot_discard_count
    ]
];

echo json_encode($response);
exit; 