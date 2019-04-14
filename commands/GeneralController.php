<?php

namespace mozzler\base\commands;

use app\components\GeneralTools;
use app\components\viterra\exceptions\ApiException;
use Egulias\EmailValidator\EmailParser;
use Egulias\EmailValidator\Warning\ObsoleteDTEXT;
use MongoDB\BSON\ObjectId;
use mozzler\base\components\Tools;
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

    public $emailTemplate =  'defaultEmail.twig';

    /**
     * Send a test email
     *
     * Take note that this expects a twig file in views/emails/defaultEmail.twig
     * which can output the {{content}} field in your project
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
            $this->stdout("✓ Emailing: $emailAddress\n", Console::FG_GREEN);
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

            $this->stdout("Email subject: $emailSubject\n");
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
}
