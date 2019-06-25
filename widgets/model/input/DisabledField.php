<?php
namespace mozzler\base\widgets\model\input;

class DisabledField extends BaseField
{

    public function defaultConfig()
    {
        // NB: Have to re-add the form-control class when overriding the inputOptions to add the disabled attribute.
        return [
            'model' => null,
            'attribute' => null,
            'fieldOptions' => ['inputOptions' => ['disabled' => 'disabled', 'class' => 'form-control']]
        ];
    }
}
