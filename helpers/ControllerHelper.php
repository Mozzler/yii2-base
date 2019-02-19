<?php
namespace mozzler\base\helpers;

class ControllerHelper {
	
	/**
	 * Given a controller, build an array of all the potential parent view paths
	 * based on the controller inheritance heirarchy
	 */
	public static function getViewPaths($controller) {
		$parentClasses = class_parents($controller);
		$controllerName = self::getControllerName($controller::className());
		
		// 1. Try the controller->module view path; ie: @basePath/views/controller/action.twig
		$viewPaths[] = $controller->module->getViewpath() . DIRECTORY_SEPARATOR . $controllerName;
		
		// 2. Try the module view path based on class name; ie: @mozzler/web/views/controller/action.twig
		$className = $controller::className();
		$moduleClass = $className::$moduleClass;
		
		$viewPaths[] = $moduleClass::$viewPath . DIRECTORY_SEPARATOR . $controllerName;

		foreach ($parentClasses as $className) {
			// If we have a parent of yii\web\Controller we have gone too far as there
			// will be no mozzler parent
			if ($className == 'yii\web\Controller') {
				break;
			}
			
			$controllerName = self::getControllerName($className);
			$moduleClass = $className::$moduleClass;
			$viewPaths[] = $moduleClass::$viewPath . DIRECTORY_SEPARATOR . $controllerName;
		}
		
		return $viewPaths;
	}
	
	/**
	 * Given a controller, build an array of all the potential parent view paths
	 * based on the controller inheritance heirarchy
	 */
	public static function getModelViewPaths($controller) {
		$model = \Yii::createObject($controller->modelClass);
		
		$parentClasses = class_parents($model);
		$modelName = self::getModelName($model::className());
		
		// 1. Try the app view path; ie: @basePath/views/model/action.twig
		$viewPaths[] = \Yii::$app->getViewpath() . DIRECTORY_SEPARATOR . $modelName;
		
		// 2. Try the module view path based on class name; ie: @mozzler/web/views/model/action.twig
		$moduleClass = $model::$moduleClass;
		$viewPaths[] = $moduleClass::$viewPath . DIRECTORY_SEPARATOR . $modelName;
		
		// 3. Try the module view path based on controller class name; ie: @mozzler/web/views/model/action.twig
		$moduleClass = $controller::$moduleClass;
		$viewPaths[] = $moduleClass::$viewPath . DIRECTORY_SEPARATOR . $modelName;

		foreach ($parentClasses as $className) {
			$model = \Yii::createObject($className);
			$modelName = self::getModelName($className);
			$moduleClass = $model::$moduleClass;
			
			$viewPaths[] = \Yii::$app->getViewpath() . DIRECTORY_SEPARATOR . $modelName;
			
			$viewPaths[] = $moduleClass::$viewPath . DIRECTORY_SEPARATOR . $modelName;
			
			// If we have processed mozzler\web\models we are at the top of the mozzler
			// heirarchy, so no need to continue
			if ($className == 'mozzler\base\models\Model') {
				break;
			}
		}
		
		return $viewPaths;
	}
	
	/**
	 * Given a controller class name, determine the controller name for views
	 *
	 * ie: mozzler\auth\UserControler has module=auth and controller=user
	 */
	public static function getControllerName($className) {
		preg_match('/^(.*)\\\\controllers\\\\([^\\\\]*)Controller$/i', $className, $matches);

		if (sizeof($matches) == 3) {
			return strtolower($matches[2]);
		}

		throw new \Exception("Unable to determine metadata Controller ({$className})");
	}
	
	/**
	 * Given a model class name, determine the model name for views
	 *
	 * ie: mozzler\auth\models\User has model=user
	 */
	public static function getModelName($className) {
		$parts = preg_split('/\\\\/', $className);
		return strtolower($parts[sizeof($parts)-1]);
	}
	
	/**
	 * Build a `yii\web\Controller` object from supplied parameters.
	 * 
	 * Example 1: Build the `User` controller in the `apiv1 module by specifying all the details:
	 * 
	 * `$controller = buildController('apiv1\controllers\UserController', 'apiv1', 'user');`
	 * 
	 * Example 2: Build the User controller by just specifying the controller class name. This assumes
	 * the controller is in the `app` module and the controller `id` is lower case version
	 * of the name (`user1).
	 * 
	 * `$controller = buildController('app\controllers\UserController');`
	 * 
	 * @param	$controllerClass	string	Full path of the controller class
	 * @param	$module				string	Module the controller belongs to (ie: `app` or `apiv1`)
	 * @param	$id					string	Id of the controller, will automatically build from the controller class if not supplied
	 */
	public static function buildController($controllerClass, $module='app', $id=null)
	{
		if (!$id) {
			$id = self::getControllerName($controllerClass);
		}

		return \Yii::createObject(['class' => $controllerClass], [$id, $module]);
	}
}	
	
