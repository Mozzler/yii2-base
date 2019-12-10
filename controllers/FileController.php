<?php

namespace mozzler\base\controllers;

use mozzler\base\controllers\ModelController as BaseController;
use yii\helpers\ArrayHelper;


/**
 * Class FileController
 * @package mozzler\base\controllers
 *
 * You'll need to ensure the FileController is included in the app config to get file uploads working
 *
 * e.g config/web.php
 *
 *     'controllerMap' => [
 * 'file' => [
 * 'class' => 'mozzler\base\controllers\FileController'
 * ],
 * ],
 */
class FileController extends BaseController
{

    public $modelClass = 'mozzler\base\models\File';

    public static function rbac()
    {
        return ArrayHelper::merge(parent::rbac(), [
            'registered' => [
                'index' => [
                    'grant' => true
                ],
                'view' => [
                    'grant' => true
                ],
                'create' => [
                    'grant' => true
                ],
                'update' => [
                    'grant' => true
                ],
                'delete' => [
                    'grant' => true
                ]
            ],
            'admin' => [
                'create' => [
                    'grant' => true
                ],
                'update' => [
                    'grant' => true
                ],
                'delete' => [
                    'grant' => true
                ]
            ]
        ]);
    }

}
