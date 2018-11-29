<?php
namespace mozzler\base;

use yii\base\Component;

class Mozzler extends Component {
	
	public $fieldTypes = [
		'MongoId' => 'mozzler\base\fields\MongoId',
        'Text' => 'mozzler\base\fields\Text',
        'Integer' => 'mozzler\base\fields\Integer',
        'Timestamp' => 'mozzler\base\fields\Timestamp',
        'Date' => 'mozzler\base\fields\Date',
        'DateTime' => 'mozzler\base\fields\DateTime',
        'Boolean' => 'mozzler\base\fields\Boolean',
        'Email' => 'mozzler\base\fields\Email',
        'RelateOne' => 'mozzler\base\fields\RelateOne'
	];
	
}