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

*/

$courseId = 2;
$parametersRequest = [
    "courseids" => [
        $courseId
    ]
];

// все страницы (конспект) определенных курстов
$dataCourseContents = $MoodleRest->request('mod_page_get_pages_by_courses', $parametersRequest, MoodleRest::METHOD_POST);


$parametersRequest = [
    "courseids" => [
        $courseId
    ]
];

// все тесты определенных курстов
$dataQuizzes = $MoodleRest->request('mod_quiz_get_quizzes_by_courses', $parametersRequest, MoodleRest::METHOD_POST);
if (count($dataQuizzes["quizzes"]) > 0) {
    foreach ($dataQuizzes["quizzes"] as $arQuiz) {
        $quizId = $arQuiz['id'];

        echo '<div style="font-size:22px;">Название теста: '.$arQuiz['name'].'</div>';

        // выборка всех попыток прохождения тестов
        $parametersRequest = [
            "quizid" => $quizId,
            "userid" => $userId
        ];
        
        echo '<div style="font-size:18px;">Завершенные попытки прохождения теста пользователем #'.$userId.'</div>';

        // Вернуть список попыток для данного теста и пользователя.
        $dataAttempts = $MoodleRest->request('mod_quiz_get_user_attempts', $parametersRequest, MoodleRest::METHOD_POST);
        
        echo '<table cellpadding="5" cellspacing="0" border="1">';
        foreach ($dataAttempts['attempts'] as $key => $value) {
            echo "<tr>";
            foreach ($value as $name=>$data) {
                echo "<th>".$name."</th>";
            }
            echo "</tr>";
            break;
        }
        foreach ($dataAttempts['attempts'] as $key => $value) {
            if ($value['state'] != 'finished') {
                continue;
            }

            echo "<tr>";
            foreach ($value as $name=>$data) {
                echo "<td>".$data."</td>";
            }
            echo "</tr>";

            // посмотреть детальную информацию по каждой попытке
            $parametersRequest = [
                "attemptid" => $value['id']
            ];
    
            // Вернуть список попыток для данного теста и пользователя.
            $dataAttemptReview = $MoodleRest->request('mod_quiz_get_attempt_review', $parametersRequest, MoodleRest::METHOD_POST);
            #p($dataAttemptReview);

            echo "<tr><td colspan='".count($value)."'>Вопросы на которые был дан не верный ответ:<br/>";
            $questionsIncorrectAnswers = [];
            foreach ($dataAttemptReview['questions'] as $arQuestion) {
                // собрать список вопросов, по которым был дан не верный ответ
                if ($arQuestion['status'] == 'Incorrect') {
                    // DomDocument давал не правильную кодировку
                    // сделано через костыль, решение не универсальное
                    $tmp = explode('class="qtext">', $arQuestion['html']);
                    $tmp2 =explode('<div class="ablock">', $tmp[1]);
                    $textQuestion = strip_tags($tmp2[0]);

                    $questionsIncorrectAnswers[] = $textQuestion;
                }
            }

            if (count($questionsIncorrectAnswers) > 0) {
                $repository = new Repository\RedisStorage();

                foreach ($questionsIncorrectAnswers as $k=>$text) {
                    foreach ($dataCourseContents['pages'] as $arPage) {
                        // сохраняем в Redis
                        // вопрос и текст, где нужно найти соотвествие
                        $repository->save('lsa', [
                            'pageId' => $arPage['id'],
                            'courseId' => $courseId,
                            'quizId' => $quizId,
                            'questionId' => $arQuestion['id'],
                            'questionText' => $text,
                            'attemptId' => $value['id'],
                            'pageText' => $arPage['content']
                        ]);
                    }
                }
                p($questionsIncorrectAnswers);
            } else {
                echo '-';
            }
            echo "</td></tr>";
        }
        echo "</table>";
    }
}

/*
$repository = new Repository\RedisStorage();
$arData = $repository->getAll('lsa');
p($arData);
*/