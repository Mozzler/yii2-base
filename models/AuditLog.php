<?php

namespace mozzler\base\models;

use mozzler\base\models\Model as BaseModel;
use yii\helpers\ArrayHelper;

/**
 * Class AuditLog
 * @package mozzler\base\models
 *
 * This is to support the AuditLogBehaviour and allow
 *
 */
class AuditLog extends BaseModel
{


    const ACTION_INSERT = 'insert';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';

    protected static $collectionName = 'app.auditLog'; // This should be the same as what's used in \mozzler\base\components\cache

    protected function modelConfig()
    {
        return [
            'label' => 'AuditLog',
            'labelPlural' => 'AuditLogs'
        ];
    }

    protected function modelFields()
    {
        return ArrayHelper::merge(parent::modelFields(), [
            'newValue' => [
                'type' => 'TextLarge',
                'label' => 'New Value',
                'required' => true,
            ],
            'previousValue' => [
                'type' => 'TextLarge',
                'label' => 'Previous Value',
                'required' => false,
            ],
            'field' => [
                'type' => 'Text',
                'label' => 'Field',
                'required' => true,
            ],
            'entityId' => [
                'type' => 'Text',
                'label' => 'Entity ID',
                'required' => true,
            ],
            'entityType' => [
                'type' => 'Text',
                'label' => 'Entity Type',
                'required' => true,
            ],
            // A random ID number for the action, to group of fields together
            'actionId' => [
                'type' => 'Text',
                'label' => 'Action ID',
                'required' => false,
            ],
            'action' => [
                'type' => 'SingleSelect',
                'label' => 'Action',
                'options' => [
                    self::ACTION_INSERT => 'Insert',
                    self::ACTION_UPDATE => 'Update',
                    self::ACTION_DELETE => 'Delete',
                ]
            ],
        ]);
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['id', 'newValue', 'previousValue', 'field', 'entityId', 'entityType', 'action', 'actionId'];
        $scenarios[self::SCENARIO_UPDATE] = $scenarios[self::SCENARIO_CREATE];
        $scenarios[self::SCENARIO_LIST] = ['action', 'entityType', 'entityId', 'field', 'newValue', 'createdUserId'];
        $scenarios[self::SCENARIO_VIEW] = ['id', 'newValue', 'previousValue', 'field', 'entityId', 'entityType', 'action', 'actionId', 'createdUserId', 'createdAt', 'updatedUserId', 'updatedAt'];
        return $scenarios;
    }

}
