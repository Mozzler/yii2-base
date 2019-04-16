<?php

namespace mozzler\base\events;

use Yii;
use yii\base\Event;
use yii\web\Response;

class RequestEvent
{

    /**
     * @param $event Event
     * @throws \yii\web\NotFoundHttpException
     *
     * Expected to be called in the web.php $config = [
     * 'on beforeAction' => ['\mozzler\base\events\RequestEvent', 'handleDeniedModes'],
     * ...
     * ]
     *
     * You then set the params.php to include something like:
     * 'deniedModes' => ['api'],
     *
     * The two modes are 'api' or 'web'
     * The main use of this is so that you can have servers that just return API or just process Web requests.
     * If you deny both then you'd better like using the command line
     */
    public function handleDeniedModes($event)
    {
        if (!isset(\Yii::$app->params['deniedModes'])) {
            Yii::debug("No Yii params['deniedModes'] set, not rejecting the request");
            return $event;
        }
        $deniedModes = \Yii::$app->params['deniedModes'];
        $isApi = \mozzler\base\components\Tools::isApi();
        if ($isApi && in_array('api', $deniedModes)) {
            Yii::error("Api in the Yii params['deniedModes'], rejecting the request");
            Yii::$app->response->statusCode = 404;

            // -- Setting a nicer JSON response
            Yii::$app->response->format = Response::FORMAT_JSON;
            Yii::$app->response->content = json_encode([
                "name" => "API not available",
                'message' => 'Access Denied',
                "code" => 404,
                "type" => "yii\\web\\NotFoundHttpException"
            ]);
            $event->isValid = false;
            return $event;
        }
        if (!$isApi && in_array('web', $deniedModes)) {
            Yii::error("Web in the Yii params['deniedModes'], rejecting the request");
            Yii::$app->response->statusCode = 404;
            $event->isValid = false;
            return $event;
        }
        Yii::error("Denied Request modes check - isApi: " . json_encode($isApi) . " and the denied requestModes is " . json_encode($deniedModes) . " but it's not either, so allowing the request");
        return $event;
    }

}