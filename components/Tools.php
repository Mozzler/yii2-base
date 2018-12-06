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
	
	/**
	 * Render a twig template
	 *
	 * @param	string	$template	Twig template to render
	 * @param	array	$data		Data to pass to the template
	 * @param	array	$options	Any template rendering options
	 * @return	string	Returns the template result
	 */
	public static function renderTwig($template, $data=[], $options=[]) {
		$twig = TwigFactory::getEnvironment();
		$twigTemplate = $twig->createTemplate($template);
        $output = $twigTemplate->render($data);

		if (isset($options["recursive"]) && $options["recursive"] === true) {
			$count = 0;
			if (!isset($options["recursiveLimit"]) || !is_int($options["recursiveLimit"])) {
				$options["recursiveLimit"] = 10;
			}

			while (preg_match('/'.$options["recursive"].'/', $output)) {
				$twigTemplate = $twig->createTemplate($output);
                $output = $twigTemplate->render($data);

				$count++;
				
				if ($count > $options["recursiveLimit"]) {
					\Yii::warning("Recursive limit (".$options['recursiveLimit'].") hit in renderTemplate(). Check your template and recursive regex doesn't cause a never-ending loop, or increase the `recursiveLimit` option.");
					break;
				}
			}
		}
		
		return $output;
		
	}
	
	public static function getWidget($widget) {
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