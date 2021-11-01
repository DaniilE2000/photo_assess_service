<?php

namespace app\models;

use yii\db\ActiveRecord;

/** @property int $task_id */
/** @property string $user_name */
/** @property string $file_name */

class UserInfo extends ActiveRecord
{
    public static function tableName()
    {
        return 'user_info';
    }

    public function rules()
    {
        return [
            [['task_id', 'user_name', 'file_name'], 'required'],
            [['user_name', 'file_name'], 'string', 'length' => [1, 100]],
        ];
    }

    public function getTask()
    {
        return $this->hasOne(Tasks::class, ['id' => 'task_id']);
    }

    public function saveAs(int $id, string $userName, string $filename)
    {
        $this->task_id = $id;
        $this->user_name = $userName;
        $this->file_name = $filename;
        $this->save();
    }
}

?>
