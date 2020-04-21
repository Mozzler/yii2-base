<?php
namespace mozzler\base\widgets\ui;

use mozzler\base\widgets\BaseWidget;

class Columns extends BaseWidget {
	
	public function defaultConfig()
	{
		return [
			'tag' => 'div',
			'options' => [
				'class' => 'widget-ui-columns'
			],
			'items' => [],
			'rows' => [
				'tag' => 'div',
				'options' => [
					'class' => 'row'
				]
			],
			'columns' => [
				'count' => 2,
				'tag' => 'div',
				'options' => [],
				'classes' => [],
				'calculateClasses' => true,
				'classTemplate' => 'col-md-{{ columnWidth }}'
			],
			'direction' => 'ltr'
		];
	}
	
	// take $config and process it to generate final config
	public function code($templatify = false) {
		$config = $this->config();
		$t = new \mozzler\base\components\Tools;

		// direction can be "ltr" (left to right) or "ttb" (top to bottom)
		if ($config['direction'] == "ttb") {
		    $columnItems = [];
		    $column = -1;
		    $maxRows = ceil(sizeof($config['items']) / $config['columns']['count']);
		    
		    $i = 0;
		    foreach ($config['items'] as $item) {
			    if ($i % $maxRows === 0) {
                    $column++;
				}
				
				if (!isset($columnItems[$column])) {
					$columnItems[$column] = [];
				}
				
				$columnItems[$column][] = $item;
			}
		    
		    $items = [];
		    $row = 0;
		    
		    while ($row < $maxRows) {
			    foreach ($columnItems as $columnId => $item) {
				    $items[] = $columnItems[$columnId][$row];
				}
				
				$row++;
			}
		    
		    $config['items'] = $items;
		}

		if ($config['columns']['calculateClasses']) {
			$classes = [];
			$column = 1;
			
			while ($column <= $config['columns']['count']) {
				$data = [
					'column' => $column,
					'count' => $config['columns']['count'],
					'columnWidth' => ceil(12 / $config['columns']['count'])
				];

				$classes['col-'.$column] = $t->renderTwig($config['columns']['classTemplate'], $data);
				$column++;
			}
			
			$config['columns']['classes'] += $classes;
		}
				
		return $config;
	}
	
}

