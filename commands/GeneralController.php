<?php

namespace mozzler\base\commands;

use app\components\GeneralTools;
use app\components\viterra\exceptions\ApiException;
use Egulias\EmailValidator\EmailParser;
use Egulias\EmailValidator\Warning\ObsoleteDTEXT;
use MongoDB\BSON\ObjectId;
use mozzler\base\components\IndexManager;
use mozzler\base\components\Tools;
use mozzler\base\models\Model;
use mozzler\base\models\Task;
use Yii;
use yii\console\Controller;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\console\ExitCode;
use yii\validators\EmailValidator;

/**
 * General Controller
 *
 * This is used to run general commands like
 * Sending test emails or getting a list of the models and their fields
 *
 *
 * // NB: Don't forget to update your project's config/console.php controllerMap array for this command to appear
 */
class GeneralController extends BaseController
{

    public $emailTemplate = '@mozzler/base/views/layouts/emails/html.twig';

    /**
     * Send a test email
     *
     * Sends a generic test email to the email address you specify
     * This is mainly useful to ensure the \Yii::$app->mailer is
     * configured correctly and that you can send
     *
     * @param $emailAddress
     * @return int
     */
    public function actionTestEmail($emailAddress)
    {

        if (empty($emailAddress)) {
            $this->stderr("#### Error ####\nNo email address provided\n", Console::FG_RED);
            return ExitCode::USAGE;
        }

        // -- Ensure it's at least sort of looking like a valid email address
        $validator = new EmailValidator();
        if ($validator->validate($emailAddress, $error)) {
            $this->stdout("Email Address: ");
            $this->stdout("{$emailAddress}\n", Console::FG_CYAN);

        } else {
            $this->stderr("#### Error ####\nInvalid email address provided {$emailAddress}\n", Console::FG_RED);
            return ExitCode::USAGE;
        }


        try {

            // -- Start creating the email contents
            $date = new \DateTime();
            $currentTimestamp = $date->format('Y-m-d H:i:s P');

            // ==================================
            //    Send the Email
            // ==================================

            $emailSubject = "Test Email";
            $content = <<<EOC
This is a test email to {$emailAddress}
Sent on {{ "now" |date("m/d/Y\") }}
EOC;

            $this->stdout("Email Subject: ");
            $this->stdout("$emailSubject\n", Console::FG_CYAN);
            $this->stdout("Email Template: ");
            $this->stdout("{$this->emailTemplate}\n", Console::FG_CYAN);
            $emailData = ['content' => $content];

            // -- We use the configured Yii Tools version
            // in case there's specific instance changes compared to the default instantiation
            // But make sure you've got `'t' => [  'class' => '\mozzler\base\components\Tools' ],` in your common config
            $emailSent = \Yii::$app->t::sendEmail(
                $emailAddress,
                $emailSubject,
                $this->emailTemplate,
                $emailData
            );

            if ($emailSent) {
                $this->stdout("✓ Email sent successfully\n", Console::FG_GREEN);
            } else {
                $this->stderr("✘ Emailing failed\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;

            }
        } catch (\Throwable $exception) {
            $this->stderr("There was an exception with sending the test email: " . Tools::returnExceptionAsString($exception));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Model Rules
     *
     * Outputs a list of the models and their 'rules'
     * This is mainly used for helping cover any OWASP sanitisation requirements
     * or for basic debugging
     *
     * @return int
     */
    public function actionModelRules($asJson = false)
    {
        /** @var IndexManager $indexManager */
        $indexManager = \Yii::createObject('mozzler\base\components\IndexManager');

        // find all the models
        $models = $indexManager->buildModelClassList();

        $json = [];
        foreach ($models as $className) {
            /** @var Model $model */
            $model = \Yii::createObject($className);
            $rules = $model->rules();

            if (!$asJson) {

                $this->stdout('Model: ' . $className . "\n", Console::FG_GREEN);
                $this->stdout(print_r($rules, true));
                $this->stdout("------\n\n");
            } else {
                $json[] = ['model' => $className,
                    'rules' => $rules];
            }
        }

        if ($asJson) {
            $this->stdout(json_encode($json, JSON_PRETTY_PRINT));
        }

        return ExitCode::OK;
    }
}
