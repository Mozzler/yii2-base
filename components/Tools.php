<?php
namespace mozzler\base\components;

use yii\base\Component;
use yii\helpers\ArrayHelper;

class Tools extends Component {
	
	public static function app() {
		return \Yii::$app;
	}
	
	public static function load($className, $config=[]) {
		$className = self::getClassName($className);
		\Yii::trace("Loading widget".$className);
		
		return \Yii::createObject($className, $config);
	}
	
	public static function renderWidget($widgetName, $config=[], $wrapConfig=true) {
		if ($wrapConfig) {
			$config = ['config' => $config];
		}
		
		$widget = self::getWidget($widgetName);
		$output = $widget::widget($config);
		return $output;
	}
	
	public static function getWidget($widget) {
		\Yii::trace('getWidget('.$widget.')');
		$className = self::getClassName($widget);
		ob_start();
        ob_implicit_flush(false);
		$widget = new $className;
		ob_get_clean();
		return $widget;
	}
	
	public static function getClassName($className) {
		return '\\'.preg_replace("/\./", "\\\\", $className);
	}
}