<?php
namespace mozzler\base;

class MozzlerModule extends \yii\base\Module
{
	public static $viewPath = '@mozzler/base/views';
	
    public function init()
    {
        parent::init();
        
        \Yii::configure($this, require __DIR__ . '/config.php');
    }
}