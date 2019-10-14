<?php

require_once('db.php');

session_start();

// если сессия с данными пользователя не существует
if (!isset($_SESSION['user'])) {
    // если существуют куки с данными
    if (isset($_COOKIE['user'])) {
        $isLogin = $_COOKIE['user']['is_login'];
        $name = $_COOKIE['user']['name'];
        $email = $_COOKIE['user']['email'];
    }
    
} else {
    $isLogin = $_SESSION['user']['is_login'];
    $name = $_SESSION['user']['name'];
    $email = $_SESSION['user']['email'];
}

function userLogin($pdo) {
    
    if (empty($_POST)) {
        return false;
    }

    // получение данных из полей
    $email = trim(htmlspecialchars($_POST['email']));
    $password = trim(htmlspecialchars($_POST['password']));

    // статус чекбокса "Запомнить меня"
    $rememberMe = $_POST['remember'];

    $validation = true; // статус валидации
    $messages = [];     // массив для флеш-сообщений
    
    // создаем сессию для автозаполнения полей
    $_SESSION['fieldData']['email'] = $email;
    $_SESSION['fieldData']['password'] = $password;

    // валидация для корректного ввода email
    if (!preg_match("/^(?:[a-z0-9]+(?:[-_.]?[a-z0-9]+)?@[a-z0-9_.-]+(?:\.?[a-z0-9]+)?\.[a-z]{2,5})$/i", $email)) {
        $validation = false;
        $messages['errors']['email'] = 'Введенный вами email не соответствует формату!';
    }
    if (empty($email)) {
        $validation = false;
        $messages['errors']['email'] = 'Введите email!';
    }
    if (strlen($password) < 6 ) {
        $validation = false;
        $messages['errors']['password'] = 'Минимальная длина пароля 6 символов';
    }
    if (empty($password)) {
        $validation = false;
        $messages['errors']['password'] = 'Введите пароль!';
    }

    // если поля прошли валидацию
    if ($validation == true) {

        // формируем sql-запрос 
        $sql = "SELECT * 
                FROM users 
                WHERE email = '$email'
                LIMIT 1";

        // выполнение запроса
        $stmt = $pdo->query($sql);

        // получение данных
        $row = $stmt->fetch();

        $password_unhash = password_verify($password, $row['password']); // дешифрование пароля
        
        // если пользователь существует в базе данных
        if ($row['email'] && $password_unhash) {
            // добавление флеш-сообщения
            $messages['success'] = 'Вход успешно выполнен!';
            
            // стоит галочка "Запомнить меня"
            if ($rememberMe) {
                setcookie('user[name]', $row['name'], time() + 60 * 2);
                setcookie('user[email]', $email, time() + 60 * 2);
                setcookie('user[password]', $password, time() + 60 * 2);
                setcookie('user[is_login]', true, time() + 60 * 2);
            } else {
                // создаем массив данных пользователя
                $userData['name'] = $row['name'];
                $userData['email'] = $row['email'];
                $userData['password'] = $row['password'];
                $userData['is_login'] = true;
            }

            // создаем сессию для хранения данных пользователя
            $_SESSION['user'] = $userData;

            // редирект на главную
            header('Location: /');
        } else {
            if (!$row['email']) {
                $messages['errors']['email'] = 'Неверный email!';
            }
            if (!$password_unhash) {
                $messages['errors']['password'] = 'Неверный пароль!';
            }
        }
    }
    
    // заносим массив флеш-сообщений в сессию
    $_SESSION['messages'] = $messages;
}

userLogin($pdo);

// переменная для вывода мини-сообщений под полями
$errors = $_SESSION['messages']['errors'];

// переменная для автозаполнения полей
if (isset($_COOKIE['user'])) {
    $fieldData = $_COOKIE['user'];
} else {
    $fieldData = $_SESSION['fieldData'];
}

// данные пользователя
$user =  $_SESSION['user'];
// уничтожение сессий
unset($_SESSION['messages']['errors']);
unset($_SESSION['fieldData']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Comments</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="css/app.css" rel="stylesheet">

    <!-- Scripts -->
    <script src="markup/js/jquery.min.js" defer></script>
    <script src="markup/js/bootstrap.js" defer></script>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="/">
                    Project
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav mr-auto">

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Authentication Links -->
                        <?php if (isset($isLogin)): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?= $name ?>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                <a class="dropdown-item" href="profile.php">Профиль</a>
                                <a class="dropdown-item" href="logout.php">Выход</a>
                            </div>
                        </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="login.php">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="register.php">Register</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">Login</div>

                            <div class="card-body">
                                <form method="POST" action="">

                                    <div class="form-group row">
                                        <label for="email" class="col-md-4 col-form-label text-md-right">E-Mail Address</label>

                                        <div class="col-md-6">
                                            <input id="email" type="email" class="form-control <?php if (isset($errors['email'])): ?>is-invalid <?php endif; ?>" name="email"  autocomplete="email" autofocus value="<?= $fieldData['email']; ?>">
                                            <?php if (isset($errors['email'])): ?> 
                                                <span class="invalid-feedback" role="alert">
                                                    <strong><?= $errors['email']; ?></strong>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="password" class="col-md-4 col-form-label text-md-right">Password</label>

                                        <div class="col-md-6">
                                            <input id="password" type="password" class="form-control <?php if (isset($errors['password'])): ?> is-invalid <?php endif; ?>" name="password"  autocomplete="current-password" value="<?= $fieldData['password']; ?>">
                                            <?php if (isset($errors['password'])): ?> 
                                                <span class="invalid-feedback" role="alert">
                                                    <strong><?= $errors['password']; ?></strong>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-6 offset-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="remember" id="remember" >

                                                <label class="form-check-label" for="remember">
                                                    Remember Me
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row mb-0">
                                        <div class="col-md-8 offset-md-4">
                                            <button type="submit" class="btn btn-primary">
                                               Login
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
