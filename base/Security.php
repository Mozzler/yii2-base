<?php

namespace mozzler\base\base;

use yii\base\Security as SecurityComponent;

/**
 * Security provides a set of methods to handle common security-related tasks.
 *
 * Extends the basic security component and adds the generateRandomPassword method
 * @see \yii\base\Security
 *
 * Config file:
 *
 * 'container' => [
 *  'definitions' => [
 *   'yii\base\Security' => [
 *    'class' => 'mozzler\base\base\Security'
 * ]]]
 *
 * Usage:
 * $password = Yii::$app->getSecurity()->generateRandomPassword($length, $usableCharacters);
 */
class Security extends SecurityComponent
{

    public $randomPasswordCharacterSet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789-+()[]*#%^@/'; // Intentionally not using l, i, o or 0 as it's too hard to differentiate them
    public $randomPasswordLength = 9; // This can't be set higher than the number of characters in the character set

    /**
     * Generate Random Password
     *
     * Useful for creating human readable random codes or passwords.
     *
     * Especially for things like verification codes,
     * in which case you would set the character set to 0123456789 and the length to 6
     *
     * @param int|null $length
     * @param string|null $characterSet
     * @return string A random password
     */
    public function generateRandomPassword($length = null, $characterSet = null)
    {
        if (empty($length)) {
            $length = $this->randomPasswordLength;
        }
        if (empty($characterSet)) {
            $characterSet = $this->randomPasswordCharacterSet;
        }
        // Based off https://stackoverflow.com/a/5438778/11345827
        $seed = str_split($characterSet);
        shuffle($seed);
        $rand = '';
        foreach (array_rand($seed, $length) as $k) {
            $rand .= $seed[$k];
        }
        return $rand;
    }

}