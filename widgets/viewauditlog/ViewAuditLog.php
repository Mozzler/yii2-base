<?php

namespace mozzler\base\widgets\viewauditlog;

use mozzler\base\components\Tools;
use mozzler\base\models\AuditLog;
use mozzler\base\widgets\BaseWidget;
use yii\base\Exception;

class ViewAuditLog extends BaseWidget
{

    /*
     * Example usage in the ViewModel.php
     *
      {% if widget.auditLogAttached %}
        {{ t.renderWidget("mozzler.base.widgets.viewauditlog.ViewAuditLog",{"model": widget.model }) }}
      {% endif %}
     */

    /**
     * @return array
     */
    public function defaultConfig()
    {
        return [
            'tag' => 'div',
            'options' => [
                'class' => 'widget-audit-log-entries'
            ],
            'model' => null,
            'auditLogEntries' => [],
        ];
    }

    // take $config and process it to generate final config
    public function code()
    {
        $config = $this->config();

        if (empty($config['model'])) {
            throw new Exception("There's no model provided to the auditLog. Try calling it using {{ t.renderWidget(\"mozzler.base.widgets.viewauditlog.ViewAuditLog\",{\"model\": widget.model }) }}");
        }
        $model = $config['model'];


        // -- Get the associated auditLog's
        $auditLog = Tools::getModels(AuditLog::class, ['entityId' => Tools::ensureId($model->getId()), 'entityType' => $model::className()]);
        if (!empty($auditLog)) {
            \Yii::debug("The auditLog entries found are: " . print_r($auditLog, true));
            $config['auditLog'] = $auditLog;
        } else {
            \Yii::warning("No auditLog entries found.");
        }
        return $config;
    }

}

?>