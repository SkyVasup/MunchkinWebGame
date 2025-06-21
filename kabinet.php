<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once("modules/mysql.php");

if (isset($_SESSION['id_user'])) {
    $id_user = $_SESSION['id_user'];
    $login = $_SESSION['login'];

    if (isset($_GET["profile"]) && $_GET["profile"] == "my") {
        $getuinfo = $mysql->sql_query("SELECT * FROM users WHERE id_user=" . intval($id_user));
        $udata = mysqli_fetch_assoc($getuinfo);

        $get_stats = $mysql->sql_query("SELECT * FROM statistic_game WHERE id_user=" . intval($id_user));
        $countgames = mysqli_num_rows($get_stats);

        $get_visctories = $mysql->sql_query("SELECT * FROM statistic_game WHERE id_user=" . intval($id_user) . " AND winner='" . $login . "'");
        $victories = mysqli_num_rows($get_visctories);

        $get_new_msg = $mysql->sql_query("SELECT * FROM messages WHERE `to`='" . intval($id_user) . "' AND isread=0");
        $new_msg_cnt = mysqli_num_rows($get_new_msg);
        if ($new_msg_cnt < 1) {
            $new_msg_cnt = "нет новых";
        }

        echo "<div align='center'><h2>Мой профиль</h2></div>";
        echo "<table class='profile' width='400' align='center'>";
        echo "<tr><td class='tdhead' colspan='2'>Информация пользователя</td></tr>";

        function u($field, $label, $default = '—') {
            global $udata;
            echo "<tr><td><strong>$label:</strong></td><td>" . (!empty($udata[$field]) ? htmlspecialchars($udata[$field]) : $default) . "</td></tr>";
        }

        echo "<tr><td colspan='2' align='center'><img class='useravatar' src='" .
             (!empty($udata['image']) ? "picture/users/" . $udata['image'] : "picture/users/default.png") .
             "' width='120' height='120' alt='Avatar' style='border-radius:8px; margin:10px auto; box-shadow: 0 0 5px #000;'></td></tr>";

        u('login', 'Логин');
        u('name', 'Имя');
        u('sname', 'Фамилия');
        u('email', 'E-mail');
        echo "<tr><td><strong>Дата регистрации:</strong></td><td>" . (isset($udata['timeactive']) ? date("d.m.Y", $udata['timeactive']) : '—') . "</td></tr>";
        u('www', 'Сайт', 'Не указан');
        u('about', 'О себе', 'Нет информации');
        u('city', 'Город', 'Не указан');
        u('icq', 'ICQ', 'Не указан');
        u('skype', 'Skype', 'Не указан');
        u('exper', 'Опыт');
        u('u_level', 'Уровень');

        echo "<tr><td><strong>Игр сыграно:</strong></td><td>{$countgames}</td></tr>";
        echo "<tr><td><strong>Побед:</strong></td><td>{$victories}</td></tr>";
        echo "<tr><td><strong>Сообщения:</strong></td><td>{$new_msg_cnt}</td></tr>";

        echo "<tr><td colspan='2' align='center'><br><a class='msgbtn' href='index.php?profile=change'>Изменить профиль</a> <a class='msgbtn' href='index.php?profile=settings'>Настройки</a></td></tr>";
        echo "</table><br><div style='text-align:center;'>© ABCLNTS 2025</div>";
    }

    if (isset($_GET["profile"]) && $_GET["profile"] == "change") {
        $getuinfo = $mysql->sql_query("SELECT * FROM users WHERE id_user=" . intval($id_user));
        $udata = mysqli_fetch_assoc($getuinfo);

        echo "<h2 align='center'>Редактировать профиль</h2>";
        echo "<form method='post'>";
        echo "<table class='profile' width='400' align='center'>";
        echo "<tr><td class='tdhead' colspan='2'>Редактирование</td></tr>";

        function f($name, $label, $val) {
            echo "<tr><td><strong>$label:</strong></td><td><input type='text' name='$name' value='" . htmlspecialchars($val) . "' /></td></tr>";
        }

        f('name', 'Имя', $udata['name'] ?? '');
        f('sname', 'Фамилия', $udata['sname'] ?? '');
        f('www', 'Сайт', $udata['www'] ?? '');
        f('about', 'О себе', $udata['about'] ?? '');
        f('city', 'Город', $udata['city'] ?? '');
        f('icq', 'ICQ', $udata['icq'] ?? '');
        f('skype', 'Skype', $udata['skype'] ?? '');

        echo "<tr><td colspan='2' align='center'><input type='submit' value='Сохранить' class='msgbtn' /></td></tr>";
        echo "</table></form>";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $sname = $_POST['sname'] ?? '';
            $www = $_POST['www'] ?? '';
            $about = $_POST['about'] ?? '';
            $city = $_POST['city'] ?? '';
            $icq = $_POST['icq'] ?? '';
            $skype = $_POST['skype'] ?? '';

            $mysql->sql_query("UPDATE users SET name='$name', sname='$sname', www='$www', about='$about', city='$city', icq='$icq', skype='$skype' WHERE id_user='$id_user'");
            echo "<div style='text-align:center;'>Профиль обновлён. <a href='index.php?profile=my'>Вернуться</a></div>";
        }
    }
} else {
    echo "Вы не авторизованы.";
}
