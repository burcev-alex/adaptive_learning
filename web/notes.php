<?php
ini_set('default_charset', 'utf-8');

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

spl_autoload_register(function ($className) {
    include_once $_SERVER['DOCUMENT_ROOT'] . '/' . $className . '.php';
});

use Web\Repository;

$urlServer = 'http://91.200.42.232/webservice/rest/server.php';
$token = '1818c0cedf5f484f9c767b6b135a12c1';

$redis = new \Predis\Client(
    array(
        "scheme" => "tcp",
        "host" => '127.0.0.1',
        "port" => 6379,
        "read_write_timeout" => 0
    )
);

$userId = 4; // Александр Бурцев

libxml_use_internal_errors(true);
/* Createa a new DomDocument object */
$dom = new \DomDocument();

$MoodleRest = new MoodleRest($urlServer, $token);

function p($arr, $console = false)
{
    if (!$console) {
        echo '<pre>'.print_r($arr, true).'</pre>';
    } else {
        print_r($arr);
    }
}


$repository = new Repository\RedisStorage();
$arData = $repository->getAll('notes');

if (count($arData) > 0) {
    $parametersRequest = [];
    foreach ($arData as $key=>$item) {
        $message = "Вы дали не верный ответ на вопрос: <br/>".$item['questionText'] . "<br/>Ответ вы можете найти здесь: <a href='/mod/page/view.php?id=".$item['pageId']."'>конспект</a>";
        $parametersRequest['messages'][] = [
            "touserid" => $userId,
            "text" => $message,
            "textformat" => 1,
            "clientmsgid" => 1
        ];
    }
    p($parametersRequest);

    // отправить персональное уведомление
    $notes = $MoodleRest->request('core_message_send_instant_messages', $parametersRequest, MoodleRest::METHOD_POST);
        
    p($notes);
}