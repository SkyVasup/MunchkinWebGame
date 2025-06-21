<?php
/*
if (!isset($_SESSION)) {
    session_start();
}
*/

require_once("global.php"); // Теперь этот файл создает $mysql и стартует сессию
//require_once("modules/mysql.php"); // Уже подключен в global.php
//$mysql = new MYSQL(); // Уже создан в global.php

$id_user = $_SESSION['id_user'] ?? null;
$login = $_SESSION['login'] ?? null;

// Пытаемся определить id_gt из сессии или GET-параметра
$id_gt = null;
if (isset($_GET['id'])) {
    $id_gt = intval($_GET['id']);
    $_SESSION['id_gt'] = $id_gt; // Сохраняем в сессию для последующих запросов
} elseif (isset($_SESSION['id_gt'])) {
    $id_gt = intval($_SESSION['id_gt']);
}

// Проверяем, в игре ли пользователь.
// Сначала ищем в сессии, потом в базе данных (на случай, если сессия слетела).
if (!$id_gt && $id_user) {
    $user_game_q = $mysql->sql_query("SELECT id_gt FROM users WHERE id_user = $id_user AND id_gt IS NOT NULL AND id_gt > 0");
    if ($user_game = mysqli_fetch_assoc($user_game_q)) {
        $id_gt = $user_game['id_gt'];
        $_SESSION['id_gt'] = $id_gt;
    }
}

// Если после всех проверок мы не нашли игру для пользователя, показываем лобби
if (!$id_gt || !$id_user) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Игровое меню - Munchkin Online</title>
        <link href="style.css" rel="stylesheet" type="text/css" />
        <script src="js/jquery-1.4.4.min.js"></script>
        <script>
            $(document).ready(function(){
                function updateGameList() {
                    $('#game-list-container').load('table_list.php', function(response, status, xhr) {
                        if (status == "error") {
                            $("#game-list-container").html("Ошибка загрузки списка игр: " + xhr.status + " " + xhr.statusText);
                        }
                    });
                }
                updateGameList();
                setInterval(updateGameList, 5000); // Обновлять каждые 5 секунд

                $('#create-table-form').submit(function(e){
                    e.preventDefault();
                    $.post('create_table.php', $(this).serialize(), function(response){
                        // Просто перезагружаем страницу, чтобы войти в созданную игру
                        location.reload();
                    }).fail(function(){
                        alert('Ошибка при создании стола.');
                    });
                });
            });
        </script>
    </head>
    <body>
        <div class="main-container" style="padding: 20px;">
            <h1>Игровое меню</h1>
            <p>Вы не состоите в игре. Создайте новую или присоединитесь к существующей.</p>
            <a href="index.php">Вернуться на главную</a> | <a href="logout.php">Выйти</a>
            
            <div class="lobby" style="display: flex; justify-content: space-around; padding: 20px; margin-top: 20px;">
                <div class="create-table" style="width: 45%;">
                    <h2>Создать новый стол</h2>
                    <form id="create-table-form" style="background: #f2f2f2; padding: 15px; border-radius: 5px;">
                        <p>
                            <label for="table_name">Название стола:</label><br>
                            <input type="text" id="table_name" name="table_name" required style="width: 95%;">
                        </p>
                        <p>
                            <label for="limit_user">Макс. игроков (3-6):</label><br>
                            <input type="number" id="limit_user" name="num_user" min="3" max="6" value="6" required>
                        </p>
                        <p>
                            <label for="num_time">Время на ход (в секундах):</label><br>
                            <select name="num_time" id="num_time">
                                <option value="180">3 минуты</option>
                                <option value="300" selected>5 минут</option>
                                <option value="600">10 минут</option>
                            </select>
                        </p>
                        <button type="submit">Создать стол</button>
                    </form>
                </div>

                <div class="table-list" style="width: 50%;">
                    <h2>Доступные игры</h2>
                    <div id="game-list-container">
                        <p>Загрузка...</p>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit(); // Важно: останавливаем выполнение скрипта, чтобы не показывать игровую логику
}

// ===================================================================
// ЕСЛИ ПОЛЬЗОВАТЕЛЬ В ИГРЕ - ПОКАЗЫВАЕМ ИГРОВОЙ ИНТЕРФЕЙС
// ===================================================================

// === ФУНКЦИИ ДЛЯ РАБОТЫ С ФАЗАМИ ХОДА ===
function setPhase($mysql, $id_gt, $id_user, $phase) {
    $mysql->sql_query("UPDATE game_players SET phase = '".addslashes($phase)."' WHERE id_gt = $id_gt AND id_user = $id_user");
}
function getPhase($mysql, $id_gt, $id_user) {
    $q = $mysql->sql_query("SELECT phase FROM game_players WHERE id_gt = $id_gt AND id_user = $id_user");
    $row = mysqli_fetch_assoc($q);
    return $row ? $row['phase'] : 'start';
}
function passTurn($mysql, $id_gt, $current_user_id) {
    // Сбрасываем флаг хода у текущего игрока
    $mysql->sql_query("UPDATE game_players SET is_turn = 0, door_opened = 0, phase = 'start' WHERE id_gt = $id_gt AND id_user = $current_user_id");
    // Ищем следующего игрока
    $nextPlayerQ = $mysql->sql_query("SELECT id_user FROM game_players WHERE id_gt = $id_gt AND id_user > $current_user_id ORDER BY id_user ASC LIMIT 1");
    if ($next = mysqli_fetch_assoc($nextPlayerQ)) {
        // Устанавливаем флаг хода и НАЧАЛЬНУЮ ФАЗУ для следующего игрока
        $mysql->sql_query("UPDATE game_players SET is_turn = 1, phase = 'start' WHERE id_gt = $id_gt AND id_user = {$next['id_user']}");
    } else {
        // Если текущий был последним, передаём ход первому
        $restartQ = $mysql->sql_query("SELECT id_user FROM game_players WHERE id_gt = $id_gt ORDER BY id_user ASC LIMIT 1");
        if ($restart = mysqli_fetch_assoc($restartQ)) {
            // Устанавливаем флаг хода и НАЧАЛЬНУЮ ФАЗУ для первого игрока
            $mysql->sql_query("UPDATE game_players SET is_turn = 1, phase = 'start' WHERE id_gt = $id_gt AND id_user = {$restart['id_user']}");
        }
    }
}

// Вспомогательные функции
function hasMonsterInHand($mysql, $id_user, $id_gt) {
    $result = $mysql->sql_query("SELECT COUNT(*) as count FROM cards_of_user cou 
                                JOIN cards c ON cou.id_card = c.id_card 
                                WHERE cou.id_user = $id_user AND cou.id_gt = $id_gt 
                                AND c.c_type = 'monster'");
    $row = mysqli_fetch_assoc($result);
    return $row['count'] > 0;
}

function hasHelpCards($mysql, $id_user, $id_gt) {
    $result = $mysql->sql_query("SELECT COUNT(*) as count FROM cards_of_user cou 
                                JOIN cards c ON cou.id_card = c.id_card 
                                WHERE cou.id_user = $id_user AND cou.id_gt = $id_gt 
                                AND c.c_type IN ('curse', 'u_class', 'race')");
    $row = mysqli_fetch_assoc($result);
    return $row['count'] > 0;
}

function getActivePhase($mysql, $id_gt) {
    $activePlayer_q = $mysql->sql_query("SELECT id_user FROM game_players WHERE id_gt = $id_gt AND is_turn = 1");
    $activePlayer = mysqli_fetch_assoc($activePlayer_q);
    if ($activePlayer) {
        return getPhase($mysql, $id_gt, $activePlayer['id_user']);
    }
    return null;
}

$profile = $_GET['profile'] ?? '';

// Универсальное определение $id_gt и $id_user
$id_gt = null;
if (isset($_GET['id'])) {
    $id_gt = intval($_GET['id']);
} elseif (isset($_SESSION['id_gt'])) {
    $id_gt = intval($_SESSION['id_gt']);
}
$id_user = $_SESSION['id_user'] ?? null;
$login = $_SESSION['login'] ?? null;

// ===================================================================
// ОСНОВНАЯ ЛОГИКА
// ===================================================================

if (isset($_SESSION['id_user'])) {
    $id_user = $_SESSION['id_user'];
    $login = $_SESSION['login'];

    // ---------------------------------------------------------------
    // ВХОД В ИГРОВУЮ КОМНАТУ
    // ---------------------------------------------------------------
    if ($profile === "join" && isset($_GET['id'])) {

        // Чей ход? (нужно для всех POST-запросов)
        $turnQ = $mysql->sql_query("SELECT id_user FROM game_players WHERE id_gt = $id_gt AND is_turn = 1");
        $turnPlayer = mysqli_fetch_assoc($turnQ);
        $isMyTurn = ($turnPlayer && $turnPlayer['id_user'] == $id_user);

        // ===========================================================
        // ОБРАБОТКА POST ЗАПРОСОВ (ИГРОВЫЕ ДЕЙСТВИЯ)
        // ===========================================================

        $action = $_POST['do'] ?? null;

        // --- ОТКРЫТЬ ДВЕРЬ ---
        if ($action === 'open_door' && $isMyTurn) {
            $phase = getPhase($mysql, $id_gt, $id_user);
            if ($phase === 'start') {
                // Получаем уровень игрока для подбора сложности монстра
                $player_level_q = $mysql->sql_query("SELECT level FROM game_players WHERE id_user = $id_user AND id_gt = $id_gt");
                $player_level = mysqli_fetch_assoc($player_level_q)['level'] ?? 1;

                // 1. Взять карту из колоды дверей (с учетом уровня)
                $card_query = "SELECT cod.*, c.* FROM cards_of_door cod JOIN cards c ON cod.id_card = c.id_card WHERE cod.id_gt = $id_gt";
                // Если игрок низкоуровневый, пытаемся дать ему слабого монстра или не-монстра
                if ($player_level <= 2) {
                    $card_query .= " AND (c.c_type != 'monster' OR c.c_force <= 8)";
                }
                $card_query .= " ORDER BY RAND() LIMIT 1";
                $cardQ = $mysql->sql_query($card_query);

                // Если по мягкому фильтру карт не нашлось, тянем любую, чтобы игра не зависла
                if (mysqli_num_rows($cardQ) == 0) {
                    $cardQ = $mysql->sql_query("SELECT cod.*, c.* FROM cards_of_door cod JOIN cards c ON cod.id_card = c.id_card WHERE cod.id_gt = $id_gt ORDER BY RAND() LIMIT 1");
                }

                if ($card = mysqli_fetch_assoc($cardQ)) {
                    $card_id = intval($card['id_card']);
                    // Приводим тип карты к нижнему регистру и убираем пробелы для надежности
                    $card_type = trim(strtolower($card['c_type']));
                    
                    // 2. Удаляем карту из колоды
                    $mysql->sql_query("DELETE FROM cards_of_door WHERE id_cd = ".intval($card['id_cd']));

                    // 3. Решаем что делать в зависимости от типа карты
                    if ($card_type == 'monster') {
                        // Это монстр! Кладем на стол и начинаем бой.
                        $mysql->sql_query("INSERT INTO cards_of_table (id_gt, id_card, place_card) VALUES ($id_gt, $card_id, 10)");
                        setPhase($mysql, $id_gt, $id_user, 'door'); // Фаза боя
                        echo "<div class='success'>Вышибаете дверь и видите монстра: ".$card['c_name']."</div>";
                    } 
                    // TODO: Добавить обработку проклятий
                    // elseif ($card_type == 'curse') { ... }
                    else {
                        // Это не монстр (класс, раса, шмотка и т.д.).
                        // По правилам, игрок забирает эту карту в руку.
                        $mysql->sql_query("INSERT INTO cards_of_user (id_user, id_card, id_gt, place_card) VALUES ($id_user, $card_id, $id_gt, 20)"); // 20 - рука
                        
                        // Сразу переходим к фазе, где можно завершить ход (или сделать милостыню)
                        setPhase($mysql, $id_gt, $id_user, 'charity');
                        echo "<div class='success'>За дверью никого. Вы забираете карту '{$card['c_name']}' в руку.</div>";
                    }
                } else {
                    echo "<div class='error'>В колоде дверей нет карт!</div>";
                }
            } else {
                 echo "<div class='error'>Вы не можете открыть дверь сейчас.</div>";
            }
            exit();
        }

        // --- БОЙ ---
        if ($action === 'fight' && $isMyTurn) {
            $phase = getPhase($mysql, $id_gt, $id_user);
            if ($phase === 'door') {
                $monsterQ = $mysql->sql_query("SELECT cot.id_ct, c.id_card, c.c_force, c.param1 as level_gain, c.param2 as treasure_gain FROM cards_of_table cot JOIN cards c ON cot.id_card = c.id_card WHERE cot.id_gt = $id_gt AND c.c_type = 'monster' LIMIT 1");

                if ($monster = mysqli_fetch_assoc($monsterQ)) {
                    // Рассчитываем силу игрока
                    $player_level_q = $mysql->sql_query("SELECT level FROM game_players WHERE id_user = $id_user AND id_gt = $id_gt");
                    $player_level = mysqli_fetch_assoc($player_level_q)['level'];
                    $items_bonus_q = $mysql->sql_query("SELECT SUM(c.c_force) as total_bonus FROM carried_items ci JOIN cards c ON ci.id_card = c.id_card WHERE ci.id_user = $id_user");
                    $items_bonus = mysqli_fetch_assoc($items_bonus_q)['total_bonus'] ?? 0;
                    $player_strength = intval($player_level) + intval($items_bonus);
                    
                    $monster_strength = intval($monster['c_force']);

                    if ($player_strength > $monster_strength) {
                        // Победа!
                        $levels_to_gain = intval($monster['level_gain']);
                        $treasures_to_draw = intval($monster['treasure_gain']);
                        
                        $mysql->sql_query("UPDATE game_players SET level = level + $levels_to_gain, treasures_to_draw = treasures_to_draw + $treasures_to_draw WHERE id_user = $id_user AND id_gt = $id_gt");
                        
                        // Карта монстра в сброс
                        $mysql->sql_query("INSERT INTO discards (id_card, id_gt, num_d) VALUES (".intval($monster['id_card']).", $id_gt, 0)");
                        $mysql->sql_query("DELETE FROM cards_of_table WHERE id_ct = ".intval($monster['id_ct']));
                        
                        setPhase($mysql, $id_gt, $id_user, 'combat'); // Фаза после боя (взятие сокровищ)
                        echo "Победа! Вы получаете $levels_to_gain уровень(ня) и $treasures_to_draw сокровищ(а).";

                    } else {
                        // Поражение
                        setPhase($mysql, $id_gt, $id_user, 'run_away');
                        echo "Вы проигрываете бой! Вы должны попытаться смыться.";
                    }

                } else {
                    echo "На столе нет монстра для боя!";
                }
            } else {
                echo "Бой возможен только после открытия двери.";
            }
            exit();
        }
        
        // --- ВЗЯТЬ СОКРОВИЩЕ ---
        if ($action === 'take_loot' && $isMyTurn) {
            $phase = getPhase($mysql, $id_gt, $id_user);
            if ($phase === 'combat') {
                // 1. Сколько сокровищ брать?
                $player_info_q = $mysql->sql_query("SELECT treasures_to_draw FROM game_players WHERE id_user = $id_user AND id_gt = $id_gt");
                $player_info = mysqli_fetch_assoc($player_info_q);
                $treasures_to_draw = $player_info ? intval($player_info['treasures_to_draw']) : 0;

                if ($treasures_to_draw > 0) {
                    // 2. Выдаём сокровища
                    $lootQ = $mysql->sql_query("SELECT * FROM cards_of_loot WHERE id_gt = $id_gt LIMIT $treasures_to_draw");
                    $count = 0;
                    while ($loot = mysqli_fetch_assoc($lootQ)) {
                        $mysql->sql_query("DELETE FROM cards_of_loot WHERE id_cl = ".intval($loot['id_cl']));
                        $mysql->sql_query("INSERT INTO cards_of_user (id_user, id_card, id_gt, place_card) VALUES ($id_user, ".intval($loot['id_card']).", $id_gt, 20)");
                        $count++;
                    }
                    
                    // 3. Сбрасываем счетчик сокровищ
                    $mysql->sql_query("UPDATE game_players SET treasures_to_draw = 0 WHERE id_user = $id_user AND id_gt = $id_gt");

                    setPhase($mysql, $id_gt, $id_user, 'charity'); // Переходим к милостыне
                    echo "<div class='success'>Вы получили $count сокровищ.</div>";

                } else {
                    // Сокровищ брать не нужно, сразу переходим дальше
                    setPhase($mysql, $id_gt, $id_user, 'charity');
                    echo "<div class='info'>Вам не полагается сокровищ.</div>";
                }
            } else {
                echo "<div class='error'>Вы не можете взять сокровища сейчас.</div>";
            }
            exit();
        }

        // --- МИЛОСТЫНЯ ---
        if ($action === 'charity' && $isMyTurn) {
             $phase = getPhase($mysql, $id_gt, $id_user);
             // Разрешаем милостыню, если фаза 'loot' (после взятия сокровищ) или 'charity' (если попали сюда напрямую)
             if ($phase === 'loot' || $phase === 'charity') {
                $hand_count_q = $mysql->sql_query("SELECT COUNT(*) as cnt, p.race FROM cards_of_user u JOIN game_players p ON u.id_user = p.id_user WHERE u.id_user = $id_user AND u.id_gt = $id_gt AND u.place_card = 20 GROUP BY p.race");
                $data = mysqli_fetch_assoc($hand_count_q);
                $hand_count = $data['cnt'] ?? 0;
                $limit = (isset($data['race']) && mb_strtolower($data['race']) === 'дварф') ? 6 : 5;

                if ($hand_count > $limit) {
                    $to_discard_count = $hand_count - $limit;
                    $cards_to_discard_q = $mysql->sql_query("SELECT id_cu, id_card FROM cards_of_user WHERE id_user = $id_user AND id_gt = $id_gt AND place_card = 20 LIMIT $to_discard_count");
                    while ($card = mysqli_fetch_assoc($cards_to_discard_q)) {
                        $mysql->sql_query("DELETE FROM cards_of_user WHERE id_cu = ".intval($card['id_cu']));
                        $mysql->sql_query("INSERT INTO discards (id_card, id_gt) VALUES (".intval($card['id_card']).", $id_gt)");
                    }
                     echo "<div class='success'>Милостыня завершена, лишние карты сброшены.</div>";
                } else {
                    echo "<div class='info'>Милостыня не требуется.</div>";
                }
                setPhase($mysql, $id_gt, $id_user, 'end');
             } else {
                 echo "<div class='error'>Сейчас не фаза милостыни.</div>";
             }
            exit();
        }

        // --- КОНЕЦ ХОДА ---
        if ($action === 'end_turn' && $isMyTurn) {
             $phase = getPhase($mysql, $id_gt, $id_user);
             if ($phase === 'end' || $phase === 'charity') { // Можно завершить ход после милостыни
                passTurn($mysql, $id_gt, $id_user);
                echo "<div class='success'>Ход завершён.</div>";
             } else {
                echo "<div class='error'>Вы не можете завершить ход сейчас.</div>";
             }
            exit();
        }

        // --- СМЫВКА ---
        if ($action === 'run_away' && $isMyTurn) {
            $phase = getPhase($mysql, $id_gt, $id_user);
            if ($phase === 'run_away') {

                $monsterResult = $mysql->sql_query("SELECT cot.id_ct, c.id_card, c.c_name FROM cards_of_table cot JOIN cards c ON cot.id_card = c.id_card WHERE cot.id_gt = $id_gt AND c.c_type = 'monster' LIMIT 1");
                $monster = mysqli_fetch_assoc($monsterResult);

                if ($monster) {
                    $dice_roll = rand(1, 6);

                    if ($dice_roll >= 5) {
                        echo "Вы успешно сбежали от '{$monster['c_name']}' (выпало $dice_roll)!";
                        setPhase($mysql, $id_gt, $id_user, 'charity');
                    } else {
                        $bad_stuff_message = "Смывка от '{$monster['c_name']}' не удалась (выпало $dice_roll)!";
                        $monster_id = $monster['id_card'];

                        switch ($monster_id) {
                            case 7:   // Сочащаяся слизь
                            case 83:  // Калечный гоблин
                                $mysql->sql_query("UPDATE game_players SET level = GREATEST(1, level - 1) WHERE id_user = $id_user AND id_gt = $id_gt");
                                $bad_stuff_message .= " Вы теряете 1 уровень.";
                                break;
                            default:
                                $bad_stuff_message .= " К счастью, его непотребство еще не реализовано.";
                                break;
                        }
                        echo $bad_stuff_message;
                        setPhase($mysql, $id_gt, $id_user, 'charity');
                    }

                    // Убираем монстра со стола
                    $mysql->sql_query("INSERT INTO discards (id_card, id_gt, num_d) VALUES (" . intval($monster['id_card']) . ", $id_gt, 0)");
                    $mysql->sql_query("DELETE FROM cards_of_table WHERE id_ct = " . intval($monster['id_ct']));
                } else {
                    echo "Ошибка: монстр для смывки не найден!";
                    setPhase($mysql, $id_gt, $id_user, 'charity');
                }
            } else {
                echo "Сейчас не время для смывки.";
            }
            exit();
        }

        // --- ПОДГАДИТЬ В БОЮ ---
        if ($action === 'help_in_combat' && !$isMyTurn) {
            $activePhase = getActivePhase($mysql, $id_gt);
            if ($activePhase === 'combat' || $activePhase === 'door') {
                $card_id = intval($_POST['card_id'] ?? 0);
                if ($card_id > 0) {
                    // Проверяем, что карта - монстр и принадлежит игроку
                    $card_q = $mysql->sql_query("SELECT c.c_type, c.c_name FROM cards_of_user cou 
                                                 JOIN cards c ON cou.id_card = c.id_card 
                                                 WHERE cou.id_user = $id_user AND cou.id_gt = $id_gt 
                                                 AND cou.id_card = $card_id AND c.c_type = 'monster'");
                    $card = mysqli_fetch_assoc($card_q);
                    
                    if ($card) {
                        // Перемещаем монстра на стол
                        $mysql->sql_query("DELETE FROM cards_of_user WHERE id_user = $id_user AND id_gt = $id_gt AND id_card = $card_id");
                        $mysql->sql_query("INSERT INTO cards_of_table (id_card, id_gt, place_card) VALUES ($card_id, $id_gt, 10)");
                        
                        echo "Вы подбросили '{$card['c_name']}' в бой!";
                    } else {
                        echo "Ошибка: карта не найдена или не является монстром.";
                    }
                } else {
                    echo "Ошибка: не указана карта.";
                }
            } else {
                echo "Сейчас не время для подгаживания.";
            }
            exit();
        }

        // --- ПОМОЧЬ ИГРОКУ ---
        if ($action === 'help_player' && !$isMyTurn) {
            $activePhase = getActivePhase($mysql, $id_gt);
            if ($activePhase === 'combat' || $activePhase === 'door') {
                $card_id = intval($_POST['card_id'] ?? 0);
                if ($card_id > 0) {
                    // Проверяем, что карта может помочь
                    $card_q = $mysql->sql_query("SELECT c.c_type, c.c_name FROM cards_of_user cou 
                                                 JOIN cards c ON cou.id_card = c.id_card 
                                                 WHERE cou.id_user = $id_user AND cou.id_gt = $id_gt 
                                                 AND cou.id_card = $card_id AND c.c_type IN ('curse', 'u_class', 'race')");
                    $card = mysqli_fetch_assoc($card_q);
                    
                    if ($card) {
                        // Перемещаем карту на стол как помощь
                        $mysql->sql_query("DELETE FROM cards_of_user WHERE id_user = $id_user AND id_gt = $id_gt AND id_card = $card_id");
                        $mysql->sql_query("INSERT INTO cards_of_table (id_card, id_gt, place_card) VALUES ($card_id, $id_gt, 11)");
                        
                        echo "Вы помогли игроку картой '{$card['c_name']}'!";
                    } else {
                        echo "Ошибка: карта не найдена или не может помочь.";
                    }
                } else {
                    echo "Ошибка: не указана карта.";
                }
            } else {
                echo "Сейчас не время для помощи.";
            }
            exit();
        }

        // --- ИГРАТЬ КАРТУ (ЭКИПИРОВКА, КЛАССЫ, РАСЫ) ---
        if ($action === 'play_card' && $isMyTurn && isset($_POST['card_id'])) {
            $card_id = intval($_POST['card_id']);
            $id_cu_q = $mysql->sql_query("SELECT id_cu FROM cards_of_user WHERE id_user = $id_user AND id_gt = $id_gt AND id_card = $card_id AND place_card = 20 LIMIT 1");

            if ($card_in_hand = mysqli_fetch_assoc($id_cu_q)) {
                // Получаем инфу о карте и игроке
                $card_info_q = $mysql->sql_query("SELECT c_type, c_name FROM cards WHERE id_card = $card_id");
                $card_info = mysqli_fetch_assoc($card_info_q);
                $card_type = trim(strtolower($card_info['c_type']));
                $card_name = $card_info['c_name'];
                
                $player_q = $mysql->sql_query("SELECT race, class FROM game_players WHERE id_user = $id_user AND id_gt = $id_gt");
                $player_info = mysqli_fetch_assoc($player_q);

                $can_play_card = false;
                $error_message = "Эту карту нельзя сейчас сыграть.";

                // Логика для шмоток
                if (strpos($card_type, 'item') !== false) {
                    $carried_q = $mysql->sql_query("SELECT c.c_type FROM carried_items ci JOIN cards c ON ci.id_card = c.id_card WHERE ci.id_user = $id_user");
                    $carried_types = [];
                    while($row = mysqli_fetch_assoc($carried_q)) { $carried_types[] = trim(strtolower($row['c_type'])); }

                    $single_slot_items = ['item_head', 'item_body', 'item_leg'];
                    if (in_array($card_type, $single_slot_items)) {
                        if (!in_array($card_type, $carried_types)) { $can_play_card = true; } 
                        else { $error_message = "Слот для этой шмотки уже занят!"; }
                    } else if ($card_type === 'item_arm') {
                        if (count(array_keys($carried_types, 'item_arm')) < 2) { $can_play_card = true; }
                        else { $error_message = "Обе руки уже заняты!"; }
                    } else {
                        // Не-слотовые шмотки (item, item_all)
                        $can_play_card = true;
                    }
                } 
                // Логика для Рас
                elseif ($card_type === 'race') {
                    if (empty($player_info['race']) || $player_info['race'] === 'Человек') {
                        $mysql->sql_query("UPDATE game_players SET race = '$card_name' WHERE id_user = $id_user AND id_gt = $id_gt");
                        $can_play_card = true;
                    } else {
                        $error_message = "У вас уже есть раса ({$player_info['race']}). Сначала избавьтесь от старой.";
                    }
                }
                // Логика для Классов
                elseif ($card_type === 'u_class') {
                     if (empty($player_info['class']) || $player_info['class'] === 'Без класса') {
                        $mysql->sql_query("UPDATE game_players SET class = '$card_name' WHERE id_user = $id_user AND id_gt = $id_gt");
                        $can_play_card = true;
                    } else {
                        $error_message = "У вас уже есть класс ({$player_info['class']}). Сначала избавьтесь от старого.";
                    }
                }
                
                // Если всё ок, перемещаем карту из руки на стол (в "надетые")
                if ($can_play_card) {
                    $mysql->sql_query("DELETE FROM cards_of_user WHERE id_cu = " . intval($card_in_hand['id_cu']));
                    $mysql->sql_query("INSERT INTO carried_items (id_user, id_card, place_card) VALUES ($id_user, $card_id, 1)");
                    echo "Вы сыграли карту: $card_name";
                } else {
                    echo $error_message;
                }

            } else {
                echo "У вас нет такой карты в руке.";
            }
            exit();
        }

        // ===========================================================
        // ПОДГОТОВКА ДАННЫХ ДЛЯ ОТОБРАЖЕНИЯ
        // ===========================================================

        // Автоматическое создание карт, если их нет
        $doorCountQ = $mysql->sql_query("SELECT COUNT(*) as cnt FROM cards_of_door WHERE id_gt = $id_gt");
        if (mysqli_fetch_assoc($doorCountQ)['cnt'] == 0) {
            $cardsQ = $mysql->sql_query("SELECT id_card FROM cards WHERE card_type = 'door' ORDER BY RAND()");
            while ($row = mysqli_fetch_assoc($cardsQ)) { $mysql->sql_query("INSERT INTO cards_of_door (num_door, id_card, id_gt) VALUES (0, " . intval($row['id_card']) . ", $id_gt)"); }
        }
        $lootCountQ = $mysql->sql_query("SELECT COUNT(*) as cnt FROM cards_of_loot WHERE id_gt = $id_gt");
        if (mysqli_fetch_assoc($lootCountQ)['cnt'] == 0) {
            $lootQ = $mysql->sql_query("SELECT id_card FROM cards WHERE card_type = 'loot' ORDER BY RAND()");
            while ($row = mysqli_fetch_assoc($lootQ)) { $mysql->sql_query("INSERT INTO cards_of_loot (id_card, id_gt) VALUES (" . intval($row['id_card']) . ", $id_gt)");}
        }

        // Присоединение к игре (если еще не в ней)
        $check = $mysql->sql_query("SELECT * FROM game_players WHERE id_gt = $id_gt AND id_user = $id_user");
        if (mysqli_num_rows($check) === 0) {
            $info = $mysql->sql_query("SELECT * FROM game_tables WHERE id_gt = $id_gt");
            if ($ginfo = mysqli_fetch_assoc($info)) {
                if ($ginfo['num_user'] < $ginfo['limit_user']) {
                    $mysql->sql_query("INSERT INTO game_players (id_gt, id_user, login) VALUES ($id_gt, $id_user, '$login')");
                    $mysql->sql_query("UPDATE game_tables SET num_user = num_user + 1 WHERE id_gt = $id_gt");
                }
            }
        }
        
        // Назначение первого игрока, если ход никому не назначен
        $check_turn = $mysql->sql_query("SELECT COUNT(*) as c FROM game_players WHERE id_gt = $id_gt AND is_turn = 1");
        if (mysqli_fetch_assoc($check_turn)['c'] == 0) {
            $firstPlayerQ = $mysql->sql_query("SELECT id_user FROM game_players WHERE id_gt = $id_gt ORDER BY id_user ASC LIMIT 1");
            if ($firstPlayer = mysqli_fetch_assoc($firstPlayerQ)) {
                $mysql->sql_query("UPDATE game_players SET is_turn = 1 WHERE id_gt = $id_gt AND id_user = " . $firstPlayer['id_user']);
            }
        }
        
        // Инфо игрока
        $playerQ = $mysql->sql_query("SELECT * FROM game_players WHERE id_user = $id_user AND id_gt = $id_gt");
        $playerInfo = mysqli_fetch_assoc($playerQ);
        $player_level = $playerInfo['level'] ?? 1;
        $player_race = $playerInfo['race'] ?? '—';
        $player_class = $playerInfo['class'] ?? '—';

        // Инфо игры
        $gameQ = $mysql->sql_query("SELECT * FROM game_tables WHERE id_gt = $id_gt");
        $game = mysqli_fetch_assoc($gameQ);
        $creator = $game['creator'] ?? '—';

        // Игроки
        $playersQ = $mysql->sql_query("SELECT login, is_turn FROM game_players WHERE id_gt = $id_gt ORDER BY id_user ASC");
        $players_list = [];
        $current_turn_user = '—';
        while ($p = mysqli_fetch_assoc($playersQ)) {
            if ($p['is_turn']) {
                $current_turn_user = $p['login'];
            }
            $players_list[] = $p['login'];
        }

        // Карты игрока
        $cardsQ = $mysql->sql_query("SELECT u.*, c.pic FROM cards_of_user u JOIN cards c ON u.id_card = c.id_card WHERE u.id_user = $id_user AND u.id_gt = $id_gt");
        $player_cards = [];
        while ($card = mysqli_fetch_assoc($cardsQ)) {
            $player_cards[] = $card;
        }

        // Карты на столе
        $openedQ = $mysql->sql_query("SELECT t.*, c.pic, c.c_name, c.c_type FROM cards_of_table t JOIN cards c ON t.id_card = c.id_card WHERE t.id_gt = $id_gt AND t.place_card = 10");
        $opened_door_cards = [];
        while ($card = mysqli_fetch_assoc($openedQ)) {
            $opened_door_cards[] = $card;
        }

        $opened_card = mysqli_fetch_assoc($openedQ);

        // Сброс
        $d_door_q = $mysql->sql_query("SELECT COUNT(*) as c FROM discards d JOIN cards c ON d.id_card = c.id_card WHERE d.id_gt=$id_gt AND c.card_type='door'");
        $d_door = mysqli_fetch_assoc($d_door_q)['c'];
        $d_loot_q = $mysql->sql_query("SELECT COUNT(*) as c FROM discards d JOIN cards c ON d.id_card = c.id_card WHERE d.id_gt=$id_gt AND c.card_type='loot'");
        $d_loot = mysqli_fetch_assoc($d_loot_q)['c'];

        $doorCountResult = $mysql->sql_query("SELECT COUNT(*) as cnt FROM cards_of_door WHERE id_gt = $id_gt");
        $doorCount = mysqli_fetch_assoc($doorCountResult)['cnt'];

        $lootCountResult = $mysql->sql_query("SELECT COUNT(*) as cnt FROM cards_of_loot WHERE id_gt = $id_gt");
        $lootCount = mysqli_fetch_assoc($lootCountResult)['cnt'];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Игровое меню</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="gamemenu.css" />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="minichat.css" />
    <link rel="stylesheet" href="window_card.css" />
    <script type="text/javascript" src="js/jquery-1.4.4.min.js"></script>
    <script type="text/javascript" src="js/jquery-ui-1.8.9.custom.min.js"></script>
    <script type="text/javascript" src="js/jquery.corner.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            const GAME_ID = <?php echo json_encode($id_gt); ?>;
            const USER_ID = <?php echo json_encode($id_user); ?>;
            let gameStateInterval = null;

            function initGame() {
                fetchGameState();
                gameStateInterval = setInterval(fetchGameState, 3000);

                // --- ОБРАБОТЧИКИ КЛИКОВ (СИНТАКСИС ДЛЯ СТАРОГО JQUERY) ---

                // Клики по кнопкам действий
                $('#action-buttons button').live('click', function() {
                    const action = $(this).data('action');
                    if (!$(this).is(':disabled')) {
                        handleAction(action);
                    }
                });

                // Клики (левый и правый) по картам
                $('.card-in-hand, .card-on-table, .monster-card, .help-card').live('mousedown', function(e) {
                    e.preventDefault();
                    const cardDiv = $(this);
                    const cardId = cardDiv.data('card-id');
                    
                    if (e.which === 1) { // Левая кнопка мыши - играть карту
                        if (cardDiv.hasClass('card-in-hand')) {
                             handlePlayCard(cardId);
                        }
                    } else if (e.which === 3) { // Правая кнопка мыши - увеличить
                        const cardImageSrc = cardDiv.find('img').attr('src');
                        $('#card-viewer-overlay img').attr('src', cardImageSrc);
                        $('#card-viewer-overlay').css('display', 'flex').hide().fadeIn(200);
                    }
                });
                
                // Закрытие просмотра карты
                $('#card-viewer-overlay').live('click', function() {
                    $(this).fadeOut(200);
                });
            };

            function fetchGameState() {
                 if (!GAME_ID) return;
                $.ajax({
                    url: `get_game_state.php?id_gt=${GAME_ID}`,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            updateUI(response.data);
                        } else {
                            console.error('ERROR from get_game_state.php:', response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX FATAL ERROR fetching state:', status, error);
                        console.error('SERVER RESPONSE:', xhr.responseText);
                    }
                });
            }

            function handleAction(action) {
                $.ajax({
                    url: `gamemenu.php?profile=join&id=${GAME_ID}`,
                    type: 'POST',
                    data: { do: action },
                    success: function(response) {
                        fetchGameState();
                    },
                    error: function(xhr, status, error) {
                        console.error(`ACTION: AJAX FATAL ERROR for '${action}':`, status, error);
                        console.error('ACTION SERVER RESPONSE:', xhr.responseText);
                    }
                });
            }

            function handlePlayCard(cardId) {
                $.ajax({
                    url: `gamemenu.php?profile=join&id=${GAME_ID}`,
                    type: 'POST',
                    data: { do: 'play_card', card_id: cardId },
                    success: function(response) {
                        // Показываем сообщение от сервера, если это не стандартный успешный ответ
                        if (response && response.indexOf('Вы надели:') === -1) {
                           alert(response);
                        }
                        fetchGameState();
                    },
                    error: function(xhr, status, error) {
                        console.error(`ACTION: AJAX FATAL ERROR for 'play_card':`, status, error);
                        alert('Произошла критическая ошибка при попытке сыграть карту.');
                    }
                });
            }

            function updateUI(data) {
                // 1. Обновление колод и сброса
                $('#door-deck-count').text(data.decks.door_count);
                $('#loot-deck-count').text(data.decks.loot_count);
                $('#door-discard-count').text(data.discards.door_count);
                $('#loot-discard-count').text(data.discards.loot_count);

                // 2. Обновление чей ход
                const activePlayer = data.players.find(p => p.id_user == data.turn_info.active_player_id);
                if (activePlayer) {
                    $('#turn-holder').text(activePlayer.login);
                }
                
                // 3. Обновление краткого списка игроков (левая панель)
                const playerListSummary = $('#player-list-summary');
                let playerListHtml = '';
                data.players.forEach(player => {
                    playerListHtml += `<div class="player-summary-item ${player.is_turn ? 'active-turn' : ''}">
                        <b>${player.login}</b> (Ур: ${player.level} / Сила: ${player.combat_strength})
                    </div>`;
                });
                playerListSummary.html(playerListHtml);

                // 4. Обновление карт на столе
                const tableContainer = $('#table-cards-container');
                tableContainer.empty();
                if (data.table_cards.monster_cards) {
                    data.table_cards.monster_cards.forEach(card => {
                        tableContainer.append(`<div class="card monster-card" data-card-id="${card.id_card}" title="${card.c_name}"><img src="picture/${card.pic}" /></div>`);
                    });
                }
                 if (data.table_cards.help_cards) {
                    data.table_cards.help_cards.forEach(card => {
                        tableContainer.append(`<div class="card help-card" data-card-id="${card.id_card}" title="${card.c_name}"><img src="picture/${card.pic}" /></div>`);
                    });
                }

                // 5. Обновление детальной информации об игроках (основная область)
                const playersContainer = $('#players-container');
                let playersHtml = '';
                 data.players.forEach(player => {
                    let handHtml = '';
                    if (Array.isArray(player.hand)) {
                        player.hand.forEach(card => {
                            handHtml += `<div class="card card-in-hand" data-card-id="${card.id_card}" title="${card.c_name}"><img src="picture/${card.pic}" /></div>`;
                        });
                    } else if (player.hand && typeof player.hand.count !== 'undefined' && player.hand.count > 0) {
                        for (let i = 0; i < player.hand.count; i++) {
                            handHtml += `<div class="card card-in-hand-other"><img src="picture/door_for_hand.jpg" /></div>`;
                        }
                    } else {
                        handHtml = '<span>Карт нет</span>';
                    }

                    let itemsHtml = '';
                    if (player.items && player.items.length > 0) {
                        player.items.forEach(item => {
                            itemsHtml += `<div class="card card-on-table" data-card-id="${item.id_card}" title="${item.c_name}"><img src="picture/${item.pic}" /></div>`;
                        });
                    } else {
                        itemsHtml = '<span>Нет шмоток</span>';
                    }

                    playersHtml += `
                        <div class="player-block ${player.is_turn ? 'active-turn' : ''}" data-player-id="${player.id_user}">
                            <div class="player-info">
                                <b>${player.login}</b> (Уровень: ${player.level} / Сила: ${player.combat_strength})
                            </div>
                            <div class="player-items-title"><b>Надето:</b></div>
                            <div class="player-items cards-container">${itemsHtml}</div>
                            <div class="player-hand-title"><b>Рука:</b></div>
                            <div class="player-hand cards-container">${handHtml}</div>
                        </div>
                        <hr>
                    `;
                });
                playersContainer.html(playersHtml);
                
                // 6. Обновление кнопок действий
                const myId = parseInt(USER_ID, 10);
                const activeId = parseInt(data.turn_info.active_player_id, 10);
                const isMyTurn = myId === activeId;
                
                // Сначала выключаем все кнопки
                $('#action-buttons button').attr('disabled', 'disabled');

                // Затем включаем нужные, если это наш ход
                if (isMyTurn) {
                    const phase = data.turn_info.phase;
                    const hasMonsterOnTable = data.table_cards && data.table_cards.monster_cards && data.table_cards.monster_cards.length > 0;

                    switch (phase) {
                        case 'start':
                            $('button[data-action="open_door"]').removeAttr('disabled');
                            break;
                        case 'door':
                            if (hasMonsterOnTable) {
                                $('button[data-action="fight"]').removeAttr('disabled');
                            }
                            // Больше здесь ничего не нужно, т.к. карты не-монстры сразу идут в руку
                            break;
                        case 'combat':
                             $('button[data-action="take_loot"]').removeAttr('disabled');
                            break;
                        case 'run_away':
                            $('button[data-action="run_away"]').removeAttr('disabled');
                            break;
                        case 'charity':
                            $('button[data-action="charity"]').removeAttr('disabled');
                            $('button[data-action="end_turn"]').removeAttr('disabled');
                            break;
                        case 'end':
                             $('button[data-action="end_turn"]').removeAttr('disabled');
                            break;
                    }
                }
            }
            initGame();
        });
    </script>
    <style>
        .player-block.active-turn, .player-summary-item.active-turn {
            border: 2px solid #ffcc00;
            box-shadow: 0 0 10px #ffcc00;
        }
        .card {
            position: relative;
            width: 100px;
            height: 150px;
            margin: 5px;
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 5px;
            overflow: hidden;
        }
        .cards-container {
             display: flex;
             flex-wrap: wrap;
        }
        .card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .card-in-hand {
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }
        .card-in-hand:hover {
            transform: scale(1.05);
            box-shadow: 0 0 10px #ffcc00;
        }
        #card-viewer-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none; justify-content: center; align-items: center; z-index: 10000;
        }
        #card-viewer-overlay img {
            max-width: 80vw; max-height: 80vh; border: 3px solid #fff; border-radius: 10px;
        }
        button:disabled {
            cursor: not-allowed !important; opacity: 0.5 !important;
            background-color: #E0E0E0 !important; color: #A0A0A0 !important;
            border-color: #C0C0C0 !important;
        }
    </style>
</head>
<body>
    <div style="float:left; width: 250px; border: 1px solid #ccc; padding: 10px; margin-right: 20px;">
        <div id="decks-info">
            <p>Колода дверей: <span id="door-deck-count">?</span></p>
            <p>Сброс дверей: <span id="door-discard-count">?</span></p>
            <br>
            <p>Колода сокровищ: <span id="loot-deck-count">?</span></p>
            <p>Сброс сокровищ: <span id="loot-discard-count">?</span></p>
        </div>
        <hr>
        <div id="players-area">
            <h4>Игроки</h4>
            <div id="player-list-summary">
                <!-- Краткая инфа об игроках будет здесь -->
            </div>
        </div>
    </div>

    <div style="float:left; width: 70%;">
        <div id="game-board">
            <div id="table-cards-area">
                <h4>На столе</h4>
                <div id="table-cards-container" class="cards-container" style="min-height: 160px;"></div>
            </div>
            <hr>
            <div id="players-container">
                <!-- Детальная инфа об игроках будет здесь -->
            </div>
        </div>
        <hr>
        <div class="actions-area">
            <h4>Действия (Ход: <span id="turn-holder">?</span>)</h4>
            <div id="action-buttons">
                <button data-action="open_door">Открыть дверь</button>
                <button data-action="fight">Бой</button>
                <button data-action="run_away">Смыться</button>
                <button data-action="take_loot">Взять сокровище</button>
                <button data-action="charity">Милостыня</button>
                <button data-action="end_turn">Конец хода</button>
            </div>
        </div>
        <hr>
        <div class="chat-area">
             <h4>Чат</h4>
            <?php require_once 'minichat.php'; ?>
        </div>
    </div>

    <div id="card-viewer-overlay">
        <img src="" alt="Увеличенное изображение карты" />
    </div>
</body>
</html>
<?php
    } // Закрываем блок if ($profile === "join" && isset($_GET['id']))
} // Закрываем блок if (isset($_SESSION['id_user']))
?>
