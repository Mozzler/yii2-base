<?php
namespace mozzler\base\actions;

use yii\base\Action;

class BaseAction extends Action
{
	public $name = 'base';
	
	public $config = [];
	
	/**
     * @var callable a PHP callable that will be called when running an action to determine
     * if the current user has the permission to execute the action. If not set, the access
     * check will not be performed. The signature of the callable should be as follows,
     *
     * ```php
     * function ($action, $model = null) {
     *     // $model is the requested model instance.
     *     // If null, it means no specific model (e.g. IndexAction)
     * }
     * ```
     */
    public $checkAccess;
	
    public function run()
    {   
	    return $this->controller->render($this->name);
    }
    
    public function defaultConfig()
	{
		return [];
	}
	
	public function config() {
		return ArrayHelper::merge($this->defaultConfig(), $this->config);
	}
}