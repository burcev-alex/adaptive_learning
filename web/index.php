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
$courseId = 11; // ID курса

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
            $questionsIncorrectAnswersId = [];
            foreach ($dataAttemptReview['questions'] as $arQuestion) {
                // собрать список вопросов, по которым был дан не верный ответ
                if ($arQuestion['status'] == 'Incorrect') {
                    // DomDocument давал не правильную кодировку
                    // сделано через костыль, решение не универсальное
                    $tmp = explode('class="qtext">', $arQuestion['html']);
                    $tmp2 =explode('<div class="ablock">', $tmp[1]);
                    $textQuestion = strip_tags(str_replace("&nbsp;", " ", htmlspecialchars_decode($tmp2[0])));

                    $questionsIncorrectAnswers[] = $textQuestion;
                    $questionsIncorrectAnswersId[] = $arQuestion['number'];
                }
            }

            if (count($questionsIncorrectAnswers) > 0) {
                $repository = new Repository\RedisStorage();

                foreach ($questionsIncorrectAnswers as $k=>$text) {
                    foreach ($dataCourseContents['pages'] as $arPage) {
                        // сохраняем в Redis
                        // вопрос и текст, где нужно найти соотвествие
                        $repository->save('lsa', [
                            'pageId' => $arPage['coursemodule'],
                            'courseId' => $courseId,
                            'quizId' => $quizId,
                            'questionId' => $questionsIncorrectAnswersId[$k],
                            'questionText' => $text,
                            'attemptId' => $value['id'],
                            'pageText' => strip_tags(str_replace("&nbsp;", " ", htmlspecialchars_decode($arPage['content'])))
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