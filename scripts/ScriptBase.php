<?php
namespace mozzler\base\scripts;


abstract class ScriptBase extends \yii\base\Component {

    // This method must be implemented by all scripts
    /**
     * @param $task \mozzler\base\models\Task
     * @return mixed
     */
    public abstract function run($task);

}