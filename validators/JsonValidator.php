<?php

namespace mozzler\base\validators;

use yii\helpers\VarDumper;
use yii\validators\Validator;

class JsonValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        if (!empty($model->__get($attribute))) {
            try {
                if (is_string($model->__get($attribute))) {
                    $decoded = \yii\helpers\Json::decode($model->__get($attribute));
                }
            } catch (\Throwable $exception) {
                // -- Example as per https://github.com/yiisoft/yii2/issues/11266
                $this->addError($model, $attribute, "Invalid JSON for $attribute: " . $exception->getMessage());
                \Yii::error("The attribute $attribute has invalid Json: " . VarDumper::export([
                        'JSON' => $model->__get($attribute),
                        'Exception' => \Yii::$app->t::returnExceptionAsString($exception),
                    ]));
            }
        }
    }

}
