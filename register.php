<?php
// Подключение файла соединения с БД
require_once('db.php');

// старт сессии
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

/**
 * Регистрация нового пользователя
 *
 * @param [object] $pdo
 * @return void
 */
function userRegister($pdo) {

    if (empty($_POST)) {
        return false;
    }

    // получение данных с полей
    $name = trim(htmlspecialchars($_POST['name']));
    $email = trim(htmlspecialchars($_POST['email']));
    $password = trim(htmlspecialchars($_POST['password']));
    $password_confirm = trim(htmlspecialchars($_POST['password_confirmation']));

    $password_hash = password_hash($password, PASSWORD_DEFAULT); // шифрование пароля

    $validation = true; // статус валидации
    $messages = [];     // массив для флеш-сообщений

    // записываем данные полей в сессионную переменную для автозаполнения
    $_SESSION['fieldData']['name'] = $name;
    $_SESSION['fieldData']['email'] = $email;
    $_SESSION['fieldData']['password'] = $password;

    // правила валидации полей формы и добавление сообщений для вывода под полями
    if (empty($name)) {
        $validation = false;
        $messages['errors']['name'] = 'Ведите имя!';
    }
    // валидация на уже существующий email
    $email_exist = checkEmail($pdo, $email);
    if ($email_exist) {
        $validation = false;
        $messages['errors']['email'] = 'Введенный вами email уже существует!';
    }
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
    if (empty($password_confirm)) {
        $validation = false;
        $messages['errors']['password_confirm'] = 'Подтвертите пароль!';
    }
    if (!empty($password) && !empty($password_confirm) && $password != $password_confirm) {
        $validation = false;
        $messages['errors']['password_equal'] = 'Пароли не совпадают!';
    }
    
    // если поля прошли валидацию
    if ($validation == true) {
        // формируем sql-запрос 
        $sql = "INSERT INTO users 
                (name, email, password) 
                VALUES (:name, :email, :password)";

        // подготавливаем sql-запрос 
        $stmt = $pdo->prepare($sql);

        // связываение параметров
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password_hash);

        // выполнение запроса
        $stmt->execute();

        // добавление флеш-сообщения
        $messages['success'] = 'Пользователь успешно зарегистрирован!';

        // создаем массив данных пользователя
        $userData['name'] = $name;
        $userData['email'] = $email;
        $userData['password'] = $password;
        $userData['is_login'] = true;

        // создаем сессию для хранения данных пользователя
        $_SESSION['user'] = $userData;

        // заносим массив флеш-сообщений в сессию
        $_SESSION['messages'] = $messages;

        // редирект на главную
        header('Location: /');
    } else {
        $_SESSION['messages'] = $messages;
    }
}

/**
 * Проверка уже существующего email в базе
 *
 * @param [object] $pdo
 * @param [string] $email
 * @return boolean
 */
function checkEmail($pdo, $email) {
    // Выбор всех полей email в базе
    $sql = "SELECT email FROM users";
    // выполнение запроса
    $stmt = $pdo->query($sql);

    // проверка соответствия введенного email с другими
    while ($row = $stmt->fetch()) {
        if ($row['email'] == $email) return true;
    }

    return false;
}

// вызов функции регистрации
userRegister($pdo);

// переменная для вывода мини-сообщений под полями
$errors = $_SESSION['messages']['errors'];

// переменная для автозаполнения полей
$fieldData = $_SESSION['fieldData'];

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
                            <div class="card-header">Register</div>

                            <div class="card-body">
                                <form method="POST" action="">

                                    <div class="form-group row">
                                        <label for="name" class="col-md-4 col-form-label text-md-right">Name</label>

                                        <div class="col-md-6">
                                            <input id="name" type="text" class="form-control <?php if (isset($errors['name'])): ?> @error('name') is-invalid @enderror <?php endif; ?>" name="name" autofocus value="<?= $fieldData['name'] ?>">
                                                <?php if (isset($errors['name'])): ?> 
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong><?= $errors['name']; ?></strong>
                                                    </span>
                                                <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="email" class="col-md-4 col-form-label text-md-right">E-Mail Address</label>

                                        <div class="col-md-6">
                                            <input id="email" type="text" class="form-control <?php if (isset($errors['email'])): ?> @error('email') is-invalid @enderror <?php endif; ?>" name="email" value="<?= $fieldData['email'] ?>">
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
                                            <input id="password" type="password" class="form-control <?php if (isset($errors['password']) || isset($errors['password_equal'])): ?> @error('password') is-invalid @enderror <?php endif; ?>" name="password"  autocomplete="new-password" value="<?= $fieldData['password'] ?>">
                                            <?php if (isset($errors['password'])): ?> 
                                                <span class="invalid-feedback" role="alert">
                                                    <strong><?= $errors['password']; ?></strong>
                                                </span>
                                            <?php endif; ?>

                                            <?php if (isset($errors['password_equal'])): ?> 
                                                <span class="invalid-feedback" role="alert">
                                                    <strong><?= $errors['password_equal']; ?></strong>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="password-confirm" class="col-md-4 col-form-label text-md-right">Confirm Password</label>

                                        <div class="col-md-6">
                                            <input id="password-confirm" type="password" class="form-control <?php if (isset($errors['password_confirm']) || isset($errors['password_equal'])): ?> @error('password_confirmation') is-invalid @enderror <?php endif; ?>" name="password_confirmation"  autocomplete="new-password">
                                            <?php if (isset($errors['password_confirm'])): ?> 
                                                <span class="invalid-feedback" role="alert">
                                                    <strong><?= $errors['password_confirm']; ?></strong>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="form-group row mb-0">
                                        <div class="col-md-6 offset-md-4">
                                            <button type="submit" class="btn btn-primary">
                                                Register
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
