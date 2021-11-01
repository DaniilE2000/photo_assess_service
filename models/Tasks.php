<?php

namespace app\models;

use yii\db\ActiveRecord;

/** @property int $id */
/** @property string $status */

class Tasks extends ActiveRecord
{
    public function rules()
    {
        return [
            [['id'], 'safe'],
            [['status'], 'in', 'range' => ['wait', 'ready']],
            [['status'], 'default', 'value' => 'wait'],
        ];
    }
}

?>
