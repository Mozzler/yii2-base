<?php
namespace mozzler\base\components;

use yii\base\Component;
use yii\helpers\ArrayHelper;

class Tools extends Component {
	
	public static function app() {
		return \Yii::$app;
	}
	
	public static function load($className, $config=[]) {
		$className = '\\'.preg_replace("/\./", "\\\\", $className);
		\Yii::trace("Loading ".$className);
		
		return \Yii::createObject($className, $config);
	}
	
	public static function renderWidget($widgetName, $widgetConfig=[], $constructorConfig=[]) {
		$config = ArrayHelper::merge($constructorConfig, [
			'config' => $widgetConfig
		]);
		
		$widget = self::load($widgetName, $config);
		return $widget::widget($config);
	}	
}