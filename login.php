<?php
if (isset($_POST['login']) && isset($_POST['pass'])) {
    $login = $_POST['login'];
    $pass = $_POST['pass'];
    $canlogin = true;
    $loginerror = "";
    $passwordHash = md5($pass);

    // Экранируем логин для безопасности
    $login_safe = mysqli_real_escape_string($mysql->connection, $login);

    $result = $mysql->sql_query("SELECT * FROM users WHERE login = '$login_safe'");
    
    if (mysqli_num_rows($result) < 1) {
        $canlogin = false;
        $loginerror = "Пользователя с таким логином нет";
    } else {
        $row = mysqli_fetch_assoc($result);

        if ($row['pass'] != $passwordHash) {
            $canlogin = false;
            $loginerror = "Неверный пароль";
        }
    }

    if ($canlogin) {
        if (!isset($row['user_status']) || $row['user_status'] != 1) {
            $canlogin = false;
            $loginerror = "Логин не активирован";
        }
    }

    if ($canlogin) {
        $_SESSION['id_user'] = $row['id_user'];
        $_SESSION['login'] = $row['login'];
        $_SESSION['sex'] = (mb_strtolower($row['sex']) === "м" || mb_strtolower($row['sex']) === "m") ? "man" : "woman";
        $_SESSION['level'] = $row['level'] ?? 1;

        if (isset($_POST['remember'])) {
            $cookvalue = $_SESSION['login'] . "|" . md5($_SESSION['login']) . "|" . $passwordHash;
            setcookie("auth", $cookvalue, time() + 1209600);
        }

        $ip = getIP();
        $mysql->sql_query("UPDATE users SET active = " . time() . ", last_ip = '$ip', last_page = 'index' WHERE id_user = " . $_SESSION['id_user']);
        header("Location: forum/autologin.php?user=$login&pass=$pass");
        exit();
    }
}
?>
