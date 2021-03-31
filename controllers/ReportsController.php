<?php

namespace mozzler\base\controllers;

use mozzler\base\controllers\ActiveController as MozzlerBaseController;

class ReportsController extends MozzlerBaseController
{

    public $modelClass = 'mozzler\base\models\model';

    public function actions()
    {
        return [
            'reportItem' => [
                'class' => 'mozzler\base\actions\ReportItemAction',
            ],
        ];
    }


    public static function rbac()
    {
        return [
            'public' => [
                'reportItem' => [
                    'grant' => false
                ],
            ],
            'registered' => [
                'reportItem' => [
                    'grant' => true
                ],
            ],
            'admin' => [
                'reportItem' => [
                    'grant' => true
                ],
            ]];
    }

}
