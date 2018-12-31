<?php
namespace mozzler\base\actions;

use yii\base\Action;
use yii\helpers\ArrayHelper;

class BaseAction extends Action
{
	public $id = 'base';
	
	public $config = [];
	
    public function run()
    {   
	    return $this->controller->render($this->id);
    }
    
    public function defaultConfig()
	{
		return [];
	}
	
	public function config() {
		return ArrayHelper::merge($this->defaultConfig(), $this->config);
	}
}