<?php

namespace mozzler\base\widgets\viewauditlog;

use mozzler\base\components\Tools;
use mozzler\base\models\AuditLog;
use mozzler\base\widgets\BaseWidget;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

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
        $auditLogs = Tools::getModels(AuditLog::class, ['entityId' => Tools::ensureId($model->getId()), 'entityType' => $model::className()], ['limit' => 100, 'orderBy' => ['createdAt' => -1]]);
        if (!empty($auditLogs)) {
//            \Yii::debug("The auditLog entries found are: " . print_r($auditLogs, true));

            $auditLogEntries = ArrayHelper::index(ArrayHelper::toArray($auditLogs), null, 'actionId');
            \Yii::debug("-- The auditLogEntries are: " . var_export($auditLogEntries, true));


            $config['auditLog'] = $auditLogEntries;
        } else {
            \Yii::warning("No auditLog entries found.");
        }
        return $config;
    }

}

?>