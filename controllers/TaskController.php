<?php
namespace mozzler\base\controllers;

use mozzler\base\controllers\ModelController as BaseController;
use yii\helpers\ArrayHelper;

class TaskController extends BaseController
{
	
	public $modelClass = 'mozzler\base\models\Task';

	public static function rbac() {
        return ArrayHelper::merge(parent::rbac(), [
            'registered' => [
				'index' => [
					'grant' => false
				],
				'view' => [
					'grant' => false
				],
                'create' => [
                    'grant' => false
                ],
                'update' => [
                    'grant' => false
                ],
                'delete' => [
                    'grant' => false
                ]
            ],
            'admin' => [
                'create' => [
                    'grant' => false
                ],
                'update' => [
                    'grant' => false
                ],
                'delete' => [
                    'grant' => false
                ]
            ]
        ]);
    }

}
