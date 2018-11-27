<?php
namespace mozzler\base\models;

use \yii\mongodb\ActiveRecord;

class Base extends ActiveRecord {
	
	public $label;
	public $labelPlural;
	public $fields = [];

	public static $moduleClass = '\mozzler\base\Module';	
	protected static $collectionName;
	
	public function getFields() {
		return [];
	}
	
}