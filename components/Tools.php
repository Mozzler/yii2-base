<?php

namespace mozzler\base\components;

use MongoDB\BSON\ObjectId;
use Yii;
use yii\base\Component;
use yii\caching\ArrayCache;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

class Tools extends Component
{

    public $cachedGetModelResults = [];
    public static $isApiRegex = '/api|OauthModule/';
    public $requestCacheName = 'requestCache';

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
     * @param $model \mozzler\base\models\Model|object|mixed|string
     * @return false|string
     */
    public static function getModelClassName($model)
    {
        if (!is_string($model)) {
            // Get the classname from the Object instance
            $classname = get_class($model);
        } else {
            // Assuming you've provided something like \app\models\User::class (which is just '\app\models\User')
            $classname = $model;
        }
        if ($pos = strrpos($classname, '\\')) return substr($classname, $pos + 1);
        return $pos;
    }

    /**
     * Create an empty model.
     *
     * @template T
     * @param class-string<T> $className Class name of the model to create (eg: `mozzler\auth\user`).
     * @param array $data Default data to populate the model
     * @return T Returns a new model
     * @throws \yii\base\InvalidConfigException
     *
     * !!!!!!!!!!!!!!
     * !!!  NOTE  !!!
     * !!!!!!!!!!!!!!
     * !! Because there's a call to getClassName if you want to redefine the model (e.g the config/common.php's container.definitions) you'll need to update both versions
     * e.g:
     * config/common.php:
     * return [...,
     *    'container' => [
     *      'definitions' => [
     *          'mozzler\base\models\File' => [
     *              'class' => 'app\models\File',
     *          ],
     *          '\\mozzler\\base\\models\\File' => [
     *              'class' => 'app\models\File',
     *          ],
     * ]]];
     *
     */
    public static function createModel($className, $data = [])
    {
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
     *
     * @template T
     * @param class-string<T> $className
     * @param array $filter
     * @param bool $checkPermissions
     * @return T
     * @throws \yii\base\InvalidConfigException
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
     * @template T
     * @param class-string<T> $className
     * @param $className string
     * @param $filter array|ObjectId|string
     * @return T
     * @throws \yii\base\InvalidConfigException
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
     *    'checkPermissions' => false,
     *    'select' => ['name', 'updatedAt'], // Note that the default response will include all the scenario fields but they'll be listed as null, so you'll want to set it to a scenario with the same fields as well
     * ]);
     *
     * // get models using a filter
     * $models = Tools::getModels('app\models\BlogPost', [
     *    "status" => "draft"
     * ]);
     *
     * @template T
     * @param class-string<T> $className Class name of the model to get
     * @param array $filter MongoDB filter to apply to the query
     * @param array $options
     * @return T[] Returns an array of found models. If none found, returns an empty array.
     */
    public static function getModels($className, $filter = [], $options = [])
    {
        $options = ArrayHelper::merge([
            'limit' => 20,
            'offset' => null,
            'orderBy' => [],
            'select' => [], // The fields to return, keep empty if you want them all
            'checkPermissions' => true
        ], $options);

        $limit = $options['limit'];
        $offset = $options['offset'];
        $orderBy = $options['orderBy'];
        $select = $options['select'];
        $checkPermissions = $options['checkPermissions'];

        $model = static::createModel($className);
        $query = $model->find($checkPermissions);

        if ($filter)
            $query->where = $filter;

        if ($limit)
            $query->limit = $limit;

        if ($offset)
            $query->offset = $offset;

        if ($select)
            $query->select = $select;

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
     * @param string|string[] $to Email recipient(s) in the format `[<email address> => <name>]` or simply their email address e.g 'michael+mozzler-base@greyphoenix.biz'
     * @param string $subject Email subject.
     * @param string $template Name of template to use for rendering the email (eg: `user/welcome.twig`). Email templates are all prefixed by `emails/`, but this doesn't need to be included when specifying the template name.
     * @param array $data Data to send to the email template.
     * @param array $config Config for sending the email such as; setting `replyTo` or `from` which is merged with `$params['config']`
     * @return bool whether this message is sent successfully.
     */
    public static function sendEmail($to, $subject, $template, $data = [], $config = [])
    {
        $profileName = 'Emailing-to-' . self::getEmailAddressesStringFromEmailTo($to) . '-subject-' . str_replace(' ', '_', $subject) . "-at-" . time();
        \Yii::beginProfile($profileName, "Emailing");
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

        $sent = $message->send();
        \Yii::endProfile($profileName, "Emailing");
        return $sent;

    }

    /**
     * Get Email Addresses String From Email To
     *
     * @param $emailTo array|string
     * @param $separator string
     * @return string
     *
     * Used for converting the array syntax used by the sendEmail($to...) to a string of the email addresses
     *
     * e.g $emailTo =[
     *  0 => 'newcarsales@greyphoenix.biz',
     *  'michael+staging@drivible.com' => 'Mr Drivible',
     * ]
     * should return:
     *
     * "newcarsales@greyphoenix.biz,michael+staging@drivible.com"
     *
     */
    public static function getEmailAddressesStringFromEmailTo($emailTo, $separator = ',')
    {
        $emailAddresses = [];
        if (empty($emailTo)) {
            return '';
        }
        if (is_string($emailTo)) {
            return $emailTo;
        }

        if (!is_array($emailTo)) {
            \Yii::warning("Unkown type provided to getEmailAddressesStringFromEmailTo for emailTo. Expected a string or array instead got: " . VarDumper::export($emailTo));
            return '';
        }
        foreach ($emailTo as $key => $value) {
            if (is_integer($key) && is_string($value)) {
                // e.g 0 => 'newcarsales@greyphoenix.biz', we want the value
                $emailAddresses[] = $value;
            }
            if (is_string($key) && is_string($value)) {
                // e.g 'michael+staging@drivible.com' => 'Mr Drivible',, we want the key
                $emailAddresses[] =  $key;
            }
        }
        return implode($separator, $emailAddresses);
    }


    /**
     *
     * This was originally created in the Stripe Manager
     *
     * @param string|array $to Email Address
     * @param string $subject Subject text
     * @param string $template File path
     * @param array $data Data to provide to the Twig renderer for the templating
     * @param array $config Any custom configuration e.g 'from' or 'bcc'
     * @param array $files An array of the file names and file options. Example: $files = ['/tmp/fileToUpload.pdf' => [], '/tmp/622748c3c92d45ccbe87970d.pdf' => ['fileName' => 'important document for you.pdf', 'contentType' => 'application/pdf']] as per https://www.yiiframework.com/doc/api/2.0/yii-mail-messageinterface#attach()-detail
     * @return bool
     */
    public static function sendEmailWithAttachment($to, $subject, $template, $data = [], $config = [], $files = [])
    {
        $profileName = 'Emailing-with-' . count($files) . '-attachments-to-' (is_array($to) ? array_keys($to) : $to) . '-subject-' . str_replace(' ', '_', $subject) . "-at-" . time();
        \Yii::beginProfile($profileName, "EmailingWithAttachments");
        Yii::$app->mailer->view->params = Yii::$app->params;

        $mailer = Yii::$app->mailer;
        // Adding in the params to the data. mainly for access to something like {{ _params.emailAssetsUrl }} for a Url to where you might store files on a CDN
        $message = $mailer->compose($template, ArrayHelper::merge(['_params' => Yii::$app->params], $data))
            ->setTo($to)
            ->setSubject($subject);


        $config = ArrayHelper::merge(Yii::$app->params['mozzler.base']['email'], $config);

        foreach ($config as $configName => $configValue) {
            $configSet = 'set' . ucfirst($configName); // e.g setFrom or setReplyTo
            if (method_exists($message, $configSet)) {
                $message->$configSet($configValue);
            }
        }
        if (!empty($files)) {
            foreach ($files as $fileName => $fileOptions) {
                $message->attach($fileName, $fileOptions);
            }
        }

        // -- Actually send
        $sent = $message->send();
        \Yii::endProfile($profileName, "EmailingWithAttachments");
        return $sent;
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
     * @param $exception \Throwable
     * @return string
     *
     * This is useful in the message when rethrowing an exception, we get the message, file locaiton and line number all in one line, where as returnExceptionAsString() gives you everything
     *
     *  'Exception Message' at fileLocation::lineNumber
     *
     *
     * Example usage:
     *
     * try {
     *   ... Some code that will probably throw an exception we want to know about, but don't want to continue processing because of
     * } catch (\Throwable $exception) {
     *
     *  if (!$exception instanceof BaseException) {
     *      $exception = new DataCommandsException("Exception whilst batch inserting the $className preload / seed data -- " . \Yii::$app->t::returnExceptionMessageAndFileLine($exception), $exception->getCode(), $exception, [
     *      'className' => $className,
     *      'classData' => $classData,
     *  ]);
     *  }
     *  \Yii::error($exception);
     * }
     */
    public static function returnExceptionMessageAndFileLine($exception)
    {
        if (empty($exception) || !$exception instanceof \Throwable) {
            return ""; // Incorrect Exception provided
        }
        /** @var $exception \Exception */
        return "'{$exception->getMessage()}' at {$exception->getFile()}::{$exception->getLine()}";
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
     * @pararm    string|null    $id        ID to convert to a MongoDB ID object
     * @return    \MongoDB\BSON\ObjectId    Returns a MongoID object
     */
    public static function ensureId($id = null)
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


    /**
     * @return object|ArrayCache
     * @throws \yii\base\InvalidConfigException
     *
     * Note: This isn't a static function you need to use \Yii::$app->t->getRequestCache();
     *
     * Example config/common.php if you want to specify one (ensure serializer is false)
     *
     * ['components' => [
     *  'requestCache' => [
     *   'class' => 'yii\caching\ArrayCache',
     *   'serializer' => false,
     *  ],
     * ]
     *
     * @see \mozzler\base\models\Model::getCachedModelFields()
     *
     * Example usage:
     * function cachedStuff() {
     *  $requestCache = \Yii::$app->t->getRequestCache();
     *  $key = 'cacheKey'; // Fill in this
     *  if ($requestCache->exists($key)) {
     *      return $requestCache->get($key);
     *  }
     *  $value = someExpensiveToComputerFunction();
     *  $requestCache->set($key, $value);
     *  return $value
     * }
     *
     * NOTE: THIS IS NOT A STATIC FUNCTION!!
     * Use -> not ::
     */
    public function getRequestCache()
    {
        // -- Use a session cache if defined otherwise create one
        if (\Yii::$app->has($this->requestCacheName)) {
            $requestCacheName = $this->requestCacheName;
            $requestCache = \Yii::$app->get($requestCacheName);
        } else {

            if (empty($this->requestCache)) {
                \Yii::debug("Tools Request Cache ArrayCache is being created");
                /** @var ArrayCache $requestCache */
                $requestCache = \Yii::createObject(\yii\caching\ArrayCache::class, ['serializer' => false]);
                $requestCache->serializer = false;
                $this->requestCache = $requestCache;
            } else {

                $requestCache = $this->requestCache;
            }

        }
        return $requestCache;
    }


    /**
     * Get the currently logged in user
     *
     * This can be really useful in model scenarios, model fields and RBAC policies (well any that aren't the user, you'll want to disable model field caching for users if doing that),  etc..
     * e.g \Yii::$app->t::getCurrentUser()
     * @return User|null
     * @throws \Throwable
     */
    public static function getCurrentUser()
    {
        if (!\Yii::$app->has('user')) {
            // -- Likely a CLI request
            return null;
        }
        /** @var User $user */
        $user = \Yii::$app->user->getIdentity();
        if (empty($user)) {
            return null;
        }
        return $user;
    }

    /**
     * Yield Models
     *
     * When you want to easily foreach over entries
     *
     * Note that the sort order is GOING to be out of order, mostly reversed but going forwards in batches
     * @template T
     * @param class-string<T> $className Class name of the model to get
     * @param array $filter
     * @param array $options e.g ['checkPermissions' => true] or setting the sort, although you'll get it in the opposite order than what's requested as we start from the end and also work in batches
     * @param int $limit this doesn't support 1 as a limit. It's how many records are grabbed per batch, change it depending on the size of the models, the amount of ram you want and the database load you are willing to accept
     * @return \Generator|Void
     *
     * Example usage:
     * foreach ($this->yieldModels(User::class, ['email' => ['$exists' => true]]) as $user) {
     *   $user->.... Do stuff on the users
     * }
     */
    public static function yieldModels($class, $filter = [], $options = [], $limit = 10)
    {

        $count = \Yii::$app->t::countModels($class, $filter, ArrayHelper::merge(['checkPermissions' => false], $options));
        if ($count === 0) {
            return;
        }

        $rounds = floor($count / $limit);
        if ($count % $limit === 0) {
            // e.g if 2080 / 10 = 208 rounds then the offset will be 2080 and we won't get any results the first time through. Instead want 207 rounds with 2070 as the offset
            $rounds--; // @todo: Ensure this works as expected
        }
        $modelsYielded = 0;

        // Note: Because of the way you could be modifying entries based on a filter we start from the end and work backwards.
        // e.g if you have a filter of ['accountId' => null] then because of the way the batch operations work you'd be skipping over entries if we started from the front (offset 0) instead we start with the highest offset and work backwards
        // But it's not completely backwards, if 0->100 then with a limit of 3 you'd get something like: 98, 99, 100, 95, 96, 97, 94, 93, 92 ...
        for ($round = $rounds; $round >= 0; $round--) {

            $offset = $round * $limit;
            $models = \Yii::$app->t::getModels($class, $filter, ArrayHelper::merge(['checkPermissions' => false], $options, [
                'limit' => $limit,
                'offset' => $offset,
            ]));

            if (empty($models)) {
                if ($round !== $rounds) {
                    \Yii::warning("yieldModels() Unexpectedly no more entries. Yielded $modelsYielded of $count. In round $round of $rounds of $class");
                }
                \Yii::debug("yieldModels() No more entries. Yielded $modelsYielded of $count. In round $round of $rounds of $class using: " . VarDumper::export(['limit' => $limit, 'offset' => $offset]));

                return;
            }

            foreach ($models as $model) {
                $modelsYielded++;
                yield $model;
            }
        }
        return;
    }

    public $requestCache = null;
}
