<?php

namespace app\models;

use yii\db\ActiveRecord;

/** @property int $task_id */
/** @property float $result */

class Results extends ActiveRecord
{
    public function rules()
    {
        return [
            [['task_id'], 'required'],
            [['result'], 'double'],
        ];
    }

    public function getTask()
    {
        return $this->hasOne(Tasks::class, ['id' => 'task_id']);
    }
}

?>
