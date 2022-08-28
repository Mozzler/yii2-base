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
 *
 *
 * This is the Log target for saving entries to the System Log.
 *
 * Configuration should be done in your config/common.php file.
 *
 * 'log' => [
 * 'traceLevel' => YII_DEBUG ? 3 : 0,
 * 'targets' => [
 * // ... (other log targets, like file and email)
 * [
 * // Log errors to the system log
 * 'class' => 'mozzler\base\log\SystemLogTarget',
 * 'levels' => ['error'], // Careful if adding 'warning', 'info', 'trace'
 * 'disableInfoCollection' => true, // By default if there's any error entries then an info entry is also added by Yii, this prevents the unneeded info entries
 *  'maskVars' => [
 * '_SERVER.HTTP_AUTHORIZATION',
 * '_SERVER.PHP_AUTH_USER',
 * '_SERVER.PHP_AUTH_PW',
 * // -- Also hide the DB_DSN and users identity info
 * '_SERVER.DB_DSN',
 * '_SERVER.HTTP_COOKIE',
 * '_COOKIE._identity',
 * ],
 * ]]],
 *
 *
 * -----------------
 *   Usage
 * -----------------
 * It's expected that you'll set the levels to 'error' so you capture all errors. Yii2 will also capture all the exceptions.
 *
 * You can use `\Yii:error("This is a simple error message");`
 * Should you want to get a basic string as the error message.
 *
 * If you throw an exception that's logged automatically but you can also provide it directly to the error.
 * You can also provide specific information by providing an array with 'message' and 'messageData'. Being saved to the respective fields.
 * e.g `\Yii:error(["message": "There was an unexpected issue saving the user", "messageData": $user);`
 * In this example the messageData is a User model and the SystemLog will contain both information about the model and in the response a _modelErrors field.
 *
 * If the tracelevel is set, then there should be some trace information provided for Yii::error (or Yii::warning and the like if you are logging them) entries.
 * The traceLevel defines how many levels deep of the debug backtrace you get. The more there are, the more you can see what methods and functions were called to get to that point
 *
 * In the trace JSON array, if dealing with an exception then the exception traceAsString will be saved there, with the exception message as the main message.
 * If the exception had a $previous (exception/Throwable) set, then that will also be provided in the trace. But it only goes back one level of error chaining (you could add support for more chaining if needed)
 *
 * If you provide a random array of data then the message lists the array keys. e.g `\Yii::error(['this' => 'has some info', 'model' => $model, 'otherStuff' => $kindaUsefulInfo])` then the message  will be
 * `Array containing: this, model, otherStuff`
 *
 * You can change the systemData (Globals) being saved by adjusting the $logVars, by default it's the Globals: ['_GET', '_POST', '_FILES', '_COOKIE', '_SESSION', '_SERVER'];
 * You can also set the fields to be hidden (replaced with ****) by changing the maskVars which defaults to  '_SERVER.HTTP_AUTHORIZATION', '_SERVER.PHP_AUTH_USER', '_SERVER.PHP_AUTH_PW'
 * The maskVars uses the ArrayHelper::setValue format so you can use dot syntax
 *
 * For more information check out https://www.yiiframework.com/doc/guide/2.0/en/runtime-logging
 */
class SystemLogTarget extends Target
{

    public $disableInfoCollection = true; // By default Yii outputs an 'info' message even if you are only tracking 'error', this is our attempt at stopping it
    public $logPHPInput = true; // Add to the logVars context the php:://input for raw JSON requests and API's etc...


    // Example public $maskVars => [ '_SERVER.HTTP_AUTHORIZATION', '_SERVER.PHP_AUTH_USER', '_SERVER.PHP_AUTH_PW',  '_SERVER.DB_DSN', '_SERVER.HTTP_COOKIE', '_COOKIE._identity', ],
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
                'requestData' => $this->collectRequest($message, $category),
                'message' => $this->formatMessage($message),
                'messageData' => $this->getMessageData($message),
                'trace' => $this->getTrace($message),
                'systemData' => $this->getContextMessage($message),
                'endpoint' => $this->getEndpoint(),
                'namespace' => $this->getNamespace($message, $category),
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


        // -- Process the phpInput
        // The idea is to have the phpInput from mobile app / API endpoints, most likely provided as JSON input
        // First, we only add the phpInput if there's no $_POST request and no $_FILES.
        // We attempt to json_decode the input so if there's any fields you want masked we can do that, it also looks better in the final output

        try {

            if ($this->logPHPInput && empty($_POST) && empty($_FILES) && !empty(file_get_contents('php://input'))) {
                // Log the phpInput if there's something worth logging, most likely it's JSON sent to an API endpoint
                $context['phpInput'] = file_get_contents('php://input');
                $decodedPhpInput = json_decode($context['phpInput'], true);
                if (!empty($decodedPhpInput)) {
                    $context['phpInput'] = $decodedPhpInput;
                }
            }

        } catch (\Throwable $exception) {
            // Can't do much when you thrown an exception in an exception
            $context['exceptionProcessingPhpInput'] = \Yii::$app->t::returnExceptionAsString($exception);
        }

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
            /** @var \mozzler\base\exceptions\BaseException $exception */
            $exception = $messageContents;
            $messageData = [
                'Type' => get_class($exception),
                'Code' => $exception->getCode(),
                'Message' => $exception->getMessage(),
                'Line' => $exception->getLine(),
                'File' => $exception->getFile(),
                // If available then get extra info from the Exception
                'ContextualInfo' => method_exists($exception, 'toSystemLog') ? $exception->toSystemLog() : null,
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
            // Return the whole trace
            $traces['trace'] = $message[4];
        }

        // -- Add in the full Exception information if available
        $exception = $message[0];
        if ($exception instanceof \Throwable || $exception instanceof \Exception) {
            $traces['exception'] = self::getExceptionAsString($exception);
            if (!empty($exception->getPrevious())) {
                // Exception Chaining... Technically there could be more than 1 other exception, but that's unlikely and not currently supported
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
            return \Yii::$app->t::returnExceptionAsString($exception);
        }
    }

    /**
     * Collects summary data of current request.
     * @return array
     */
    protected function collectRequest($message, $category)
    {
        if (Yii::$app === null) {
            return [];
        }

        $request = Yii::$app->getRequest();
        $response = Yii::$app->getResponse();


        $senderIp = method_exists($request, 'getUserIP') ? $request->getUserIP() : 'N/A'; // Most likely a proxy server
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

        if (method_exists($request, 'getUrl')) {
            $summary = [
                'url' => $request->getUrl(),
                'ajax' => json_encode($request->getIsAjax()),
                'method' => $request->getMethod(),
                'category' => $category,
                'userAgent' => $request->getUserAgent(),
                'absoluteUrl' => $request->getAbsoluteUrl(),
                'userIp' => ($senderIp === $userIp) ? $userIp : "{$userIp}, {$senderIp}", // More likely to show the actual users IP address first, then the proxy server,
                'processingTime' => defined('YII_BEGIN_TIME') ? number_format(microtime(true) - YII_BEGIN_TIME, 4) . 's' : null,
                'statusCode' => $response->statusCode,
                'userId' => $userId, // The logged in user
                'userName' => $userName, // Expecting the user to have a name set, this is just a nice to have
                'sessionId' => $sessionId, // Expecting the user to have a name set, this is just a nice to have
            ];
        } else {
            // It's likely a console request
            $summary = [
                'category' => $category,
                'processingTime' => defined('YII_BEGIN_TIME') ? number_format(microtime(true) - YII_BEGIN_TIME, 4) . 's' : null,
                'userId' => $userId, // The logged in user
                'userName' => $userName, // Expecting the user to have a name set, this is just a nice to have
                'sessionId' => $sessionId, // Expecting the user to have a name set, this is just a nice to have
            ];
        }

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
        return method_exists($request, 'getAbsoluteUrl') ? $request->getAbsoluteUrl() : 'N/A';
    }

    /**
     * @return string
     * Based off https://gist.github.com/stavrossk/6233630
     *
     * @see returnYiiRequestAsHumanReadable
     */
    public static function getRealIpAddr()
    {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) //to check ip passed from proxy
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * @param $message
     * @param $category string
     * @return string
     *
     * Due to the way Yii deals with Exceptions by default if you want to change the status code
     * you need to extend from the \yii\web\HttpException however if you do then the Category
     * is set to yii\web\HttpException:<statusCode> instead of your custom exception name.
     *
     * This sets the Exceptions to use get_class
     * If the exception also has a statusCode defined then it will include that
     * e.g app\exceptions\SMSFailureException:502
     *
     * This defaults to the Yii Logger provided $category
     */
    protected function getNamespace($message, $category)
    {
        $possibleException = $message[0];

        // -- Use the ['messageData'] key if available
        if (is_array($possibleException) && isset($possibleException['messageData'])) {
            $possibleException = $possibleException['messageData'];
        }

        if ($possibleException instanceof \Throwable || $possibleException instanceof \Exception) {
            // Use the actual Exception, not something like yii\web\HttpException:502 if the exception is a custom ApiFaliureException or some such
            $category = get_class($possibleException);

            if (property_exists($possibleException, 'statusCode')) {
                // Add in the statusCode, if defined
                $category .= ':' . $possibleException->statusCode;
            }
        }
        return $category;
    }
}
