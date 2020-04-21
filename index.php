<?php
require_once dirname(__FILE__) . '/vendor/autoload.php';

$urlServer = 'http://91.200.42.232/webservice/rest/server.php';
$token = '1818c0cedf5f484f9c767b6b135a12c1';

$userId = 4; // Александр Бурцев

$MoodleRest = new MoodleRest($urlServer, $token);

function p($arr, $console = false){
    if (!$console) {
        echo '<pre>'.print_r($arr, true).'</pre>';
    }
    else{
        print_r($arr);
    }
}

/*
$parametersRequest = [
    "notes" => [
        [
            "userid" => 1, //id of the user the note is about
            "publishstate" => "personal",  //'personal', 'course' or 'site'
            "courseid" => 2,
            "text" => "Тестовое сообщение из <b>WS</b>",
            "format" => 1
        ]
    ]
];

// отправить персональное уведомление
$note = $MoodleRest->request('core_notes_create_notes', $parametersRequest, MoodleRest::METHOD_POST);

p($note);


$parametersRequest = [
    "courseids" => [
        2
    ]
];

// все тесты определенных курстов
$data = $MoodleRest->request('mod_quiz_get_quizzes_by_courses', $parametersRequest, MoodleRest::METHOD_POST);

p($data);
*/

$parametersRequest = [
    "quizid" => 1,
    "userid" => $userId
];

// Вернуть список попыток для данного теста и пользователя.
$data = $MoodleRest->request('mod_quiz_get_user_attempts', $parametersRequest, MoodleRest::METHOD_POST);

p($data);
