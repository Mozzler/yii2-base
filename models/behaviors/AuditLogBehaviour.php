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

    public $auditLogAttributes;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();


        $this->auditLogActionId = (string)new ObjectId(); // This should be fairly unique for this action
        $attributes = [];

        $auditLogAttributes = [];
        if (!empty($this->auditLogAttributes)) {
            $auditLogAttributes = $this->auditLogAttributes;
        } else if (!empty($this->owner) && method_exists($this->owner, 'attributes')) {
            $auditLogAttributes = $this->owner->attributes();
        }

        // We need to save the auditLog :
        // - after the Insert we know the modelId
        // - before the Update so we know the previous value
        // - Before the Delete so we know the modelId


        if (!empty($auditLogAttributes)) {
            foreach ($auditLogAttributes as $attribute) {
                $attributes[$attribute] = [
                    BaseActiveRecord::EVENT_AFTER_INSERT => [$this, 'saveAuditLog'],
                    BaseActiveRecord::EVENT_BEFORE_UPDATE => [$this, 'saveAuditLog'],
                    BaseActiveRecord::EVENT_BEFORE_DELETE => [$this, 'saveAuditLog']
                ];
            }
//            \Yii::debug("Setting the AuditLogBehaviour attributes to: " . print_r($attributes, true) . "\nBased on the auditLogAttributes: ". json_encode($auditLogAttributes));
            $this->attributes = $attributes;
        } else {
            \Yii::debug("The AuditLogBehaviour owner isn't set. Can't find the attributes");
        }
    }

    /**
     */
    protected function saveAuditLog($event, $attribute)
    {
        // @todo: Only process dirty (changed) attributes if it's on update
        // @todo: Don't process the createdAt nor updatedAt fields
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
            'newValue' => json_encode($model->$attribute),
            'field' => $attribute,
            'entityId' => Tools::ensureId($model->getId()),
            'entityType' => get_class($model),
            'action' => $action,
            'actionId' => $this->auditLogActionId,
        ];


        if (AuditLog::ACTION_UPDATE === $action) {

            // Locate the previous value for this attribute
            $previousModel = Tools::getModel($model::className(), ['_id' => Tools::ensureId($model->getId())], false);
            if (!empty($previousModel)) {
                $auditLogData['previousValue'] = json_encode($previousModel->$attribute);

                if ($auditLogData['previousValue'] === $auditLogData['newValue']) {
                    return $this->owner->$attribute; // The field hasn't changed, so return the original attribute and don't save this
                }
            }
        }

        \Yii::debug("AuditLogBehaviour->saveAuditLog() Setting the AuditLog to to: " . print_r($auditLogData, true));

        $auditLog = Tools::createModel(AuditLog::class, $auditLogData);
        $auditLogSaved = $auditLog->save(true, null, false);
        if (!$auditLogSaved) {
            \Yii::error("auditLog save error:\n" . print_r($auditLog->getErrors(), true));
        }

        return $this->owner->$attribute; // Return the original attribute
    }
}
