<?php
return [
    'components' => [
        // list of component configurations
    ],
    'params' => [
        // list of parameters
        'fieldTypes' => [
	        'MongoId' => 'mozzler\base\fields\MongoId',
	        'Text' => 'mozzler\base\fields\Text',
	        'Integer' => 'mozzler\base\fields\Integer',
	        'Timestamp' => 'mozzler\base\fields\Timestamp',
	        'Date' => 'mozzler\base\fields\Date',
	        'DateTime' => 'mozzler\base\fields\DateTime',
	        'Boolean' => 'mozzler\base\fields\Boolean'
	        'Email' => 'mozzler\base\fields\Email'
        ]
    ]
];