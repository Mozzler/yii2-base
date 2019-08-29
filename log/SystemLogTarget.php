<?php


namespace mozzler\base\log;

use mozzler\base\components\Tools;
use mozzler\base\models\SystemLog;
use Yii;
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
            /** @var SystemLog $systemLog */
            $systemLog = Tools::createModel(SystemLog::class, [
                'type' => $level,
                'request' => $this->collectRequest(), // This can be removed if the getContextMessage() returns enough info
                'message' => $this->formatMessage($message),
                'data' => $this->getContextMessage($message),
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
    protected function getContextMessage($message)
    {
        $vars = ArrayHelper::filter($GLOBALS, $this->logVars);

        $traces = [];
        if (isset($message[4])) {
            foreach ($message[4] as $trace) {
                $traces[] = "in {$trace['file']}:{$trace['line']}";
            }
        }
        $vars['traces'] = $traces;
        return $vars;
    }


    /**
     * Formats a log message for display as a string or as an array.
     * @param array $message the log message to be formatted.
     * The message structure follows that in [[Logger::messages]].
     * @return string|array the message to be used
     */
    public function formatMessage($message)
    {
        list($text, $level, $category, $timestamp) = $message;
        $level = Logger::getLevelName($level);


        if (!is_string($text)) {
            // exceptions may not be serializable if in the call stack somewhere is a Closure
            if ($text instanceof \Throwable || $text instanceof \Exception) {
                try {
                    $text = \Yii::$app->t->returnExceptionAsString($text);
                } catch (\Throwable $exception) {
                    // If the Mozzler tools aren't defined as 't' as expected.
                    $text = Tools::returnExceptionAsString($text);
                }
            } else if (is_array($text)) {
                // Save the provided array
            } else {
                $text = VarDumper::export($text);
            }
        }
        return $text;
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
                $userId = $identity->getId();
                $userName = $identity->name; // This might not be defined
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
            'statusCode' => $response->statusCode,
            'userId' => $userId, // The logged in user
            'userName' => $userName, // Expecting the user to have a name set, this is just a nice to have
            'sessionId' => $sessionId, // Expecting the user to have a name set, this is just a nice to have
        ];

        return $summary;
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
