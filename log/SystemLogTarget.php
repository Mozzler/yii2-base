<?php


namespace mozzler\base\log;

use mozzler\base\components\Tools;
use mozzler\base\models\SystemLog;
use Yii;
use yii\base\Arrayable;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use yii\log\Logger;
use yii\log\LogRuntimeException;
use yii\log\Target;

/**
 * Class SystemLogTarget
 * @package mozzler\base\log
 */
class SystemLogTarget extends Target
{

    public $disableInfoCollection = true; // By default Yii outputs an 'info' message even if you are only tracking 'error', this is our attempt at stopping it

    /**
     * Initializes the route.
     * This method is invoked after the route is created by the route manager.
     */
    public function init()
    {
        parent::init();
    }

    public function export()
    {
        foreach ($this->messages as $messageIndex => $message) {

            list($text, $level, $category, $timestamp) = $message;
            $level = Logger::getLevelName($level);
            if ($this->disableInfoCollection && 'info' === $level) {
                return false; // Skipping the creation of info system log entries
            }
            /** @var SystemLog $systemLog */
            $systemLog = Tools::createModel(SystemLog::class, [
                'type' => $level,
                'requestData' => $this->collectRequest(),
                'message' => $this->formatMessage($message),
                'messageData' => $this->getMessageData($message),
                'trace' => $this->getTrace($message),
                'systemData' => $this->getContextMessage($message),
                'endpoint' => $this->getEndpoint(),
                'category' => $category
            ]);
            // @todo: Work out how to batch save multiple systemLog entries?
            $save = $systemLog->save(true, null, false);
            if (!$save) {
                throw new LogRuntimeException('Unable to export SystemLog - ' . json_encode($systemLog->getErrors()));
            }

        }
    }

    /**
     * Generates the context information to be logged.
     * The default implementation will dump user information, system variables, etc.
     * @return array the context information. If empty, it means there's no context information.
     */
    protected function getContextMessage()
    {
        $context = ArrayHelper::filter($GLOBALS, $this->logVars);
        foreach ($this->maskVars as $var) {
            if (ArrayHelper::getValue($context, $var) !== null) {
                ArrayHelper::setValue($context, $var, '***');
            }
        }
        return $context;
    }


    /**
     * Format Message
     *
     * Returns the basic message string
     *
     * Note that the $message usually consists of:
     * [
     *   [0] => message (mixed, can be a string or some complex data, such as an exception object)
     *   [1] => level (integer)
     *   [2] => category (string)
     *   [3] => timestamp (float, obtained by microtime(true))
     *   [4] => traces (array, debug backtrace, contains the application code call stacks)
     *   [5] => memory usage in bytes (int, obtained by memory_get_usage()), available since version 2.0.11.
     * ]
     * Formats a log message for display as a string or as an array.
     * @param array $message the log message to be formatted.
     * The message structure follows that in [[Logger::messages]].
     * @return string the message to be saved
     */
    public function formatMessage($message)
    {
        $messageText = $message[0];
        $formattedMessage = $messageText;
        if (!is_string($messageText)) {
            if ($messageText instanceof \Throwable || $messageText instanceof \Exception) {
                // Exception
                $formattedMessage = $messageText->getMessage();
            } else if (is_array($messageText)) {

                if (isset($messageText['message'])) {
                    // If it's an array containing 'message' return just that
                    $formattedMessage = (string)$messageText['message'];
                } else {
                    $formattedMessage = "Array containing: " . implode(', ', array_keys($messageText));
                }
            } else {
                // Not sure what it is, so the messageData can return it
                if (in_array(gettype($messageText), ['boolean', 'integer', 'double', 'NULL'])) {
                    $formattedMessage = VarDumper::dumpAsString($messageText); // Get a string representation
                } else {
                    $formattedMessage = "Type: " . gettype($messageText);
                }
            }
        }
        return $formattedMessage;
    }

    /**
     * Format Message Data
     *
     * Note that the $message usually consists of:
     * [
     *   [0] => message (mixed, can be a string or some complex data, such as an exception object)
     *   [1] => level (integer)
     *   [2] => category (string)
     *   [3] => timestamp (float, obtained by microtime(true))
     *   [4] => traces (array, debug backtrace, contains the application code call stacks)
     *   [5] => memory usage in bytes (int, obtained by memory_get_usage()), available since version 2.0.11.
     * ]
     * Formats a log message for an array (or string if needed).
     * @param array $message the log message to be formatted.
     * The message structure follows that in [[Logger::messages]].
     * @return string|array the message data to be saved
     */
    public function getMessageData($message)
    {
        $messageContents = $message[0];

        // -- Use the ['messageData'] key if available
        // e.g  Yii2::error::message.messageData (if supplied) OR Yii2::error::message IF array without key messageData
        if (is_array($messageContents) && isset($messageContents['messageData'])) {
            $messageContents = $messageContents['messageData'];
        }

        if (is_string($messageContents)) {
            // We are already saving the string representation to the message field
            $messageData = null;
        } else if ($messageContents instanceof \Throwable || $messageContents instanceof \Exception) {
            // Exception info
            $exception = $messageContents;
            $messageData = [
                'Type' => get_class($exception),
                'Code' => $exception->getCode(),
                'Message' => $exception->getMessage(),
                'Line' => $exception->getLine(),
                'File' => $exception->getFile(),
//                'Trace' => $exception->getTraceAsString(),
            ];
        } else if ($messageContents instanceof Arrayable) {
            // E.g If it's a model
            $messageData = $messageContents->toArray();

            if (method_exists($messageContents, 'getErrors')) {
                // If it's a model and there's errors
                if (!empty($messageContents->getErrors())) {
                    $messageData = ArrayHelper::merge($messageData, ['_modelErrors' => $messageContents->getErrors()]);
                }
            }
        } else if (is_array($messageContents)) {
            // e.g  Yii2::error::message.messageData (if supplied) OR Yii2::error::message IF array without key messageData
            $messageData = $messageContents;
        } else {
            // It's possibly some weird thing, like a closure or object
            $messageData = strip_tags(VarDumper::dumpAsString($messageContents)); // NB: strip_tags is included as you can get weird things like xdebug entries which contain HTML that screw up the rest of the page
        }
        return $messageData;
    }

    public function getTrace($message)
    {

        // -- Add in any traces if it's from an exceptions
        $traces = [];
        if (isset($message[4])) {
//            return VarDumper::export($message[4]); // This is for testing what the trace information contains

            // Return the whole trace
            $traces['trace'] = $message[4];

            // -- Return just the trace information as well formatted lines
//            foreach ($message[4] as $trace) {
//                $traces['trace'][] = "in {$trace['file']}:{$trace['line']}";
//            }

        }

        // -- Add in the full Exception information if available
        $exception = $message[0];
        if ($exception instanceof \Throwable || $exception instanceof \Exception) {
            $traces['exception'] = self::getExceptionAsString($exception);
            if (!empty($exception->getPrevious())) {
                // Exception Chaining... Technically there could be more than 2 levels, but that's unlikely and not currently supported
                // Change to a recursive function if you think it'll be useful
                $traces['previousException'] = self::getExceptionAsString($exception->getPrevious());
            }
        }

        return $traces;
    }

    public static function getExceptionAsString($exception)
    {
        try {
            return \Yii::$app->t->returnExceptionAsString($exception);
        } catch (\Throwable $exception) {
            // If the Mozzler tools aren't defined as 't' as expected we invoke them directly
            return Tools::returnExceptionAsString($exception);
        }
    }

    /**
     * Collects summary data of current request.
     * @return array
     */
    protected function collectRequest()
    {
        if (Yii::$app === null) {
            return [];
        }

        $request = Yii::$app->getRequest();
        $response = Yii::$app->getResponse();

        $senderIp = $request->getUserIP(); // Most likely a proxy server
        $userIp = self::getRealIpAddr(); // Most likely the actual user's IP
        $userId = null;
        $userName = null;
        $sessionId = null;
        try {

            /* @var $user \yii\web\User */
            $user = Yii::$app->has('user', true) ? Yii::$app->get('user') : null;
            if ($user && ($identity = $user->getIdentity(false))) {
                /** @var \mozzler\auth\models\User $identity */
                $userId = $identity->getId();
                $userName = $identity->{$identity::$usernameField}; // This might not be defined
            }
            /* @var $session \yii\web\Session */
            $session = Yii::$app->has('session', true) ? Yii::$app->get('session') : null;
            $sessionId = $session && $session->getIsActive() ? $session->getId() : '-';

        } catch (\Throwable $exception) {
            // Don't really care about this not working
            // If the app is such that there's no user defined then we don't want to break the system log capabilities
        }


        $summary = [
            'url' => $request->getUrl(),
            'ajax' => json_encode($request->getIsAjax()),
            'method' => $request->getMethod(),
            'userAgent' => $request->getUserAgent(),
            'absoluteUrl' => $request->getAbsoluteUrl(),
            'userIp' => ($senderIp === $userIp) ? $userIp : "{$userIp}, {$senderIp}", // More likely to show the actual users IP address first, then the proxy server,
            'time' => isset($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : time(),
            'processingTime' => defined('YII_BEGIN_TIME') ? number_format(microtime(true) - YII_BEGIN_TIME, 4) . 's' : null,
            'statusCode' => $response->statusCode,
            'userId' => $userId, // The logged in user
            'userName' => $userName, // Expecting the user to have a name set, this is just a nice to have
            'sessionId' => $sessionId, // Expecting the user to have a name set, this is just a nice to have
        ];

        return $summary;
    }

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    protected function getEndpoint()
    {
        if (Yii::$app === null) {
            return null;
        }
        $request = Yii::$app->getRequest();
        return $request->getAbsoluteUrl();

    }

    /**
     * @return string
     * Based off https://gist.github.com/stavrossk/6233630
     *
     * @see returnYiiRequestAsHumanReadable
     */
    public static function getRealIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) //to check ip passed from proxy
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}
