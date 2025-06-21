<?php
// Подключаем класс MySQL
require_once("modules/mysql.php");

// Определяем функцию safform, если она не существует
if (!function_exists('safform')) {
    function safform($data) {
        global $mysql;
        return mysqli_real_escape_string($mysql->connection, trim($data));
    }
}

// Проверяем, существует ли сессия, и начинаем её только если нужно
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<table align="center" width="100%" cellpadding="6" cellspacing="6">
 <tbody>
    <tr>
        <td align="center" width="20%" valign="undefined">
            <h2>Регистрация</h2>
        </td>
    </tr>
    <tr>
        <td align="center">
            <form action="" method="post">
            <table cellpadding="6" cellspacing="6">
            <tr>
               <td align="left">Логин* :</td>
               <td colspan="2"><input type="text" name="rLogin" value="<?php echo isset($_POST["rLogin"]) ? htmlspecialchars($_POST["rLogin"]) : ''; ?>" size="25" maxlength="15" /></td>
            </tr>
            <tr>
               <td align="left">Пароль* :</td>
               <td colspan="2"><input type="password" name="rPass" value="<?php echo isset($_POST["rPass"]) ? htmlspecialchars($_POST["rPass"]) : ''; ?>" size="25" maxlength="15" /></td>
            </tr>
            <tr>
               <td align="left">Повторите пароль* :</td>
               <td colspan="2"><input type="password" name="rPass2" value="<?php echo isset($_POST["rPass2"]) ? htmlspecialchars($_POST["rPass2"]) : ''; ?>" size="25" maxlength="15" /></td>
            </tr>
            <tr>
               <td align="left">E-mail* :</td>
               <td colspan="2"><input type="text" name="rEmail" value="<?php echo isset($_POST["rEmail"]) ? htmlspecialchars($_POST["rEmail"]) : ''; ?>" size="25" maxlength="30" /></td>
            </tr>
            <tr>
               <td align="left">Имя :</td>
               <td colspan="2"><input type="text" name="rName" value="<?php echo isset($_POST["rName"]) ? htmlspecialchars($_POST["rName"]) : ''; ?>" size="25" maxlength="15" /></td>
            </tr>
            <tr>
               <td align="left">Фамилия :</td>
               <td colspan="2"><input type="text" name="rSname" value="<?php echo isset($_POST["rSname"]) ? htmlspecialchars($_POST["rSname"]) : ''; ?>" size="25" maxlength="15" /></td>
            </tr>
            <tr>
                <td align="left">Год рождения:</td>
                <td align="left" colspan="2">
                    <input type="text" name="bday" value="<?php echo isset($_POST["bday"]) && strlen($_POST["bday"]) > 0 ? htmlspecialchars($_POST["bday"]) : 'ДД'; ?>" size="3" />
                    <input type="text" name="bmth" value="<?php echo isset($_POST["bmth"]) && strlen($_POST["bmth"]) > 0 ? htmlspecialchars($_POST["bmth"]) : 'ММ'; ?>" size="3" />
                    <input type="text" name="byar" value="<?php echo isset($_POST["byar"]) && strlen($_POST["byar"]) > 0 ? htmlspecialchars($_POST["byar"]) : 'ГГГГ'; ?>" size="3" />
                </td>
            </tr>
            <tr>
               <td align="left">Город :</td>
               <td colspan="2"><input type="text" name="rCity" value="<?php echo isset($_POST["rCity"]) ? htmlspecialchars($_POST["rCity"]) : ''; ?>" size="25" maxlength="15" /></td>
            </tr>
            <tr>
               <td align="left">Пол :</td>
               <td align="left" colspan="2">                
                    <input name="rsex" type="radio" value="m" checked>муж.
                    <input name="rsex" type="radio" value="f">жен.
                </td>
            </tr>
            <tr>
               <td align="left">Введите ответ* :</td>
               <td><input type="text" name="rAnswer" value="" size="10" maxlength="10" /></td>
               <td><img src="./pic.php?<?php echo time(); ?>" alt="Капча" /></td>
            </tr>                    
            <tr>
               <td></td>
               <td colspan="2">
                   <input style="border:#524231 1px solid; border-radius:5px; -moz-border-radius:5px; padding:6px; cursor:pointer;" type="reset" name="reset" value="Очистить" />
                   <input style="border:#524231 1px solid; border-radius:5px; -moz-border-radius:5px; padding:6px; cursor:pointer;" type="submit" name="ok" value="Готово" />
                </td>
            </tr>
            </table>
            </form>
       </td>
    </tr>  
</tbody>
</table>

<?php
if (isset($_POST['ok'])) { // Проверяем кнопку "Готово"
    $rLogin = safform($_POST['rLogin']);
    $rPass  = safform($_POST['rPass']);
    $rPass2 = safform($_POST['rPass2']);
    $rEmail = safform($_POST['rEmail']);
    $rName = safform($_POST['rName']);
    $rSname = safform($_POST['rSname']);
    $rCity = safform($_POST['rCity']);
    $rAnswer = safform($_POST['rAnswer']);
    $rsex = safform($_POST['rsex']);
    
    // Дата рождения
    if (is_numeric($_POST["bday"]) && is_numeric($_POST["bmth"]) && is_numeric($_POST["byar"])) {
        $birth = mktime(0, 0, 0, $_POST["bmth"], $_POST["bday"], $_POST["byar"]);
        $chbirth = $birth;
    } else {
        $chbirth = "";
    }

    $error = "";
    
    if (strlen($rLogin) < 1) {
        $error .= "Поле логин должно быть заполнено<br/>";
    }
    
    if (strlen($rEmail) < 1) {
        $error .= "Поле email должно быть заполнено<br/>";
    }
    
    if (!preg_match("/^[a-zA-Z0-9_\.\-]+@([a-zA-Z0-9\-]+\.)+[a-zA-Z]{2,6}$/", $rEmail)) {
        $error .= "Указанный E-mail имеет недопустимый формат<br/>";
    }
    
    if (strlen($rPass) < 3) {
        $error .= "Пароль должен быть длиной 3 и более символов<br/>";
    }
    
    if ($rPass !== $rPass2) {
        $error .= "Пароли не совпадают<br/>";
    }
    
    if (isset($_SESSION['regcode']) && $rAnswer !== $_SESSION['regcode']) {
        $error .= "Неправильно введен ответ на вопрос-картинку<br/>";
    }   

    // Проверка на существование логина и email
    $checklogin = $mysql->sql_query("SELECT * FROM users WHERE login='$rLogin'");
    if (!$checklogin) {
        $error .= "Ошибка проверки логина: " . mysqli_error($mysql->connection) . "<br/>";
    } elseif (mysqli_num_rows($checklogin) > 0) {
        $error .= "Извините, введённый вами логин уже зарегистрирован. Используйте другой логин<br/>";
    }
    
    $checkmail = $mysql->sql_query("SELECT * FROM users WHERE email='$rEmail'");
    if (!$checkmail) {
        $error .= "Ошибка проверки email: " . mysqli_error($mysql->connection) . "<br/>";
    } elseif (mysqli_num_rows($checkmail) > 0) {
        $error .= "Извините, введённый вами email уже использован для регистрации, попробуйте восстановить пароль<br/>";
    }

    if (strlen($error) < 1) {
        // Хешируем пароль
        $mdPassword = md5($rPass);
        $time = time();

        if ($rsex == "f") $rsex = "ж";    
        else $rsex = "м";

        $ip_addr = getIP();

        // Вставка пользователя в таблицу users
        $insert_user = $mysql->sql_query("INSERT INTO users (login, pass, name, sname, email, timeactive, sex, last_ip, u_level, birth, city) VALUES ('$rLogin', '$mdPassword', '$rName', '$rSname', '$rEmail', $time, '$rsex', '$ip_addr', 1, '$chbirth', '$rCity')");
        if (!$insert_user) {
            $error .= "Ошибка при добавлении пользователя: " . mysqli_error($mysql->connection) . "<br/>";
        } else {
            // Получаем Id нового пользователя
            $id_result = $mysql->sql_query("SELECT LAST_INSERT_ID() as id");
            if (!$id_result) {
                $error .= "Ошибка получения ID пользователя: " . mysqli_error($mysql->connection) . "<br/>";
            } else {
                $id_row = mysqli_fetch_assoc($id_result);
                $id = $id_row['id'];
                
                // Создаем аналогичного пользователя в базе форума
                $insert_forum = $mysql->sql_query("INSERT INTO forum_users VALUES ($id, 3, '$rLogin', '$mdPassword', '', '$rEmail', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 'Russian', 'Oxygen', 1, 1304646541, NULL, NULL, $time, '', $time, NULL, NULL, NULL)");
                if (!$insert_forum) {
                    $error .= "Ошибка при добавлении пользователя в форум: " . mysqli_error($mysql->connection) . "<br/>";
                }
            }
        }

        if (strlen($error) < 1) {
            // Сразу активируем пользователя без почты
            $mysql->sql_query("UPDATE users SET user_status = 1 WHERE login='$rLogin'");
            echo "<script>alert('Вы успешно зарегистрировались и можете войти в игру!'); location.href='index.php'</script>";
            unset($_SESSION['regcode']);
            exit();
        }
    }

    if (strlen($error) > 0) {
        echo "<span style=\"color:red\">$error</span>";
    }
}
?>