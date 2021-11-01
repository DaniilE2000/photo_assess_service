<?php

namespace app\commands;

use yii\console\Controller;
use yii\httpclient\Client;

class CommandsController extends Controller
{
    public function actionPostTest()
    {
        $file = curl_file_create('./files/1.jpg');
        $ch = curl_init('http://localhost:8080/');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['name' => 'Kesha', 'photo' => $file]);

        $result = curl_exec($ch);
        curl_close($ch);
    }

    public function actionGetTest()
    {
        $ch = curl_init('http://localhost:8080/?task_id=30');
        curl_exec($ch);
        curl_close($ch);
    }
}

?>