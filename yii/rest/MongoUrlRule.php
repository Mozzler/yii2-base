<?php
namespace mozzler\base\yii\rest;

use yii\rest\UrlRule;

class MongoUrlRule extends UrlRule
{
	
	public $tokens = ['{id}' => '<id:\\w[\\w,]*>'];
	
}