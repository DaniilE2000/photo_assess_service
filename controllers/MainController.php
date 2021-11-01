<?php

namespace app\controllers;

use app\components\FileUploader;
use app\models\Results;
use app\models\Tasks;
use app\models\UploadFile;
use app\models\UserInfo;
use app\queue\TaskJob;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\UploadedFile;

class MainController extends Controller
{
    public $enableCsrfValidation = false;

    const RESPONSE_STATUS_NOT_FOUND = 'not_found';
    const RESPONSE_STATUS_WAIT = 'wait';
    const RESPONSE_STATUS_READY = 'ready';

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    private function setCORSPositive()
    {
        $headers = Yii::$app->response->headers;
        $headers->add('Access-Control-Allow-Origin', '*');
        $headers->add('Access-Control-Allow-Headers', '*');
        $headers->add('Access-Control-Allow-Methods', '*');
        $headers->add('Access-Control-Allow-Credentials', 'true');
    }

    private function respond(string $responseStatus, ...$args)
    {
        $response = Yii::$app->response;
        switch($responseStatus) {
            case self::RESPONSE_STATUS_READY:
                $response->statusCode = 200; // OK.
                $response->content = json_encode([
                    'status' => $responseStatus,
                    'result' => $args[0],
                ]);
                return;
            case self::RESPONSE_STATUS_WAIT:
                $response->statusCode = 425; //Too Early
                break;
            case self::RESPONSE_STATUS_NOT_FOUND:
                $response->statusCode = 404; // Not Found
                break;
        }

        $response->content = json_encode([
            'status' => $responseStatus,
            'result' => null,
        ]);
    }

    private function notFound()
    {
        $response = Yii::$app->response;
        $response->statusCode = 404; // Not Found
        $response->content = json_encode([
            'status' => 'not_found',
            'result' => null,
        ]);
    }

    private function wait() 
    {
        $response = Yii::$app->response;
        $response->statusCode = 425; //Too Early
        $response->content = json_encode([
            'status' => 'wait',
            'result' => null,
        ]);
    }

    private function ready($result)
    {
        $response = Yii::$app->response;
        $response->statusCode = 425; //Too Early
        $response->content = json_encode([
            'status' => 'wait',
            'result' => null,
        ]);
    }

    private function processGet(int $task_id)
    {
        $task = Tasks::findOne($task_id);
        if (!$task) {
            return $this->respond(self::RESPONSE_STATUS_NOT_FOUND);
        }

        if ($task->status === 'wait') {
            return $this->respond(self::RESPONSE_STATUS_WAIT);
        }

        $result = Results::findOne($task->id)->result;
        return $this->respond(self::RESPONSE_STATUS_READY, $result);
    }

    private function findUserRecordId(string $name, string $filename)
    {
        $user_info = UserInfo::find()->select(['task_id'])->where('`user_name` = "' . $name . '" AND `file_name` = "' . $filename . '"')
            ->asArray()->one();
        return $user_info ? $user_info['task_id'] : null;
    }

    private function processPost(string $name)
    {
        $response = Yii::$app->response;

        $photoFile = $_FILES['photo'];
        if ($task_id = $this->findUserRecordId($name, $photoFile['name'])) {
            $response->statusCode = 208; // Already reported.
            $resultRecord = Results::findOne($task_id);
            $response->content = json_encode([
                'status' => 'received',
                'task' => $task_id,
                'result' => $resultRecord->result,
            ]);
            return;
        }

        $response->statusCode = 202; // Accepted.

        $uploadPath = realpath('../temp-img-folder');
        
        $fileUploader = new FileUploader($photoFile['tmp_name'], pathinfo($photoFile['name'], PATHINFO_EXTENSION), $uploadPath);

        $task = new Tasks();
        $task->save();

        $userInfo = new UserInfo();
        $userInfo->saveAs($task->id, $name, $photoFile['name']);

        Yii::$app->queue->push(new TaskJob([
            'task_id' => $task->id,
            'data' => [
                'status' => 'init',
                'name' => $name,
                'filepath' => $uploadPath . '/' . $fileUploader->getFilename(),
            ],
        ]));
        
        if ($fileUploader->isUploaded) {
            $response->content = json_encode([
                'status' => 'received',
                'task' => $task->id,
                'result' => null,
            ]);
        } else {
            $response->content = json_encode([
                'status' => false,
                'message' => 'Not Uploaded',
            ]);
        }
    }

    public function actionIndex() {
        Yii::$app->request->enableCsrfValidation = false;
        Yii::$app->request->enableCookieValidation = false;
        $request = Yii::$app->request;
        $this->setCORSPositive();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        switch($request->method) {
            case 'GET':
                $task_id = $request->get('task_id');
                return $this->processGet($task_id);
                break;
            case 'POST':
                $name = $request->post('name');
                return $this->processPost($name);
                break;
            default:
                return $request->method;
        }
        return $this->notFound();
    }
}

?>
