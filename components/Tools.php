<?php

namespace mozzler\base\components;

use MongoDB\BSON\ObjectId;
use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;

class Tools extends Component
{

    public $cachedGetModelResults = [];
    public static $isApiRegex = '/api|OauthModule/';

    public static function app()
    {
        return \Yii::$app;
    }

    public static function load($className, $config = [])
    {
        $className = self::getClassName($className);

        return \Yii::createObject($className, $config);
    }

    public static function renderWidget($widgetName, $config = [], $wrapConfig = true)
    {
        if ($wrapConfig) {
            $config = ['config' => $config];
        }

        $widget = self::getWidget($widgetName, $config);
        $output = $widget::widget($config);
        return $output;
    }

    /**
     * Render a twig template
     *
     * @param string $template Twig template to render
     * @param array $data Data to pass to the template
     * @param array $options Any template rendering options
     * @return    string    Returns the template result
     */
    public static function renderTwig($template, $data = [], $options = [])
    {
        $twig = TwigFactory::getEnvironment();
        $twigTemplate = $twig->createTemplate($template);
        $output = $twigTemplate->render($data);

        if (isset($options["recursive"]) && $options["recursive"] === true) {
            $count = 0;
            if (!isset($options["recursiveLimit"]) || !is_int($options["recursiveLimit"])) {
                $options["recursiveLimit"] = 10;
            }

            while (preg_match('/' . $options["recursive"] . '/', $output)) {
                $twigTemplate = $twig->createTemplate($output);
                $output = $twigTemplate->render($data);

                $count++;

                if ($count > $options["recursiveLimit"]) {
                    \Yii::warning("Recursive limit (" . $options['recursiveLimit'] . ") hit in renderTemplate(). Check your template and recursive regex doesn't cause a never-ending loop, or increase the `recursiveLimit` option.");
                    break;
                }
            }
        }

        return $output;

    }

    public static function getWidget($widget, $config = [])
    {
        $className = self::getClassName($widget);
        ob_start();
        ob_implicit_flush(false);
        $widget = new $className($config);
        ob_get_clean();
        return $widget;
    }

    public static function getClassName($className)
    {
        return '\\' . preg_replace("/\./", "\\\\", $className);
    }

    /**
     * Get Model Class Name
     *
     * Get's just the final class name, useful for getting the name of a model without the full namespace
     * Example usage:
     * $modelName = \Yii::$app->t::getModelClassName($user);
     * would return 'User' as the model name instead of \app\models\User or \mozzler\auth\models\User
     *
     * Can obviously be used for other object types as well
     *
     * @param $model \mozzler\base\models\Model|object|mixed
     * @return false|string
     */
    public static function getModelClassName($model)
    {
        $classname = get_class($model);
        if ($pos = strrpos($classname, '\\')) return substr($classname, $pos + 1);
        return $pos;
    }

    /**
     * Create an empty model.
     *
     * @param string $className Class name of the model to create (eg: `mozzler\auth\user`).
     * @param array $data Default data to populate the model
     * @return \mozzler\base\models\Model   Returns a new model
     * @throws \yii\base\InvalidConfigException
     */
    public static function createModel($className, $data = [])
    {
        /** @var \mozzler\base\models\Model $model */
        $model = Yii::createObject(self::getClassName($className));

        if ($data) {
            $model->load($data, "");
        }


        $model->scenario = "create";
        $model->loadDefaultValues();

        return $model;
    }

    /**
     * Get a model from the database
     */
    public static function getModel($className, $filter = [], $checkPermissions = true)
    {
        $model = static::createModel($className);
        if (!is_array($filter)) {
            $filter = ['_id' => self::ensureId($filter)];
        }
        return $model->findOne($filter, $checkPermissions);
    }

    /**
     * @param $className string
     * @param $filter array|ObjectId|string
     * @return mixed
     * This will always work without a permissions check so shouldn't be used for basics
     * It's mainly for use when exporting or when you are likely to lookup the same information multiple times in a request.
     */
    public function cachedGetModel($className, $filter)
    {
        $nameSpace = $className . '-' . json_encode($filter);
        if (isset($this->cachedGetModelResults[$nameSpace])) {
            return $this->cachedGetModelResults[$nameSpace];
        }
        $model = self::getModel($className, $filter, false);
        $this->cachedGetModelResults[$nameSpace] = $model;
        return $model;
    }

    /**
     * Get existing models from the database.
     *
     * Example usage:
     *
     * ```
     * // get all models, but limit to 10 results sorted by inserted DESC and ignore RBAC
     * $models = Tools::getModels('app\models\BlogPost', [], [
     *    'limit' => 10
     *    'offset' => 0,
     *    'orderBy' => ['inserted' => SORT_DESC],
     *    'checkPermissions' => false
     * ]);
     *
     * // get models using a filter
     * $models = Tools::getModels('app\models\BlogPost', [
     *    "status" => "draft"
     * ]);
     *
     * @param string $className Class name of the model to get
     * @param array $filter MongoDB filter to apply to the query
     * @param array $options
     * @return    array    Returns an array of found models. If none found, returns an empty array.
     */
    public static function getModels($className, $filter = [], $options = [])
    {
        $options = ArrayHelper::merge([
            'limit' => 20,
            'offset' => null,
            'orderBy' => [],
            'checkPermissions' => true
        ], $options);

        $limit = $options['limit'];
        $offset = $options['offset'];
        $orderBy = $options['orderBy'];
        $checkPermissions = $options['checkPermissions'];

        $model = static::createModel($className);
        $query = $model->find($checkPermissions);

        if ($filter)
            $query->where = $filter;

        if ($limit)
            $query->limit = $limit;

        if ($offset)
            $query->offset = $offset;

        if ($orderBy)
            $query->orderBy = $orderBy;

        return $query->all();
    }


    /**
     * Get a count of the models in the database.
     *
     * Example usage:
     *
     * ```
     * // Get a count of the draft blog posts
     * $draftBlogPostsCount = Tools::getModels('app\models\BlogPost', ["status" => "draft"], [
     *    'checkPermissions' => false
     * ]);
     * ```
     *
     * @param string $className Class name of the model to get
     * @param array $filter MongoDB filter to apply to the query
     * @param array $options
     * @return   int   Returns the number of models in the collection
     */
    public static function countModels($className, $filter = [], $options = [])
    {
        $options = ArrayHelper::merge([
            'offset' => null,
            'checkPermissions' => false
        ], $options);

        $offset = $options['offset'];
        $checkPermissions = $options['checkPermissions'];

        /** @var \mozzler\base\models\Model $model */
        $model = static::createModel($className);
        $query = $model->find($checkPermissions);

        if ($filter)
            $query->where = $filter;

        if ($offset)
            $query->offset = $offset;

        return $query->count();
    }


    /**
     * Send an email.
     *
     * Using this method directly will send an email in a blocking manner. Uses twig
     * template files and email layout template.
     *
     * Usage example:
     *
     * ```
     * \Yii::$app->t->sendEmail(
     *    ["john@company.com" => "John Doe"],
     *    "Hello!",
     *    "hello.twig",
     *    [
     *        "messsage" => "hello world"
     *    ],
     *    [
     *        "smtpSettings" => [
     *            "username" => <username>
     *            "password" => <password>
     *            "host" => <host>
     *            "port" => <port>
     *            "encryption" => "ssl"
     *        ],
     *        "from": [
     *            "name" => "Michael",
     *            "email" => "michael@company.com"
     *        ],
     *        "replyTo": [
     *            "name" => "Info",
     *            "email" => "info@company.com"
     *        ]
     *    ]
     * )
     * ```
     *
     * Configuration:
     *
     * SMTP settings and default from, replyTo addresses are loaded in the following order:
     *
     * - via config if specified when calling sendEmail
     * - via `web.php` configuration
     *
     * @param string $to Email recipient(s) in the format `[<email address> => <name>]`
     * @param string $subject Email subject.
     * @param string $template Name of template to use for rendering the email (eg: `user/welcome.twig`). Email templates are all prefixed by `emails/`, but this doesn't need to be included when specifying the template name.
     * @param array $data Data to send to the email template.
     * @param array $config Config for sending the email such as; setting `replyTo` or `from` which is merged with `$params['config']`
     * @return bool whether this message is sent successfully.
     */
    public static function sendEmail($to, $subject, $template, $data = [], $config = [])
    {
        Yii::$app->mailer->view->params = \Yii::$app->params;

        $mailer = \Yii::$app->mailer;
        // Adding in the params to the data. mainly for access to something like {{ _params.emailAssetsUrl }} for a Url to where you might store files on a CDN
        $message = $mailer->compose($template, ArrayHelper::merge(['_params' => \Yii::$app->params], $data))
            ->setTo($to)
            ->setSubject($subject);


        $config = ArrayHelper::merge(\Yii::$app->params['mozzler.base']['email'], $config);

        if (isset($config['from'])) {
            $message->setFrom($config['from']);
        }

        if (isset($config['replyTo'])) {
            $message->setReplyTo($config['replyTo']);
        }

        return $message->send();
    }

    /**
     * @param $exception \Throwable
     * @return string
     */
    public static function returnExceptionAsString($exception)
    {
        /** @var $exception \Exception */
        return "\n#### EXCEPTION ####\nType: " . get_class($exception) . "\nCode: {$exception->getCode()}\nMessage: {$exception->getMessage()}\nLine: {$exception->getLine()}\nFile: {$exception->getFile()}\nTrace\n--------\n{$exception->getTraceAsString()}";
    }


    /**
     * Array Keys Exist
     *
     * Check if the Keys are in the array.
     * Especially useful for checking 3rd party API responses.
     * @param string[] $keys
     * @param array $array
     * @return bool
     *
     * Based off https://stackoverflow.com/questions/13169588/how-to-check-if-multiple-array-keys-exists
     */
    public static function arrayKeysExist(array $keys, array $array)
    {
        return !array_diff_key(array_flip($keys), $array);
        // Alternatively could use the ArrayHelper::keyExists and loop through the keys to check.
    }

    /**
     * Ensure an ID is a proper MongoDB ID object.
     *
     * @pararm    string    $id        ID to convert to a MongoDB ID object
     * @return    \MongoDB\BSON\ObjectId    Returns a MongoID object
     */
    public static function ensureId($id)
    {
        return new \MongoDB\BSON\ObjectId($id);
    }

    public static function trace($message, $location = null, $dump = false)
    {
        \Yii::trace($dump ? print_r($message, true) : $message, $location);
    }

    public static function warning($message, $location = null, $dump = false)
    {
        \Yii::warning($dump ? print_r($message, true) : $message, $location);
    }

    public static function info($message, $location = null, $dump = false)
    {
        \Yii::info($dump ? print_r($message, true) : $message, $location);
    }

    public static function error($message, $location = null, $dump = false)
    {
        \Yii::error($dump ? print_r($message, true) : $message, $location);
    }

    /**
     * @param $microtimeStart float microtime(true)
     * @return string e.g 3.546s
     *
     * Takes the microtime the event started and returns how many seconds ago that was, to 3 decimal places
     * Example usage:
     *
     * $microtimeStart = microtime(true);
     * // Do a bunch of processing things
     * echo("It took " . \Yii::$app->t::processingTimeResponse($microtimeStart) . " to process this");
     *
     *
     */
    public static function processingTimeResponse($microtimeStart)
    {
        return number_format(microtime(true) - $microtimeStart, 3) . 's';
    }

    public static function isApi()
    {
        if (isset(\Yii::$app) && isset(\Yii::$app->controller) && isset(\Yii::$app->controller->module)) { // Ensure the controller and module exist otherwise the codeception tests barf
            $controllerClass = \Yii::$app->controller->module::className();
            Yii::debug("isAPI says the controller class is: {$controllerClass}");
            return preg_match(static::$isApiRegex, $controllerClass) == 1;
        } else {
            Yii::warning("Can't find the \Yii::\$app->controller->module so can't check if the request is API or not");
            return false; // NB: It's likely being run as a test or via CLI
        }
    }
}