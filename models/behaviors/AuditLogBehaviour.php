<?php

namespace mozzler\base\models\behaviors;

use MongoDB\BSON\ObjectId;
use mozzler\base\components\Tools;
use mozzler\base\models\AuditLog;
use mozzler\base\models\Model;
use yii\db\BaseActiveRecord;
use yii\behaviors\AttributesBehavior;

/**
 * AuditLog Behaviour for logging all changes to an entity
 * and who made those changes
 */
class AuditLogBehaviour extends AttributesBehavior
{

    public $auditLogActionId = '';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();


        $this->auditLogActionId = new ObjectId(); // This should be fairly unique for this action
        $attributes = [];

        // We need to save the auditLog :
        // - after the Insert we know the modelId
        // - before the Update so we know the previous value
        // - Before the Delete so we know the modelId
        foreach ($this->owner->attributes() as $attribute) {
            $attributes[$attribute] = [
                BaseActiveRecord::EVENT_AFTER_INSERT => [$this, 'saveAuditLog'],
                BaseActiveRecord::EVENT_BEFORE_UPDATE => [$this, 'saveAuditLog'],
                BaseActiveRecord::EVENT_BEFORE_DELETE => [$this, 'saveAuditLog']
            ];
        }
        $this->attributes = $attributes;
    }

    /**
     */
    protected function saveAuditLog($event, $attribute)
    {
        /** @var Model $model */
        $model = $this->owner;

        $action = $event->name; // Init
        switch ($event->name) {
            case BaseActiveRecord::EVENT_AFTER_INSERT:
                $action = AuditLog::ACTION_INSERT;
                break;
            case BaseActiveRecord::EVENT_BEFORE_UPDATE:
                $action = AuditLog::ACTION_UPDATE;
                break;
            case BaseActiveRecord::EVENT_BEFORE_DELETE:
                $action = AuditLog::ACTION_DELETE;
                break;
        }

        $auditLogData = [
            'newValue' => $model->$attribute,
            'field' => $attribute,
            'entityId' => $model->getId(),
            'entityType' => get_class($model),
            'action' => $action,
            'actionId' => $this->auditLogActionId,
        ];


        if ($action === AuditLog::ACTION_UPDATE)
            // Locate the previous value for this attribute
            $previousModel = Tools::getModel($model::className(), ['_id' => Tools::ensureId($model->getId()), false]);
        if (!empty($previousModel)) {
            $auditLogData['oldValue'] = $previousModel->$attribute;
        }

        $auditLog = Tools::createModel(AuditLog::class, $auditLogData);
        $auditLog->save(true, null, false);

        return $this->owner->$attribute; // Return the original attribute
    }
}
