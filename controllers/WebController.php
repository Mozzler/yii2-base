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

	/**
	 * Raw data (returned as Json or sent to HTML template)
	 */
	public $data = [];

	/**
	 * Data for the HTML template (merged with `$data`)
	 */
	public $templateData = [];

	/**
	 * Has the client requested a Json response?
	 */
	public $jsonRequested = false;

	/**
	 * Before running any action, check if we need to be returning Json
	 */
	public function init()
	{
	    parent::init(); // Required to be called as of Yii2 v2.0.36 https://github.com/yiisoft/yii2/pull/18083#issuecomment-646020002
		$this->on(self::EVENT_BEFORE_ACTION, [$this, 'checkJsonRequested']);
	}

	/**
	 * Check if we need to return a Json response
	 */
	public function checkJsonRequested() {
		$contentTypes = \Yii::$app->request->getAcceptableContentTypes();
        if (isset($contentTypes['application/json'])) {
            $this->jsonRequested = true;
        }
	}

	/**
	 * Support defining $this->data for establishing what data should be sent to view templates
	 *
	 * Merge with $data sent as the second parameter
	 */
	public function render($template, $data=[]) {
        $data = ArrayHelper::merge($this->templateData, $this->data,  $data);
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
