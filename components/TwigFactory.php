<?php
namespace mozzler\base\components;

use yii\twig\TwigEmptyLoader;
use yii\twig\ViewRendererStaticClassProxy;

class TwigFactory {

    /**
     * Get an initialised twig environment for loading twig from a string
     *
     * @todo Use a ViewRenderer so the same config that is in web.php can be used
     */
    public static function getEnvironment() {
        $twig = new \Twig\Environment(new TwigEmptyLoader(), ["autoescape" => false]);
        // -- These methods no longer exist in newer Twig versions
        // $twig->getExtension('Twig\Extension\CoreExtension')->setTimezone(\Yii::$app->formatter->timeZone);
        // $twig->getExtension('Twig\Extension\CoreExtension')->setDateFormat(\Yii::$app->formatter->dateFormat, '%d days');

        $twig = self::addGlobals($twig, [
            'html' => '\yii\helpers\Html',
            'arrayhelper' => '\yii\helpers\ArrayHelper',
            't' => '\mozzler\base\components\Tools'
        ]);

        $twig->addExtension(new \Twig\Extension\StringLoaderExtension());
        if (YII_ENV === YII_ENV_DEV) {
            $twig->addExtension(new \Twig\Extension\DebugExtension());
        }

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
