<?php
namespace mozzler\base\controllers\traits;

use yii\helpers\ArrayHelper;

/**
 * Trait to enable Cors and ensure authenticator and rbacFilter
 * behaviours are added back in the correct order so they
 * work correctly.
 */
trait CorsTrait {

    public function behaviors()
	{
        $behaviors = parent::behaviors();
        
        // add CORS filter at the start
        $behaviors = ArrayHelper::merge([
            'corsFilter' => [
                'class' => \yii\filters\Cors::className()
            ]
        ], $behaviors);
		
        // re-add authentication filter
        if (isset($behaviors['authenticator'])) {
            // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
		    $behaviors['authenticator']['except'] = ['options'];
        }
	
		return $behaviors;
	}

}