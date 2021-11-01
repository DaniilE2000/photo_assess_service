<?php

namespace app\queue;

use app\models\Results;
use app\models\Tasks;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class TaskJob extends BaseObject implements JobInterface
{
    public int $task_id;
    public array $data;

    public function execute($queue)
    {
        $ch = curl_init('http://merlinface.com:12345/api/');
        curl_setopt($ch, CURLOPT_POST, 1);
        switch ($this->data['status']) {
            case 'init':
                $file = curl_file_create($this->data['filepath'], 'image/' . pathinfo($this->data['filepath'], PATHINFO_EXTENSION));
                curl_setopt($ch, CURLOPT_POSTFIELDS, ['name' => $this->data['name'], 'photo' => $file]);
                break;
            case 'retry':
                curl_setopt($ch, CURLOPT_POSTFIELDS, ['retry_id' => $this->data['retry_id']]);
                break;
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        

        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if ($result['status'] === 'success') {
            $task = Tasks::findOne($this->task_id);
            $task->updateAttributes(['status' => 'ready']);
            $resultRecord = new Results();
            $resultRecord->task_id = $this->task_id;
            $resultRecord->result = $result['result'];
            $resultRecord->save();
        } else {
            Yii::$app->queue->delay(5)->push(new TaskJob(['task_id' => $this->task_id, 'data' => ['status' => 'retry', 'retry_id' => $result['retry_id']]]));
        }
    }
}

?>
