<?php
namespace mozzler\base\controllers;

use yii\web\Controller as Controller;
use yii\helpers\ArrayHelper;

use mozzler\base\helpers\ControllerHelper;

use mozzler\auth\yii\oauth\auth\HttpBearerAuth;
use mozzler\rbac\filters\CompositeAuth;
use mozzler\rbac\filters\RbacFilter;

class WebController extends Controller {

	public static $moduleClass = 'mozzler\base\Module';
	public $data = [];
	
	/**
	 * Support defining $this->data for establishing what data should be sent to view templates
	 *
	 * Merge with $data sent as the second parameter
	 */
	public function render($template, $data=[]) {
		$data = ArrayHelper::merge($this->data, $data);

		if (\Yii::$app->request->isAjax) {
			\Yii::$app->response->format = \Yii::$app->response::FORMAT_JSON;
			\Yii::trace($data);
			return $data;
		}
		return parent::render($template, $data);
	}
	
	/**
	 * Search all the parents controllers to try and find a view template file that follows
	 * the controller inheritance structure.
	 */
    public function getFullViewPath($view)
    {
	    $viewPaths = ControllerHelper::getViewPaths($this);

	    foreach ($viewPaths as $path) {
		    $path = \Yii::getAlias($path);
		    $viewPath = $path . DIRECTORY_SEPARATOR . $view . '.' . $this->view->defaultExtension;
		    
		    if (file_exists($viewPath)) {
	            return $viewPath;
	        }
	    }
	    
	    return false;
    }
    
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
	    /**
		 * Enable RBAC permission checks on controller actions
		 */
        return ArrayHelper::merge(parent::behaviors(), [
            'rbacFilter' => [
                'class' => RbacFilter::className()
            ]
        ]);
    }
}