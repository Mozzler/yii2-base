<?php

namespace mozzler\base\widgets\model;

use mozzler\base\models\AuditLog;
use mozzler\base\models\behaviors\AuditLogBehaviour;
use mozzler\base\models\Model;
use mozzler\base\widgets\BaseWidget;

class ViewModel extends BaseWidget
{

    public function defaultConfig()
    {
        return [
            'tag' => 'div',
            'options' => [
                'class' => 'row widget-model-view'
            ],
            'container' => [
                'tag' => 'div',
                'options' => [
                    'class' => 'col-md-12'
                ]
            ],
            'model' => null,
            'panelConfig' => [
                'heading' => [
                    'title' => [
                        'content' =>
                            '{% if t.app.rbac.canAccessModel(widget.model, "update") %}
							<div class="pull-right">
								<a href="{{ widget.model.getUrl("update") }}" class="btn btn-success btn-sm">Edit {{ widget.model.getModelConfig(\'label\') }}</a>
							</div>
						{% endif %}
						{{ widget.model.getModelConfig("label") }}'
                    ]
                ],
                'body' => [],
                'footer' => false
            ]
        ];
    }

    // take $config and process it to generate final config
    public function code($templatify = false)
    {
        $config = $this->config(false);
        $model = $config['model'];
        $t = new \mozzler\base\components\Tools;

        $attributes = $model->activeAttributes();

        $items = [];

        foreach ($attributes as $attribute) {
            $modelField = $model->getModelField($attribute);
            if (!$modelField) {
                \Yii::warning("Non-existent attribute ($attribute) specified in scenario " . $model->scenario . " on " . $model->className());
                continue;
            }

            if (in_array($modelField->type, ['RelateMany', 'RelateManyMany'])) {
                // Don't render relate many fields in the view
                continue;
            }

            $fieldConfig = [
                'model' => $model,
                'attribute' => $attribute
            ];

            $items[] = $t->renderWidget('mozzler.base.widgets.model.view.RenderField', $fieldConfig);
        }

        $config['items'] = $items;

        /** @var $model Model */
        $behaviours = $model->behaviors();
        foreach ($behaviours as $behaviour) {
            if (!empty($behaviour) && !empty($behaviour['class']) && AuditLogBehaviour::class === $behaviour['class']) {
                // -- The auditLog behaviour is attached, so get an auditLog entry if there's one attached
//                \Yii::debug("The auditLog behaviour is attached: " . json_encode($behaviour, JSON_PRETTY_PRINT));
                if (empty($model)) {
                    \Yii::warning("The model isn't defined. Can't attach the auditLog"); // This shouldn't happen, but just in case
                    break;
                }
                // -- Check if there's any auditLogs for this model and that you are allowed to view them
                $auditLog = $t::getModel(AuditLog::class, ['entityId' => $t::ensureId($model->getId()), 'entityType' => $model::className()]);
                if (!empty($auditLog) && \Yii::$app->rbac->canAccessModel($auditLog, 'find')) {
                    $config['auditLogAttached'] = true;
                }
                break; // Stop searching
            }
        }

        $this->config = $config;

        return parent::code(true);
    }

}

?>