<?php
namespace mozzler\base\widgets\auditLog;

use mozzler\base\widgets\BaseWidget;

class ViewAuditLog extends BaseWidget {
	
	public function defaultConfig()
	{
		return [
			'tag' => 'div',
			'options' => [
				'class' => 'audit-log'
			],
		];
	}
	
	// take $config and process it to generate final config
	public function code() {
		$config = $this->config();

		// @todo Get the data
		return $config;
	}
	
}

?>