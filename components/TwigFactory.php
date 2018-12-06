<?php
namespace mozzler\base\components;

use yii\twig\Twig_Empty_Loader;

class TwigFactory {
	
	/**
	 * Get an initialised twig environment for loading twig from a string
	 *
	 * @todo Use a ViewRenderer so the same config that is in web.php can be used
	 */
	public static function getEnvironment() {
		$twig = new \Twig_Environment(new Twig_Empty_Loader(),["autoescape" => false]);
		$twig->getExtension('Twig_Extension_Core')->setTimezone(\Yii::$app->formatter->timeZone);
		
		$twig->addGlobal('html', '\yii\helpers\Html');
		$twig->addGlobal('arrayhelper', '\yii\helpers\ArrayHelper');
		$twig->addGlobal('t', '\mozzler\base\components\Tools');
		$twig->addExtension(new \Twig_Extension_StringLoader());
		
		return $twig;
	}
	
}

?>