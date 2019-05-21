<?php
namespace mozzler\base\controllers\traits;

/**
 * Trait to enable Cors and ensure authenticator and rbacFilter
 * behaviours are added back in the correct order so they
 * work correctly.
 */
trait CorsTrait {

    public function behaviors()
	{
		$behaviors = parent::behaviors();

        // remove authentication filter
        $auth = null;
        if (isset($behaviors['authenticator'])) {
            $auth = $behaviors['authenticator'];
            unset($behaviors['authenticator']);
        }

        $rbacFilter = null;
        if (isset($behaviors['rbacFilter'])) {
            $rbacFilter = $behaviors['rbacFilter'];
            unset($behaviors['rbacFilter']);
        }
		
		// add CORS filter
		$behaviors['corsFilter'] = [
			'class' => \yii\filters\Cors::className(),
		];
		
        // re-add authentication filter
        if ($auth) {
            $behaviors['authenticator'] = $auth;
            // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
		    $behaviors['authenticator']['except'] = ['options'];
        }
        
        // re-add rbac filter filter
		if ($rbacFilter) {
            $behaviors['rbacFilter'] = $rbacFilter;
        }
	
		return $behaviors;
	}

}