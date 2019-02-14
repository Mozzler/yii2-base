<?php
namespace mozzler\base\controllers;

use mozzler\base\helpers\ControllerHelper;
use yii\helpers\ArrayHelper;

class ModelController extends WebController {
	
	public $modelClass;
	public static $moduleClass = 'mozzler\base\Module';
	
	public function actions() {
		return ArrayHelper::merge(parent::actions(), [
			'create' => [
	            'class' => 'mozzler\base\actions\ModelCreateAction'
	        ],
	        'view' => [
	            'class' => 'mozzler\base\actions\ModelViewAction'
	        ],
	        'update' => [
	            'class' => 'mozzler\base\actions\ModelUpdateAction'
	        ],
	        'index' => [
	            'class' => 'mozzler\base\actions\ModelIndexAction'
	        ],
	        'delete' => [
	            'class' => 'mozzler\base\actions\ModelDeleteAction'
	        ]
	    ]);
	}
	
	/**
	 * Deny access to public users, which ensures
	 */
	public static function rbac() {
		return [
			'public' => [
				'create' => [
		            'grant' => false
		        ],
		        'view' => [
		            'grant' => false
		        ],
		        'update' => [
		            'grant' => false
		        ],
		        'index' => [
		            'grant' => false
		        ],
		        'delete' => [
		            'grant' => false
		        ]
	        ],
	        'registered' => [
				'create' => [
		            'grant' => true
		        ],
		        'view' => [
		            'grant' => true
		        ],
		        'update' => [
		            'grant' => true
		        ],
		        'index' => [
		            'grant' => true
		        ],
		        'delete' => [
		            'grant' => true
		        ]
	        ]
	    ];
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
		    $viewPath = $path . DIRECTORY_SEPARATOR . $view . '.' . $this->view->defaultExtension;
		    
		    if (file_exists($viewPath)) {
	            return $viewPath;
	        }
	    }
	    
	    return false;
    }
	
}