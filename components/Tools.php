<?php
namespace mozzler\base\components;

use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;

class Tools extends Component {
	
	public static function app() {
		return \Yii::$app;
	}
	
	public static function load($className, $config=[]) {
		$className = self::getClassName($className);
		
		return \Yii::createObject($className, $config);
	}
	
	public static function renderWidget($widgetName, $config=[], $wrapConfig=true) {
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
	 * @param	string	$template	Twig template to render
	 * @param	array	$data		Data to pass to the template
	 * @param	array	$options	Any template rendering options
	 * @return	string	Returns the template result
	 */
	public static function renderTwig($template, $data=[], $options=[]) {
		$twig = TwigFactory::getEnvironment();
		$twigTemplate = $twig->createTemplate($template);
        $output = $twigTemplate->render($data);

		if (isset($options["recursive"]) && $options["recursive"] === true) {
			$count = 0;
			if (!isset($options["recursiveLimit"]) || !is_int($options["recursiveLimit"])) {
				$options["recursiveLimit"] = 10;
			}

			while (preg_match('/'.$options["recursive"].'/', $output)) {
				$twigTemplate = $twig->createTemplate($output);
                $output = $twigTemplate->render($data);

				$count++;
				
				if ($count > $options["recursiveLimit"]) {
					\Yii::warning("Recursive limit (".$options['recursiveLimit'].") hit in renderTemplate(). Check your template and recursive regex doesn't cause a never-ending loop, or increase the `recursiveLimit` option.");
					break;
				}
			}
		}
		
		return $output;
		
	}
	
	public static function getWidget($widget, $config=[]) {
		$className = self::getClassName($widget);
		ob_start();
        ob_implicit_flush(false);
		$widget = new $className($config);
		ob_get_clean();
		return $widget;
	}
	
	public static function getClassName($className) {
		return '\\'.preg_replace("/\./", "\\\\", $className);
	}
	
	/**
	 * Create an empty model.
	 *
	 * @param	string	$className  Class name of the model to create (eg: `mozzler\auth\user`).
	 * @param	array	$data		Default data to populate the model
	 * @return	\mozzler\base\models\Model	Returns a new model
	 */
	public static function createModel($className, $data=[]) {
		$model = Yii::createObject(self::getClassName($className));
		
		if ($data)
			$model->load($data,"");
		
		$model->scenario = "create";

		return $model;
	}
	
	/**
	 * Get a model from the database
	 */
	public static function getModel($className, $filter=[], $checkPermissions=true) {
		$model = static::createModel($className);
		return $model->findOne($filter, $checkPermissions);
	}

	/**
	 * Get existing models from the database.
	 *
	 * Example usage:
	 * 
	 * ```
	 * // get all models, but limit to 10 results sorted by inserted DESC and ignore RBAC
	 * $models = Tools::getModels('app\models\BlogPost', [], [
	 * 	'limit' => 10
	 * 	'offset' => 0,
	 * 	'orderBy' => ['inserted' => SORT_DESC],
	 * 	'checkPermissions' => false
	 * ]);
	 *
	 * // get models using a filter
	 * $models = Tools::getModels('app\models\BlogPost', [
	 * 	"status" => "draft"
	 * ]);
	 *
	 * @param	string	$className	Class name of the model to get
	 * @param	array	$filter		MongoDB filter to apply to the query
	 * @param	array	$options
	 * @return	array	Returns an array of found models. If none found, returns an empty array.
	 */
	public static function getModels($className, $filter=[], $options=[]) {
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
	 * Send an email.
	 *
	 * Using this method directly will send an email in a blocking manner. Uses twig
	 * template files and email layout template.
	 *
	 * Usage example:
	 *
	 * ```
	 * \Yii::$app->t->sendEmail(
	 * 	["john@company.com" => "John Doe"],
	 * 	"Hello!",
	 * 	"hello.twig",
	 * 	[
	 * 		"messsage" => "hello world"
	 * 	],
	 * 	[
	 * 		"smtpSettings" => [
	 * 			"username" => <username>
	 * 			"password" => <password>
	 * 			"host" => <host>
	 * 			"port" => <port>
	 * 			"encryption" => "ssl"
	 * 		],
	 * 		"from": [
	 * 			"name" => "Michael",
	 * 			"email" => "michael@company.com"
	 * 		],
	 * 		"replyTo": [
	 * 			"name" => "Info",
	 * 			"email" => "info@company.com"
	 * 		]
	 * 	]
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
	 * @param	string	$to			Email recipient(s) in the format `[<email address> => <name>]`
	 * @param	string	$subject	Email subject.
	 * @param	string	$template	Name of template to use for rendering the email (eg: `user/welcome.twig`). Email templates are all prefixed by `emails/`, but this doesn't need to be included when specifying the template name.
	 * @param	array	$data		Data to send to the email template.
	 * @param	array	$config		Config for sending the email such as; setting `replyTo` or `from` which is merged with `$params['config']`
	 */
	public static function sendEmail($to, $subject, $template, $data=[], $config=[]) {
		$mailer = \Yii::$app->mailer;
        $message = $mailer->compose($template, $data)
        	->setTo($to)
			->setSubject($subject);
		
		$config = ArrayHelper::merge(\Yii::$app->params['mozzler.base']['email'], $config);

		if (isset($config['from'])) {
			\Yii::trace(print_r($config['from'],true));
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
        return "#### EXCEPTION ####\nMessage: {$exception->getMessage()}\nCode: {$exception->getCode()}\nTrace\n--------\n{$exception->getTraceAsString()}";
    }
	
	/**
	 * Ensure an ID is a proper MongoDB ID object.
	 *
	 * @pararm	string	$id		ID to convert to a MongoDB ID object
	 * @return	\MongoId	Returns a MongoID object
	 */
	public static function ensureId($id) {
		return new \MongoDB\BSON\ObjectId($id);
	}
	
	public static function trace($message, $location=null, $dump=false)
	{
    	\Yii::trace($dump ? print_r($message,true) : $message, $location);
	}
	
	public static function warning($message, $location=null, $dump=false)
	{
    	\Yii::warning($dump ? print_r($message,true) : $message, $location);
	}
	
	public static function info($message, $location=null, $dump=false)
	{
    	\Yii::info($dump ? print_r($message,true) : $message, $location);
	}
	
	public static function error($message, $location=null, $dump=false)
	{
    	\Yii::error($dump ? print_r($message,true) : $message, $location);
	}
}