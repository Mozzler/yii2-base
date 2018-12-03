<?php
namespace mozzler\base\actions;

use yii\base\Action;

class BaseAction extends Action
{
	public $name = 'base';
	
    public function run()
    {   
	    return $this->controller->render($this->name);
    }
}