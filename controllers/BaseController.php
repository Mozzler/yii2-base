<?php
namespace mozzler\base\controllers;

use yii\web\Controller;

class BaseController extends Controller {

	public static $moduleClass = 'mozzler\web\Module';
	public $data = [];
    
}