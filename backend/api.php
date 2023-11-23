<?php
include 'config.php';
header('Access-Control-Allow-Origin: http://localhost:9000');
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS");

$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];


switch (true) {
    case preg_match("/^\/api\/test\/expense$/", $requestUri):
        all_expense($requestMethod);
        break;

    case preg_match("/^\/api\/test\/expense\/(\d+)$/", $requestUri, $matches):
        $id = $matches[1]; // Извлечение ID
        expense($requestMethod, $id);
        break;


    default:
        sendJsonResponse(['message' => 'Not Found'], 404);
}

//эндпоинт для определенного расхода
function expense($method,$id) {
    $mysqli = new mysqli(HOST, USER, PASS, DB);

    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    //Проверка в базе на существование
    $stmt = $mysqli->prepare("SELECT * FROM expense WHERE ID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }


    if(empty($data)){
        sendJsonResponse(['success' => false, 'error' => "Запись с таким ID не найдена"], 400);
        return;
    }

    switch ($method) {
        case 'GET':
            $mysqli->close();
            header('Content-Type: application/json');
            echo json_encode($data);
            break;
        case 'PATCH':

            $inputJSON = file_get_contents('php://input');
            $input = json_decode($inputJSON, TRUE); // Преобразуем JSON в массив

            $requiredFields = ['comment', 'sum', 'date'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    sendJsonResponse(['success' => false, 'error' => "Не заполнено обязательное поле $field"], 400);
                    return;
                }
            }

            $stmt = $mysqli->prepare("UPDATE expense SET comment=?, sum=?, date=? WHERE id=?");
            $stmt->bind_param("sdss", $input['comment'], $input['sum'], $input['date'], $id);

            if ($stmt->execute()) {
                sendJsonResponse(['success' => true, 'notification'=>['title'=>'Изменения сохранены',"type"=> "success"]]);
            } else {
                sendJsonResponse(['success' => false, 'notification'=>['title'=>'Произошла ошибка записи '.$stmt->error ,"type"=> false]]);
            }
            break;
        case 'DELETE':

            $stmt = $mysqli->prepare("DELETE FROM expense WHERE id=?");
            $stmt->bind_param("i",  $id);
            if ($stmt->execute()) {
                sendJsonResponse(['success' => true, 'notification'=>['title'=>'Позиция удалена',"type"=> "success"]]);
            } else {
                sendJsonResponse(['success' => false, 'notification'=>['title'=>'Произошла ошибка записи '.$stmt->error ,"type"=> false]]);
            }
            break;
        case 'OPTIONS':
            sendJsonResponse(['message' => 'Запрос успешно обработан']);
            break;
        default:
            sendJsonResponse(['message' => 'Метод недоступен'], 405);
    }
    $stmt->close();
}

//эндпоинт для вывода всех расходов
function all_expense($method) {
    $mysqli = new mysqli(HOST, USER, PASS, DB);

    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }
    switch ($method) {
        case 'GET':

            $sql = "SELECT * FROM expense";
            $result = $mysqli->query($sql);

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            header('Content-Type: application/json');
            echo json_encode($data);
            break;
        case 'POST':

            $comment = isset($_POST['comment']) ? $mysqli->real_escape_string($_POST['comment']) : '';
            $sum = isset($_POST['sum']) ? $mysqli->real_escape_string($_POST['sum']) : '';
            $date = isset($_POST['date']) ? $mysqli->real_escape_string($_POST['date']) : '';

            if(empty($comment)){
                sendJsonResponse(['success' => false, 'error' => "Не заполнено обязательное поле comment"], 400);
                return;
            }
            if(empty($sum)){
                sendJsonResponse(['success' => false, 'error' => "Не заполнено обязательное поле sum"], 400);
                return;
            }
            if(empty($date)){
                sendJsonResponse(['success' => false, 'error' => "Не заполнено обязательное поле date"], 400);
                return;
            }


            $stmt = $mysqli->prepare("INSERT INTO expense (comment, sum, date) VALUES (?, ?, ?)");
            $stmt->bind_param("sds", $comment, $sum, $date);
            header('Content-Type: application/json');
            header('X-Requested-With: XMLHttpRequest');
            if ($stmt->execute()) {
                sendJsonResponse(['success' => true, 'notification'=>['title'=>'Позиция добавлена',"type"=> "success"]]);
            } else {
                sendJsonResponse(['success' => false, 'notification'=>['title'=>'Произошла ошибка записи '.$mysqli->error ,"type"=> false]]);
            }
            $stmt->close();

            break;

        case 'OPTIONS':
            sendJsonResponse(['message' => 'Запрос успешно обработан']);
            break;
        default:
            sendJsonResponse(['message' => 'Метод недоступен'], 405);
    }
}

// Функция для отправки JSON ответов
function sendJsonResponse($data, $statusCode = 200) {
    header('Content-Type: application/json');
    header("HTTP/1.1 $statusCode " . getStatusMessage($statusCode));
    echo json_encode($data);
}

function getStatusMessage($statusCode) {
    $statusMessages = [
        200 => 'OK',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
    ];
    return $statusMessages[$statusCode] ?? '';
}