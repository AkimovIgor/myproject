<?php

require_once('db.php');

// старт сессии
session_start();

// если пользователь не авторизован
if (!isset($_SESSION['user']['is_login']) && !isset($_COOKIE['user']['is_login'])) {
    header('Location: /'); // редирект на главную
    exit;
}

// если сессия с флеш-сообщениями не существует
if (!isset($_SESSION['messages'])) {
    $_SESSION['messages'] = []; // создать сессию
} else {
    // если сессия существует, записать значения в соответствующие переменные
    $errors = $_SESSION['messages']['errors'] ? $_SESSION['messages']['errors'] : null;
    $success = $_SESSION['messages']['success'] ? $_SESSION['messages']['success'] : null;

    // уничтожить сессию
    unset($_SESSION['messages']);
}
//var_dump($_SESSION['messages']); die;
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

// получение изображения текущего пользователя из базы
$user = getUserImage($pdo, $email);

// запись в сессию названия полученного изображения для отображения
$_SESSION['image'] = $user['image'];

/**
 * Редактирование профиля
 *
 * @param [object] $pdo
 * @param [string] $email
 * @param [array] $user
 * @return void
 */
function changeProfile($pdo, $email, $user) {

    if (empty($_POST)) {
        return false;
    }

    // почта текущего пользователя
    $currentUser = $email;

    // получение данных с полей
    $name = trim(htmlspecialchars($_POST['name']));
    $email = trim(htmlspecialchars($_POST['email']));

    $validation = true; // статус валидации
    $messages = [];     // массив для флеш-сообщений

    // получение массива данных картинки
    $file = $_FILES['image'];
    
    // если картинка выбрана из поля
    if (!empty($file['name'])) {

        // каталог для загружаемых файлов
        $uploadDir = __DIR__ . '/uploads/';

        // доступные форматы загружаемых файлов
        $availableFormats = ['jpg', 'jpeg', 'png', 'gif'];

        // получение формата загружаемого файла
        $format = mb_substr($file['name'], mb_strripos($file['name'], '.') + 1);

        // формируем имя файла
        $fileName = uniqid() . '.' . $format;

        // полный путь к месту назначения (папка uploads)
        $uploadFile = $uploadDir . $fileName;
        
        // валидация формата
        for($i = 0; $i < count($availableFormats); $i++) {
            if ($format == $availableFormats[$i]) {
                $isAvailable = true;
                break;
            } 
        }

        // если формат недоступен
        if (!$isAvailable) {
            $validation = false;
            $messages['errors']['format'] = 'Недопустимый формат файла! Допустимые форматы: ' . implode(', ', $availableFormats);
        } else {
            // если такая картинка уже существует
            if(file_exists($uploadDir . $user['image'])) {
                // удалить старую картинку
                unlink($uploadDir . $user['image']);
                // загрузить новую картинку
                move_uploaded_file($file['tmp_name'], $uploadFile);
            } else {
                // просто загрузить картинку
                move_uploaded_file($file['tmp_name'], $uploadFile);
            }
        }

    } else {
        // если картинка не выбрана
        $fileName = null;
    }

    // правила валидации полей формы и добавление сообщений для вывода под полями
    if (empty($name)) {
        $validation = false;
        $messages['errors']['name'] = 'Ведите имя!';
    }
    // валидация на уже существующий email
    $email_exist = checkEmail($pdo, $email, $currentUser);
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

    // если поля прошли валидацию
    if ($validation == true) {

        // формирование SET конструкции для выполнения запроса
        $set = "name = :name, email = :email";

        // данные для заполнения
        $data = [
            'name' => $name,
            'email' => $email
        ];
        
        // если в поле было выбрано изображение
        if ($fileName) {
            // дописать в SET конструкцию для запроса
            $set .= ", image = :image";
            // добавить в массив данных имя изображения
            $data['image'] = $fileName;
        }

        // sql-запрос на обновление данных пользователя
        $sql = "UPDATE users 
                SET $set 
                WHERE email = '$currentUser'";

        // подготовка запроса
        $stmt = $pdo->prepare($sql);
        // выполнение запроса
        $stmt->execute($data);

        // освежить данные в сессиях и куки, удаляя их, а затем устанавливая заново
        if (isset($_SESSION['user'])) {
            unset($_SESSION['user']);
            $userData['name'] = $name;
            $userData['email'] = $email;
            $userData['is_login'] = true;
            $_SESSION['user'] = $userData;
        }
        if(isset($_COOKIE['user'])) {
            setcookie('user[is_login]', '', time()-5);

            setcookie('user[name]', $name, time() + 60 * 2);
            setcookie('user[email]', $email, time() + 60 * 2);
            setcookie('user[is_login]', true, time() + 60 * 2);
        }
        
        // добавление флеш-сообщения
        $messages['success'] = 'Изменения сохранены!';
    }
    // редирект на текущую страницу
    header('Location: profile.php');
    // запись флеш-сообщений в сессию
    $_SESSION['messages'] = $messages;
}

/**
 * Получение аватара текущего пользователя
 *
 * @param [object] $pdo
 * @param [string] $currentUser
 * @return void
 */
function getUserImage($pdo, $currentUser) {
    // выбираем аватар текущего пользователя
    $sql = "SELECT image 
            FROM users 
            WHERE email = '$currentUser'
            LIMIT 1";

    $stmt = $pdo->query($sql);
    $user = $stmt->fetch();
    return $user;
}

/**
 * Проверка уже существующего email в базе
 *
 * @param [object] $pdo
 * @param [string] $email
 * @return boolean
 */
function checkEmail($pdo, $email, $currentUser) {
    // Выбор всех полей email в базе
    $sql = "SELECT email FROM users";
    // выполнение запроса
    $stmt = $pdo->query($sql);

    // проверка соответствия введенного email с другими
    while ($row = $stmt->fetch()) {
        if ($row['email'] == $email && $row['email'] != $currentUser) return true;
    }

    return false;
}

// вызов функции регистрации
if (isset($_POST['edit'])) {
    changeProfile($pdo, $email, $user);
}

// если значение сессионой переменной image равно изображению по умолчанию, загружать его с одной папки
if ($_SESSION['image'] == 'no-user.jpg') {
    $image = 'img/' . $_SESSION['image'];
} else {
    // иначе, загружать с другой папки
    $image = 'uploads/' . $_SESSION['image'];
}

// уничтожение сессий
unset($_SESSION['image']);

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
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header"><h3>Профиль пользователя</h3></div>

                        <div class="card-body">
                            <!-- Вывод флеш-сообщения в случае успеха -->
                            <?php if (isset($success)): ?>
                                <div class="alert alert-success" role="alert">
                                    <?= $success ?>
                                </div>
                            <?php endif; ?>

                            <form action="" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="exampleFormControlInput1">Имя</label>
                                            <input type="text" class="form-control <?php if (isset($errors['name'])): ?>is-invalid <?php endif; ?>" name="name" id="exampleFormControlInput1" value="<?= $name ?>">
                                            <?php if (isset($errors['name'])): ?> 
                                                <span class="invalid-feedback" role="alert">
                                                    <strong><?= $errors['name']; ?></strong>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="form-group">
                                            <label for="exampleFormControlInput2">Email</label>
                                            <input type="email" class="form-control <?php if (isset($errors['email'])): ?>is-invalid <?php endif; ?>" name="email" id="exampleFormControlInput2" value="<?= $email ?>">
                                            <?php if (isset($errors['email'])): ?> 
                                                <span class="invalid-feedback" role="alert">
                                                    <strong><?= $errors['email']; ?></strong>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="form-group">
                                            <label for="exampleFormControlInput3">Аватар</label>
                                            <input type="file" class="form-control <?php if (isset($errors['format'])): ?>is-invalid <?php endif; ?>" name="image" id="exampleFormControlInput3">
                                            <?php if (isset($errors['format'])): ?> 
                                                <span class="invalid-feedback" role="alert">
                                                    <strong><?= $errors['format']; ?></strong>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <img src="<?= $image ?>" alt="" class="img-fluid">
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" name="edit" class="btn btn-warning">Редактировать</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-12" style="margin-top: 20px;">
                    <div class="card">
                        <div class="card-header"><h3>Безопасность</h3></div>

                        <div class="card-body">
                            

                            <form action="password.php" method="post">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="exampleFormControlInput4">Текущий пароль</label>
                                            <input type="password" name="current" class="form-control <?php if (isset($errors['current'])): ?> is-invalid <?php endif; ?>" id="exampleFormControlInput4">
                                            <?php if (isset($errors['current'])): ?> 
                                                <span class="invalid-feedback" role="alert">
                                                    <strong><?= $errors['current']; ?></strong>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="form-group">
                                            <label for="exampleFormControlInput5">Новый пароль</label>
                                            <input type="password" name="password" class="form-control <?php if (isset($errors['password']) || isset($errors['password_equal'])): ?> is-invalid <?php endif; ?>" id="exampleFormControlInput5">
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

                                        <div class="form-group">
                                            <label for="exampleFormControlInput6">Новый пароль еще раз</label>
                                            <input type="password" name="password_confirmation" class="form-control <?php if (isset($errors['password_confirm']) || isset($errors['password_equal'])): ?> is-invalid <?php endif; ?>" id="exampleFormControlInput6">
                                            <?php if (isset($errors['password_confirm'])): ?> 
                                                <span class="invalid-feedback" role="alert">
                                                    <strong><?= $errors['password_confirm']; ?></strong>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <button type="submit" class="btn btn-success">Сохранить</button>
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
