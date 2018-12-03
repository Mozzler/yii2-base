<?php
namespace mozzler\base\controllers;

use mozzler\base\helpers\ControllerHelper;
use yii\helpers\ArrayHelper;

class ModelController extends WebController {
	
	public $modelClass;
	public static $moduleClass = 'mozzler\base\Module';
	
	public function init() {
		parent::init();
		
		$this->data['model'] = $this->getModel();
	}
	
	public function actions() {
		return ArrayHelper::merge(parent::actions(), [
			'create' => [
	            'class' => 'mozzler\base\actions\ModelCreateAction'
	        ]
	    ]);
	}
	
	public function actionIndex() {
		return $this->actionList();
	}
	
	public function actionUpdate() {
		return "update";
	}
	
	public function actionDelete() {
		return "delete";
	}
	
	public function actionView() {
		return $this->render('view');
	}
	
	public function actionList() {		
		return $this->render('list');
	}
	
	public function getModel() {
		return \Yii::createObject($this->modelClass);
	}
	
	/**
	 * Search all the parents controllers to try and find a view template file that follows
	 * the controller inheritance structure.
	 */
    public function getFullViewPath($view)
    {
	    $viewPaths = ControllerHelper::getModelViewPaths($this);

	    foreach ($viewPaths as $path) {
		    $path = \Yii::getAlias($path);
		    \Yii::trace($path, __METHOD__);
		    $viewPath = $path . DIRECTORY_SEPARATOR . $view . '.' . $this->view->defaultExtension;
		    
		    if (file_exists($viewPath)) {
	            return $viewPath;
	        }
	    }
	    
	    return false;
    }
	
}