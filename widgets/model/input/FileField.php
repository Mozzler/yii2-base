<?php
namespace mozzler\base\widgets\model\input;

class FileField extends BaseField
{
    /**
     * @return string
     *
     * As per https://www.yiiframework.com/doc/guide/2.0/en/input-file-upload
     */
	public function run() {
		$config = $this->config();
		$field = $config['form']->field($config['model'], $config['attribute']);
		return $field->fileInput($config['widgetConfig']);
	}
	
}
