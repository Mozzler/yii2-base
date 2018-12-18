<?php
namespace mozzler\base\models\oauth;

use yii\helpers\ArrayHelper;

class OAuthClientModel extends \mozzler\base\models\Model
{
	
	protected static $collectionName = 'oauth_clients';
	
	protected function modelConfig() {
		return [
			'label' => 'OAuth Client',
			'labelPlural' => 'OAuth Clients'
		];
	}
	
	protected function modelFields()
	{
		return ArrayHelper::merge(parent::modelFields(), [
			'client_id' => [
				'type' => 'Text',
				'label' => 'Client ID'
			],
			'client_secret' => [
				'type' => 'Text',
				'label' => 'Secret'
			]
		]);
	}
	
	public function scenarios()
    {
        return [
            self::SCENARIO_CREATE => ['client_id', 'client_secret'],
            self::SCENARIO_UPDATE => ['client_id', 'client_secret'],
            self::SCENARIO_LIST => ['client_id', 'client_secret', 'createdUserId', 'createdAt'],
            self::SCENARIO_VIEW => ['client_id', 'client_secret', 'createdUserId', 'createdAt'],
            self::SCENARIO_SEARCH => ['client_id'],
            self::SCENARIO_EXPORT => ['_id', 'client_id', 'client_secret', 'createdAt', 'createdUserId', 'updatedAt', 'updatedUserId'],
            self::SCENARIO_DEFAULT => array_keys($this->modelFields())
        ];
    }
	
}