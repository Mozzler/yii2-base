<?php
namespace mozzler\base\components;

use yii\twig\Twig_Empty_Loader;
use yii\twig\ViewRendererStaticClassProxy;

class TwigFactory {
	
	/**
	 * Get an initialised twig environment for loading twig from a string
	 *
	 * @todo Use a ViewRenderer so the same config that is in web.php can be used
	 */
	public static function getEnvironment() {
		$twig = new \Twig_Environment(new Twig_Empty_Loader(),["autoescape" => false]);
		$twig->getExtension('Twig_Extension_Core')->setTimezone(\Yii::$app->formatter->timeZone);
		$twig->getExtension('Twig_Extension_Core')->setDateFormat(\Yii::$app->formatter->dateFormat, '%d days');
		
		$twig = self::addGlobals($twig, [
			'html' => '\yii\helpers\Html',
			'arrayhelper' => '\yii\helpers\ArrayHelper',
			't' => '\mozzler\base\components\Tools'
		]);

		$twig->addExtension(new \Twig_Extension_StringLoader());
		
		return $twig;
	}
	
	protected static function addGlobals($twig, $globals) {
		foreach ($globals as $name => $className) {
            $value = new ViewRendererStaticClassProxy($className);
            $twig->addGlobal($name, $value);
        }
        
        return $twig;
	}
	
}

?>