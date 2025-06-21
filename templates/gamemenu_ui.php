<?php
// Подсчёт карт в сбросе дверей
$door_discard_count = 0;
$q = $mysql->sql_query("SELECT COUNT(*) as cnt FROM discards JOIN cards ON discards.id_card=cards.id_card WHERE id_gt=$id_gt AND cards.card_type='door'");
if ($row = mysqli_fetch_assoc($q)) $door_discard_count = $row['cnt'];
// Подсчёт карт в сбросе сокровищ
$loot_discard_count = 0;
$q = $mysql->sql_query("SELECT COUNT(*) as cnt FROM discards JOIN cards ON discards.id_card=cards.id_card WHERE id_gt=$id_gt AND cards.card_type='loot'");
if ($row = mysqli_fetch_assoc($q)) $loot_discard_count = $row['cnt'];
// Получаем флаг: мой ли сейчас ход и фазу
$isMyTurn = ($current_turn_user === $login);
$phase = getPhase($mysql, $id_gt, $id_user);
// Получаем карту на столе (открытая дверь)
$monsterQ = $mysql->sql_query("SELECT cards_of_table.*, cards.c_name, cards.c_type, cards.pic, cards.param1, cards.param2, cards.param3 FROM cards_of_table JOIN cards ON cards_of_table.id_card = cards.id_card WHERE cards_of_table.id_gt = $id_gt AND cards_of_table.place_card = 10 LIMIT 1");
$monster = mysqli_fetch_assoc($monsterQ);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Игровое меню</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #ffcc99; }
        .game-layout {
            display: flex;
            flex-direction: row;
            max-width: 1500px;
            margin: 30px auto;
            font-family: Tahoma, Ubuntu, sans-serif;
        }
        .side-panel {
            width: 180px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .deck-block, .discard-block {
            background: #ffe0b2;
            border: 2px solid #a68564;
            border-radius: 10px;
            padding: 14px 0;
            text-align: center;
            font-weight: bold;
            margin-bottom: 8px;
            cursor: pointer;
            box-shadow: 0 2px 6px #c19a74;
        }
        .deck-block .reshuffle, .discard-block .reshuffle {
            display: block;
            margin: 8px auto 0 auto;
            font-size: 12px;
            color: #a68564;
            background: #fff8e1;
            border: 1px solid #a68564;
            border-radius: 6px;
            padding: 2px 8px;
            cursor: pointer;
        }
        .main-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0 18px;
        }
        .player-area {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 18px;
        }
        .level-counter {
            font-size: 28px;
            font-weight: bold;
            color: #ff9800;
            margin-bottom: 6px;
        }
        .hand-row {
            display: flex;
            flex-direction: row;
            gap: 8px;
            margin-bottom: 10px;
        }
        .hand-cell {
            width: 90px;
            height: 130px;
            background: #fff8e1;
            border: 2px dashed #a68564;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            color: #a68564;
            position: relative;
            overflow: visible;
        }
        .zone-block {
            display: flex;
            flex-direction: row;
            gap: 24px;
            margin-bottom: 10px;
        }
        .zone {
            background: #fff8e1;
            border: 2px dashed #a68564;
            border-radius: 10px;
            min-width: 180px;
            min-height: 80px;
            padding: 8px;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: flex-start;
            position: relative;
        }
        .zone-title {
            font-weight: bold;
            color: #a68564;
            margin-bottom: 4px;
        }
        .other-players {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 18px;
        }
        .other-player-block {
            border: 2px solid #a68564;
            border-radius: 8px;
            background: #ffe0b2;
            padding: 8px;
            min-width: 180px;
        }
        .other-player-block .level {
            font-size: 18px;
            color: #ff9800;
            font-weight: bold;
        }
        .chat {
            margin-top: 18px;
            width: 100%;
            background: #fff8e1;
            border: 2px solid #a68564;
            border-radius: 8px;
            min-height: 80px;
            max-height: 160px;
            overflow-y: auto;
            padding: 8px;
            font-size: 13px;
        }
        .chat-input {
            width: 100%;
            margin-top: 4px;
            display: flex;
            gap: 4px;
        }
        .chat-input input { flex: 1; padding: 4px; border-radius: 5px; border: 1px solid #a68564; }
        .chat-input button { padding: 4px 12px; border-radius: 5px; border: 1px solid #a68564; background: #ffe0b2; font-weight: bold; }
        #game-message .success { background: #e0ffe0; color: #207520; border: 1px solid #7ad67a; padding: 10px; border-radius: 8px; font-weight: bold; }
        #game-message .error { background: #ffe0e0; color: #a00; border: 1px solid #e77; padding: 10px; border-radius: 8px; font-weight: bold; }
    </style>
</head>
<body>
<?php $game_id = isset($_GET['id']) ? intval($_GET['id']) : 0; ?>
<div class="game-layout">
    <!-- Левая панель: колоды и сбросы -->
    <div class="side-panel">
        <div class="deck-block" onclick="openDoor()">Колода дверей<br><button class="reshuffle" onclick="reshuffleDeck('door');event.stopPropagation();">Перетасовать</button></div>
        <div class="discard-block">Сброс дверей (<?= $door_discard_count ?>)</div>
        <div class="deck-block" onclick="takeTreasure()">Колода сокровищ<br><button class="reshuffle" onclick="reshuffleDeck('loot');event.stopPropagation();">Перетасовать</button></div>
        <div class="discard-block">Сброс сокровищ (<?= $loot_discard_count ?>)</div>
    </div>
    <!-- Центральная панель: ваша зона -->
    <div class="main-panel">
        <div class="player-area">
            <div class="level-counter">Уровень: <span id="my-level"><?= $player_level ?></span></div>
            <div class="hand-row">
                <?php
                // Разделение карт по типу для текущего игрока
                $hand_cards = [];
                $equipment_cards = [];
                $curse_cards = [];
                foreach ($player_cards as $card) {
                    $type = strtolower($card['card_type'] ?? $card['type'] ?? '');
                    if ($type === 'equipment' || $type === 'equip' || $type === 'шмотка' || $type === 'шмотки') {
                        $equipment_cards[] = $card;
                    } elseif ($type === 'curse' || $type === 'проклятие' || $type === 'проклятия') {
                        $curse_cards[] = $card;
                    } else {
                        $hand_cards[] = $card;
                    }
                }
                // Получаем карты других игроков
                $other_players_cards = [];
                foreach ($players_list as $pl) {
                    if ($pl == $login) continue;
                    $pInfoQ = $mysql->sql_query("SELECT id_user FROM game_players WHERE login='".addslashes($pl)."' AND id_gt=".$id_gt);
                    $pInfo = mysqli_fetch_assoc($pInfoQ);
                    $pid = $pInfo['id_user'] ?? null;
                    $other_equipment = [];
                    $other_curse = [];
                    if ($pid) {
                        $cardsQ = $mysql->sql_query("SELECT cards_of_user.*, cards.c_name as card_name, cards.c_type as card_type, cards.pic FROM cards_of_user JOIN cards ON cards_of_user.id_card = cards.id_card WHERE cards_of_user.id_user = $pid AND cards_of_user.id_gt = $id_gt");
                        while ($c = mysqli_fetch_assoc($cardsQ)) {
                            $type = strtolower($c['card_type'] ?? $c['type'] ?? '');
                            if ($type === 'equipment' || $type === 'equip' || $type === 'шмотка' || $type === 'шмотки') {
                                $other_equipment[] = $c;
                            } elseif ($type === 'curse' || $type === 'проклятие' || $type === 'проклятия') {
                                $other_curse[] = $c;
                            }
                        }
                    }
                    $other_players_cards[$pl] = [
                        'equipment' => $other_equipment,
                        'curse' => $other_curse
                    ];
                }
                ?>
                <?php foreach ($hand_cards as $card): ?>
                    <div class="hand-cell card-preview" data-img="picture/<?= htmlspecialchars($card['pic']) ?>" data-title="<?= htmlspecialchars($card['card_name'] ?? $card['c_name'] ?? '') ?>" data-type="<?= htmlspecialchars($card['card_type'] ?? $card['type'] ?? '') ?>" data-desc="<?= htmlspecialchars($card['card_text'] ?? '') ?>">
                        <img src="picture/<?= htmlspecialchars($card['pic']) ?>" alt="" style="width:70px;height:100px;"/>
                        <div><?= htmlspecialchars($card['card_name'] ?? $card['c_name'] ?? '') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="zone-block">
                <div class="zone">
                    <div class="zone-title">Экипировка</div>
                    <?php foreach ($equipment_cards as $card): ?>
                        <div class="hand-cell card-preview" data-img="picture/<?= htmlspecialchars($card['pic']) ?>" data-title="<?= htmlspecialchars($card['card_name'] ?? $card['c_name'] ?? '') ?>" data-type="<?= htmlspecialchars($card['card_type'] ?? $card['type'] ?? '') ?>" data-desc="<?= htmlspecialchars($card['card_text'] ?? '') ?>">
                            <img src="picture/<?= htmlspecialchars($card['pic']) ?>" alt="" style="width:70px;height:100px;"/>
                            <div><?= htmlspecialchars($card['card_name'] ?? $card['c_name'] ?? '') ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="zone">
                    <div class="zone-title">Проклятия</div>
                    <?php foreach ($curse_cards as $card): ?>
                        <div class="hand-cell card-preview" data-img="picture/<?= htmlspecialchars($card['pic']) ?>" data-title="<?= htmlspecialchars($card['card_name'] ?? $card['c_name'] ?? '') ?>" data-type="<?= htmlspecialchars($card['card_type'] ?? $card['type'] ?? '') ?>" data-desc="<?= htmlspecialchars($card['card_text'] ?? '') ?>">
                            <img src="picture/<?= htmlspecialchars($card['pic']) ?>" alt="" style="width:70px;height:100px;"/>
                            <div><?= htmlspecialchars($card['card_name'] ?? $card['c_name'] ?? '') ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="other-players">
            <?php foreach ($players_list as $pl): if ($pl == $login) continue; ?>
            <div class="other-player-block">
                <div><b><?= htmlspecialchars($pl) ?></b></div>
                <div class="level">Уровень: <?= /* тут нужен уровень игрока */ 1 ?></div>
                <div>Экипировка:
                    <?php foreach ($other_players_cards[$pl]['equipment'] as $card): ?>
                        <span class="hand-cell card-preview" data-img="picture/<?= htmlspecialchars($card['pic']) ?>" data-title="<?= htmlspecialchars($card['card_name'] ?? $card['c_name'] ?? '') ?>" data-type="<?= htmlspecialchars($card['card_type'] ?? $card['type'] ?? '') ?>" data-desc="<?= htmlspecialchars($card['card_text'] ?? '') ?>">
                            <img src="picture/<?= htmlspecialchars($card['pic']) ?>" alt="" style="width:40px;height:60px;"/>
                        </span>
                    <?php endforeach; ?>
                </div>
                <div>Проклятия:
                    <?php foreach ($other_players_cards[$pl]['curse'] as $card): ?>
                        <span class="hand-cell card-preview" data-img="picture/<?= htmlspecialchars($card['pic']) ?>" data-title="<?= htmlspecialchars($card['card_name'] ?? $card['c_name'] ?? '') ?>" data-type="<?= htmlspecialchars($card['card_type'] ?? $card['type'] ?? '') ?>" data-desc="<?= htmlspecialchars($card['card_text'] ?? '') ?>">
                            <img src="picture/<?= htmlspecialchars($card['pic']) ?>" alt="" style="width:40px;height:60px;"/>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- Кнопки игровых действий -->
        <div style="margin: 12px 0;">
            <button onclick="gameAction('open_door')" <?php if (!$isMyTurn || $phase !== 'start') echo 'disabled title="Доступно только в начале своего хода"'; ?>>Открыть дверь</button>
            <button onclick="gameAction('fight')" <?php if (!$isMyTurn || $phase !== 'door') echo 'disabled title="Доступно только после открытия двери"'; ?>>Бой</button>
            <button onclick="gameAction('take_loot')" <?php if (!$isMyTurn || !in_array($phase, ['combat', 'door'])) echo 'disabled title="Доступно только после боя или если за дверью не монстр"'; ?>>Взять сокровище</button>
            <button onclick="gameAction('charity')" <?php if (!$isMyTurn || !in_array($phase, ['charity', 'loot'])) echo 'disabled title="Доступно только после получения сокровищ"'; ?>>Милостыня</button>
            <button onclick="gameAction('end_turn')" <?php if (!$isMyTurn || !in_array($phase, ['end', 'charity'])) echo 'disabled title="Доступно только после милостыни или если лимит карт не превышен"'; ?>>Конец хода</button>
        </div>
        <!-- Контейнер для сообщений -->
        <div id="game-message" style="display:none; margin: 10px auto; max-width: 400px; text-align: center;"></div>
        <!-- Лог/чат -->
        <div class="chat" style="max-height:120px; min-height:60px; margin-bottom:10px;">
<?php
// Выводим последние 15 событий из gamechat/game_log
$logQ = $mysql->sql_query("SELECT * FROM gamechat WHERE id_gt = $id_gt ORDER BY time DESC LIMIT 15");
$log = [];
while ($row = mysqli_fetch_assoc($logQ)) {
    $log[] = '['.date('H:i:s', $row['time']).'] '.strip_tags($row['text']);
}
$log = array_reverse($log);
foreach ($log as $line) echo htmlspecialchars($line)."<br>";
?>
        </div>
        <div class="chat-input">
            <input type="text" id="chat-input" placeholder="Введите сообщение..."/>
            <button onclick="sendChat()">Отправить</button>
        </div>
    </div>
</div>
<!-- Блок монстра/двери на столе -->
<?php if ($monster): ?>
<div style="background:#fff8e1; border:2px solid #a68564; border-radius:10px; padding:12px; margin-bottom:12px; max-width:320px; text-align:center;">
    <div><img src="picture/<?= htmlspecialchars($monster['pic']) ?>" alt="<?= htmlspecialchars($monster['c_name']) ?>" style="max-width:120px;"></div>
    <div style="font-weight:bold; font-size:18px; color:#a68564; margin:6px 0;"> <?= htmlspecialchars($monster['c_name']) ?> </div>
    <div>Тип: <?= htmlspecialchars($monster['c_type']) ?></div>
    <?php if ($monster['c_type'] === 'monster'): ?>
        <div>Сила монстра: <b><?= intval($monster['param1']) ?></b></div>
        <div>Награда: <b><?= intval($monster['param2']) ?> сокровищ</b></div>
        <div>Штраф: <?= htmlspecialchars($monster['param3']) ?></div>
    <?php endif; ?>
</div>
<?php endif; ?>
<!-- Блок полученных сокровищ -->
<?php if (isset($last_loot) && is_array($last_loot) && count($last_loot)): ?>
<div style="background:#e0ffe0; border:2px solid #7ad67a; border-radius:10px; padding:10px; margin-bottom:10px; max-width:320px; text-align:center;">
    <b>Вы получили сокровища:</b><br>
    <?php foreach ($last_loot as $loot): ?>
        <div style="display:inline-block; margin:4px;">
            <img src="picture/<?= htmlspecialchars($loot['pic']) ?>" alt="<?= htmlspecialchars($loot['c_name']) ?>" style="max-width:60px;"><br>
            <?= htmlspecialchars($loot['c_name']) ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<script>
const GAME_ID = <?= $game_id ?>;
// Drag & Drop для карт между зонами
let draggedCard = null;

document.querySelectorAll('.zone, .hand-cell').forEach(cell => {
    cell.addEventListener('dragover', e => {
        e.preventDefault();
        cell.classList.add('dragover');
    });
    cell.addEventListener('dragleave', e => {
        cell.classList.remove('dragover');
    });
    cell.addEventListener('drop', e => {
        e.preventDefault();
        cell.classList.remove('dragover');
        if (draggedCard) {
            cell.appendChild(draggedCard);
            // TODO: отправить на сервер новое положение карты
        }
    });
});

// Превью карт по наведению
let previewDiv;
document.addEventListener('mouseover', function(e) {
    if (e.target.closest('.card-preview')) {
        const card = e.target.closest('.card-preview');
        if (!previewDiv) {
            previewDiv = document.createElement('div');
            previewDiv.style.position = 'fixed';
            previewDiv.style.zIndex = 9999;
            previewDiv.style.background = '#fff8e1';
            previewDiv.style.border = '2px solid #a68564';
            previewDiv.style.borderRadius = '10px';
            previewDiv.style.padding = '10px';
            previewDiv.style.boxShadow = '0 4px 16px #c19a74';
            previewDiv.style.width = '220px';
            previewDiv.style.display = 'flex';
            previewDiv.style.flexDirection = 'column';
            previewDiv.style.alignItems = 'center';
            document.body.appendChild(previewDiv);
        }
        previewDiv.innerHTML = `<img src='${card.dataset.img}' style='width:160px;height:220px;'><div style='font-weight:bold;margin:4px 0;'>${card.dataset.title}</div><div style='color:#a68564;'>${card.dataset.type}</div><div style='font-size:13px;margin-top:4px;'>${card.dataset.desc}</div>`;
        previewDiv.style.left = (e.pageX + 20) + 'px';
        previewDiv.style.top = (e.pageY - 40) + 'px';
        previewDiv.style.display = 'flex';
    }
});
document.addEventListener('mousemove', function(e) {
    if (previewDiv && previewDiv.style.display === 'flex') {
        previewDiv.style.left = (e.pageX + 20) + 'px';
        previewDiv.style.top = (e.pageY - 40) + 'px';
    }
});
document.addEventListener('mouseout', function(e) {
    if (e.target.closest('.card-preview') && previewDiv) {
        previewDiv.style.display = 'none';
    }
});

// Кнопки "Открыть дверь" и "Взять сокровище"
function showGameMessage(html) {
    const msg = document.getElementById('game-message');
    msg.innerHTML = html;
    msg.style.display = 'block';
    if (html.includes('success')) {
        setTimeout(() => { msg.style.display = 'none'; location.reload(); }, 1500);
    } else if (html.includes('error')) {
        setTimeout(() => { msg.style.display = 'none'; }, 2500);
    }
}
function openDoor() {
    fetch(window.location.pathname + window.location.search, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'do=open_door'
    })
    .then(response => response.text())
    .then(html => {
        showGameMessage(html);
    });
}
function takeTreasure() {
    fetch(window.location.pathname + window.location.search, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'do=take_loot'
    })
    .then(response => response.text())
    .then(html => {
        showGameMessage(html);
    });
}
function reshuffleDeck(type) {
    // TODO: реализовать перетасовку через AJAX
    showGameMessage('<div class="success">Перетасовано!</div>');
}
function gameAction(actionName) {
    const formData = new FormData();
    formData.append('do', actionName);

    fetch('gamemenu.php?profile=join&id=<?= $id_gt ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        const messageDiv = document.getElementById('game-message');
        messageDiv.innerHTML = html;
        messageDiv.style.display = 'block';

        // Автоматическая перезагрузка страницы через 1.5 секунды для обновления состояния
        setTimeout(() => {
            window.location.reload();
        }, 1500);
    })
    .catch(error => {
        console.error('Ошибка:', error);
        const messageDiv = document.getElementById('game-message');
        messageDiv.innerHTML = "<div class='error'>Произошла ошибка при отправке действия.</div>";
        messageDiv.style.display = 'block';
    });
}
function sendChat() {
    // TODO: реализовать отправку чата глобально (AJAX)
    const input = document.getElementById('chat-input');
    if (input.value.trim()) {
        const log = document.getElementById('game-log');
        log.innerHTML += '<div><b>Вы:</b> ' + input.value + '</div>';
        input.value = '';
    }
}
</script>
</body>
</html>
