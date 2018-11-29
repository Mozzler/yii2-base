
Configuration
-------------

Customising User
----------------

URL Endpoints
-------------

## Creating a new endpoint

Create a new Controller (if it doesn't already exist) and override the `actions()` method to define a new endpoint.

This example creates a `Blog` controller for a `Blog` model with a new endpoint `downloadPdf`.

```
<?php
namespace app\controllers;

//use mozzler\api\controllers\ActiveController as BaseController; -- for a model API controller
//use ? -- for non-model API controller
//use mozzler\web\controllers\ModelController as BaseController; -- for a model web controller
//use mozzler\web\controllers\BaseController as BaseController; -- for a non-model web controller

use yii\helpers\ArrayHelper;

class BlogController extends ActiveController {

	public $modelClass = 'app\models\Blog';
	
	public function actions() {
		$actions = parent::actions();
		return ArrayHelper::merge($actions, [
			'downloadPdf' => [
	            'class' => 'app\actions\BlogDownloadPdfAction'
	        ]
	    ]);
	}
	
}

```

Next, create the file `actions/BlogDownloadPdfAction.php`:

```
<?php
namespace app\components;

use yii\base\Action;

class BlogDownloadPdfAction extends Action
{
    public function run()
    {
	    // Generate a PDF file
	    // Return a response object for downloading the PDF
    }
}
```

This structure allows easily unit testing individual actions outside of the controller context. It also makes it simple to connect actions to multiple controllers at once.

Database Models
---------------

## Creating a new model

Page Templates
--------------

Business Logic
--------------

API
---