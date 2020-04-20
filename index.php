<?php
require_once dirname(__FILE__) . '/vendor/autoload.php';

$urlServer = 'http://91.200.42.232/webservice/rest/server.php';
$token = '8be9314e213c01a9a42b46c6e90d5352';

$MoodleRest = new MoodleRest($urlServer, $token);

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

$note = $MoodleRest->request('core_notes_create_notes', $parametersRequest, MoodleRest::METHOD_POST);

print_r($note);