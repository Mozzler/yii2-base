<?php
namespace mozzler\base\scripts;

abstract class ScriptBase extends \yii\base\Component {

    // This method must be implemented by all scripts
    public abstract function run($task);

}