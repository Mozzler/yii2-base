<?php

namespace mozzler\base\validators;

use yii\helpers\VarDumper;
use yii\validators\Validator;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;
use Exception;

/**
 * Phone Validator
 *
 * This is originally from https://www.yiiframework.com/extension/yii2-phone-validator
 * However there was issues with the translation and default messaging
 * This is the Mozzler modified version
 *
 * Check https://github.com/giggsey/libphonenumber-for-php for more info on the main library
 * and the Country Code is the ISO 3166 2 char country code. e.g for Australia it is 'AU' https://en.wikipedia.org/wiki/List_of_ISO_3166_country_codes
 *
 * Example usage in a model's modelFields()
 * 'mobileNumber' => [
 * 'type' => 'Text',
 * 'label' => 'Mobile Number',
 * 'required' => true,
 * 'hint' => 'e.g 0412123123',
 * 'rules' => [
 * 'app\validators\PhoneValidator' => ['country' => 'AU']
 * ]
 * ],
 *   -----
 *
 * Phone validator class that validates phone numbers for given
 * country and formats.
 * Country codes and attributes value should be ISO 3166-1 alpha-2 codes
 * @property string $countryAttribute The country code attribute of model
 * @property string $country The country is fixed
 * @property bool $strict If country is not set or selected adds error
 * @property bool $format If phone number is valid formats value with
 *          libphonenumber/PhoneNumberFormat const (default to INTERNATIONAL)
 */
class PhoneValidator extends Validator
{

    public $strict = true;
    public $countryAttribute;
    public $country = 'AU'; // Default
    public $format = true;

    public function validateAttribute($model, $attribute)
    {
//        \Yii::debug("validateAttribute() on the phone number {$model->$attribute} with: " . VarDumper::export(['$strict' => $this->strict, '$countryAttribute' => $this->countryAttribute, '$country' => $this->country, '$format' => $this->format]));
        if ($this->format === true) {
            $this->format = PhoneNumberFormat::INTERNATIONAL;
        }
        // if countryAttribute is set
        if (!isset($country) && isset($this->countryAttribute)) {
            $countryAttribute = $this->countryAttribute;
            $country = $model->$countryAttribute;
        }

        // if country is fixed
        if (!isset($country) && isset($this->country)) {
            $country = $this->country;
        }

        // if none select from our models with best effort
        if (!isset($country) && isset($model->country_code))
            $country = $model->country_code;

        if (!isset($country) && isset($model->country))
            $country = $model->country;


        // If none and strict
        if (!isset($country) && $this->strict) {
            $this->addError($model, $attribute, 'For phone validation, country code required');
            return false;
        }

        if (!isset($country)) {
            return true;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $numberProto = $phoneUtil->parse($model->$attribute, $country);
            if ($phoneUtil->isValidNumber($numberProto)) {
                if (is_numeric($this->format)) {
                    // M.Kubler's Note: The default format includes spaces which causes issues when searching on the mobile number which is saved without spaces, so we simply remove the spaces here
                    $model->$attribute = str_replace(' ', '', $phoneUtil->format($numberProto, $this->format));
                }
                return true;
            } else {
                $this->addError($model, $attribute, 'Does not seem to be a valid phone number');
                return false;
            }
        } catch (NumberParseException $e) {
            $this->addError($model, $attribute, 'Unexpected Phone Number Format');
        } catch (Exception $e) {
            $this->addError($model, $attribute, 'Unexpected Phone Number Format or Country Code');
        }
    }

}
