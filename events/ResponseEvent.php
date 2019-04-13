<?php

namespace mozzler\base\events;

use Yii;
use yii\base\Event;

class ResponseEvent
{

    /**
     * @param $event Event
     *
     * Expected to be called in the web.php $config = [
     * 'components' => [
     *   'response' =>[
     *    'class' => 'yii\web\Response',
     *    'on beforeSend' => ['mozzler\base\events\ResponseEvent', 'addApiVersionNumber'],
     *   ],
     * ...
     * ],
     * ...
     * ]
     *
     *
     * You'll want to have your params.php contain an
     * 'apiVersionNumber' => '1.1' entry or whatever version number you use
     */
    public function addApiVersionNumber($event)
    {
        if (isset(\Yii::$app->params['apiVersionNumber'])) {
            $response = $event->sender;
            // Set the version header e.g 1.102
            $response->headers['x-api-version'] = \Yii::$app->params['apiVersionNumber'];
        }
    }

}