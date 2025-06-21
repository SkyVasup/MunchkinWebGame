<?php
// Этот файл больше не должен подключать global.php или стартовать сессию,
// так как он теперь является частью gamemenu.php, где все уже инициализировано.

// Функция для отображения чата
function show_chat($mysql, $id_gt) {
    if (!$id_gt) return;
    $getlaststr = $mysql->sql_query("SELECT * FROM minichat WHERE game_id = ".intval($id_gt)." ORDER BY time DESC LIMIT 60");
    if (!$getlaststr) return;

    $chatarr = array();
    while ($row = mysqli_fetch_assoc($getlaststr)) {
        $chatarr[] = $row;
    }
    krsort($chatarr);
    foreach ($chatarr as $chatdata) {
        if (strlen($chatdata["text"]) > 0) {
            $time = date("H:i", $chatdata["time"]);
            echo "<div class='chat-message'>[{$time}] <strong>{$chatdata['login']}:</strong> {$chatdata['text']}</div>";
        }
    }
}

// Функция для добавления сообщения в чат
function add_chat_message($mysql, $id_gt, $id_user, $login, $message) {
    if (!$id_gt || !$id_user || empty(trim($message))) return;
    
    $time = time();
    $message_safe = htmlspecialchars(substr($message, 0, 256), ENT_QUOTES);

    // Простое форматирование
    $message_safe = str_replace(array('[B]', '[/B]'), array('<b>', '</b>'), $message_safe);

    $mysql->sql_query("INSERT INTO minichat (time, id_user, login, text, game_id) VALUES ($time, $id_user, '".addslashes($login)."', '".addslashes($message_safe)."', ".intval($id_gt).")");
}


// AJAX обработчик для чата.
// Этот блок будет работать, только если пришел AJAX-запрос к самому файлу minichat.php
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    
    session_start();
    require_once("global.php");
    require_once("modules/mysql.php");
    $mysql = new MySQL();

    $id_gt = $_REQUEST['game_id'] ?? 0;
    $id_user = $_SESSION['id_user'] ?? 0;
    $login = $_SESSION['login'] ?? '';
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send' && !empty($_POST['text'])) {
        add_chat_message($mysql, $id_gt, $id_user, $login, $_POST['text']);
    }

    // В любом случае (и после отправки) показываем обновленный чат
    show_chat($mysql, $id_gt);
    exit();
}

// Если это не AJAX, то это, скорее всего, подключение из gamemenu.php.
// В этом случае мы просто встраиваем HTML-разметку чата.
?>

<div id="chat-container" style="height: 200px; overflow-y: scroll; border: 1px solid #ccc; padding: 5px; margin-bottom: 5px;">
    <!-- Сообщения чата будут загружены сюда -->
</div>
<form id="chat-form">
    <input type="text" id="chat-message-input" style="width: 80%;" autocomplete="off" placeholder="Введите сообщение...">
    <button type="submit">Отправить</button>
</form>

<script>
$(document).ready(function() {
    const GAME_ID = <?php echo json_encode($id_gt); ?>;

    function updateChat() {
        if (!GAME_ID) return;
        $.ajax({
            url: 'minichat.php',
            type: 'POST',
            data: { 
                game_id: GAME_ID,
                action: 'get'
            },
            success: function(response) {
                $('#chat-container').html(response);
                // Прокрутка вниз
                $('#chat-container').scrollTop($('#chat-container')[0].scrollHeight);
            }
        });
    }

    $('#chat-form').submit(function(e) {
        e.preventDefault();
        const message = $('#chat-message-input').val();
        if (message.trim() === '') return;

        $.ajax({
            url: 'minichat.php',
            type: 'POST',
            data: {
                game_id: GAME_ID,
                action: 'send',
                text: message
            },
            success: function(response) {
                $('#chat-container').html(response);
                $('#chat-message-input').val('');
                 $('#chat-container').scrollTop($('#chat-container')[0].scrollHeight);
            }
        });
    });

    // Первоначальная загрузка и периодическое обновление
    updateChat();
    setInterval(updateChat, 3000); // Обновлять каждые 3 секунды
});
</script>
