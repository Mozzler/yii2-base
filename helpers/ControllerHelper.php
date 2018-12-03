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
		
		\Yii::trace(print_r($viewPaths,true));
		
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
			if ($className == 'mozzler\base\models\Base') {
				break;
			}
		}
		
		\Yii::trace(print_r($viewPaths,true));
		
		return $viewPaths;
	}
	
	/**
	 * Given a controller class name, determine the controller name for views
	 *
	 * ie: mozzler\auth\UserControler has module=auth and controller=user
	 */
	public static function getControllerName($className) {
		preg_match('/mozzler\\\\(.*)\\\\controllers\\\\([^\\\\]*)Controller$/i', $className, $matches);
		
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
	
}	
	
