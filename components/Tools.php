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
	 * @return	Basemodel	Returns a new model
	 */
	public static function createModel($className, $data=[]) {
		$model = Yii::createObject(self::getClassName($className));
		
		if ($data)
			$model->load($data,"");
		
		$model->scenario = "create";

		return $model;
	}
	
	public static function getModel($className, $filter=[], $checkPermissions=true) {
		$model = static::createModel($className);
		return $model->findOne($filter, $checkPermissions);
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
	 * @param	array	$config		Config for sending the email such as; setting `replyTo`, `from`, or custom SMTP settings.
	 */
	public static function sendEmail($to, $subject, $template, $data=[], $config=[]) {
		$mailer = \Yii::$app->mailer;
		$canSetFromAddress = false;
		
        if (isset($config['smtpSettings'])) {
        	$transport = $mailer->getTransport();
        	$smtpSettings = $config['smtpSettings'];
        	$smtpSettings['class'] = 'Swift_SmtpTransport';
        	$smtpSettings['plugins'] = [
	        	['class' => 'Openbuildings\Swiftmailer\CssInlinerPlugin']
        	];
        	$canSetFromAddress = true;
			$mailer->setTransport($smtpSettings);
        }
        
        // merge config with application defaults
        $defaultConfig = self::getConfig("rappsio.application.emails");
        $mergedConfig = ArrayHelper::merge($defaultConfig, $config);
        
        if (isset($mergedConfig['messageConfig'])) {
	        $oldMessageConfig = $mailer->messageConfig;
	        $mailer->messageConfig = $mergedConfig['messageConfig'];
        }
        
        $message = $mailer->compose($template, $data)
        	->setTo($to)
			->setSubject($subject);
		
		if (isset($mergedConfig['replyTo']['email']) && isset($mergedConfig['replyTo']['name'])) {
			$message->setReplyTo([$mergedConfig['replyTo']['email'] => $mergedConfig['replyTo']['name']]);
		}
        
        $useDefaultFrom = true;
        if ($canSetFromAddress) {
        	// user has custom smtpSettings, so can specify their own from address
        	if (isset($config['from'])) {
        		// use from address from config
        		$from = $config['from'];
        		if (isset($from['email']) && isset($from['name'])) {
			        $message->setFrom([$from['email'] => $from['name']]);
					$useDefaultFrom = false;
				}
				else {
					\Yii::warning("Invalid from address supplied: ".print_r($from,true));
				}
        	}
	        else {
		        $from = isset($defaultConfig['from']) ? $defaultConfig['from'] : [];
		        if ($from) {
		        	if (isset($from['email']) && isset($from['name'])) {
				        $message->setFrom([$from['email'] => $from['name']]);
						$useDefaultFrom = false;
					}
					else {
						\Yii::warning("Invalid from address supplied: ".print_r($from,true));
					}
		        }
	        }
        }
        
        if ($useDefaultFrom) {
        	// user hasn't (or can't) specify a custom from address
        	// so use the rappsio application default
        	$from = \Yii::$app->params['emailDefaults']['from'];
        	$message->setFrom([$from['email'] => $from['name']]);
        } 
        
        $result = $message->send();
        \Yii::$app->getModule("rappsio")->logUsage("email");
        
        if (isset($config['smtpSettings'])) {
        	// set transport back to original value
        	$mailer->setTransport($transport);
        }
        
        if (isset($config['messageConfig'])) {
        	// set message config back to original value
	        $mailer->messageConfig = $oldMessageConfig;
        }
        
        // return result
        return $result;
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