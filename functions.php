<?php
function include_template($name, $data) {
    $name = 'templates/' . $name;
    $result = '';

    if (!file_exists($name)) {
        return $result;
    }

    ob_start();
    extract($data);
    require $name;

    $result = ob_get_clean();

    return $result;
}
//Функция для форматирования цены и добавления знака рубля к ней
function cost_formatting($cost) {
    $cost = ceil($cost);
    if ($cost >= 1000) {
        $cost = number_format($cost,0,'',' ');
    }
    $cost .= " ₽";
    return $cost;
}

//Функция для вывода оставшегося времени действия лота
function lottime_left() {
    $time_left = strtotime('tomorrow') - time();
    $hours = floor($time_left / 3600);
    $minutes = floor(($time_left % 3600) / 60);
    if ($minutes < 10) {
        $minutes = 0 . $minutes;
    }

    if ($hours < 10) {
        $hours = 0 . $hours;
    }

    $time_left = $hours . ':' . $minutes;
    return $time_left;
}

//Функция для добавления пользователя
function add_user($connect, $user, $password) {
    $sql = 'INSERT INTO users (`add_date`, `email`, `username`, `password`, `avatar`, `contacts`) VALUES (NOW(), ?, ?, ?, ?, ?)';

    $stmt = db_get_prepare_stmt($connect, $sql, [$user['email'], $user['name'], $password, $user['avatar'], $user['contacts']]);
    $res = mysqli_stmt_execute($stmt);
    return $res;
}

//Функция для получения id пользователя по email
function get_user_by_email($connect, $email) {
    $sql = "SELECT `id` FROM users WHERE `email` = '$email'";
    $res = mysqli_query($connect, $sql);
    return $res;
}

//Функция для получения всех данных о пользователе по email и сохранения их в массив
function get_user_all_by_email($connect, $email) {
    $sql = "SELECT * FROM users WHERE `email` = '$email'";
    if ($result = mysqli_query($connect, $sql)) {
        $user = mysqli_fetch_array($result, MYSQLI_ASSOC);
        return $user;
    }
}

//Функция для добавления лота
function add_lot($connect, $lot) {
    $sql = 'INSERT INTO lots (`creation_date`, `author_id`, `category_id`, `title`, `desc`, `picture`, `start_price`, `completion_date`, `step`) VALUES (NOW(), 1, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = db_get_prepare_stmt($connect, $sql, [$lot['category'], $lot['title'], $lot['desc'], $lot['lot_picture'], $lot['start_price'], $lot['completion_date'], $lot['step']]);
    $res = mysqli_stmt_execute($stmt);
    return $res;
}

//Функция для получения списка новых, открытых лотов
function get_lots($connect) {
    $sql = 'SELECT lots.`id`, lots.`title` AS `lot_title`, `start_price`, `picture`, MAX(`bet_amount`), categories.`title` AS `category_title` FROM lots '
         . 'LEFT JOIN bets ON lots.id = bets.lot_id '
         . 'INNER JOIN categories ON lots.category_id = categories.id '
         . 'WHERE `winner_id` IS NULL '
         . 'GROUP BY lots.`id` '
         . 'ORDER BY lots.`creation_date` DESC';

    if ($result = mysqli_query($connect, $sql)) {
        $lots = mysqli_fetch_all($result, MYSQLI_ASSOC);
        return $lots;
    }
    else {
        error_show(mysqli_error($connect));
    }
}

//Функция для получения лота по id из параметра запроса
function get_lot_by_id($connect, $lot_id) {
    $sql = 'SELECT lots.`id`, lots.`title` AS `lot_title`, `desc`, `start_price`, `picture`, MAX(`bet_amount`) AS `current_bet`, categories.`title` AS `category_title` FROM lots '
         . 'LEFT JOIN bets ON lots.id = bets.lot_id '
         . 'INNER JOIN categories ON lots.category_id = categories.id '
         . 'WHERE lots.`id` =' .$lot_id;

    if ($result = mysqli_query($connect, $sql)) {
        $lot = mysqli_fetch_assoc($result);
        return $lot;
    }
    else {
        error_show(mysqli_error($connect));
    }
}

//Функция для получения списка категорий
function get_categories($connect) {
    $sql = 'SELECT `id`, `title` FROM categories';
    $res = mysqli_query($connect, $sql);

    if($res) {
        $categories = mysqli_fetch_all($res, MYSQLI_ASSOC);
        return $categories;
    }
    else {
        error_show(mysqli_error($connect));
    }
}

//Функция для вывода ошибки
function error_show($error) {
    $page_content = include_template('error.php', [
        'error' => $error
    ]);
    $layout_content = include_template('error_layout.php', [
        'content' => $page_content,
        'is_auth' => $is_auth,
        'username' => $user_name,
        'title' => 'Ошибка',
    ]);
    print $layout_content;
    die();
}

//Функция для вывода страницы 404
function error404_show() {
    $page_content = include_template('404.php', []);
    $layout_content = include_template('error_layout.php', [
        'content' => $page_content,
        'is_auth' => $is_auth,
        'username' => $user_name,
        'title' => 'Ошибка',
    ]);
    print $layout_content;
    die();
}

function db_get_prepare_stmt($link, $sql, $data = []) {
    $stmt = mysqli_prepare($link, $sql);
    if ($data) {
        $types = '';
        $stmt_data = [];
        foreach ($data as $value) {
            $type = null;
            if (is_int($value)) {
                $type = 'i';
            }
            else if (is_string($value)) {
                $type = 's';
            }
            else if (is_double($value)) {
                $type = 'd';
            }
            if ($type) {
                $types .= $type;
                $stmt_data[] = $value;
            }
        }
        $values = array_merge([$stmt, $types], $stmt_data);
        $func = 'mysqli_stmt_bind_param';
        $func(...$values);
    }
    return $stmt;
}

?>
