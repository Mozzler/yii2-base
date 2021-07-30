<?php

namespace mozzler\base\widgets\model\input;

use mozzler\base\exceptions\BaseException;
use mozzler\base\models\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use yii\web\View as WebView;

class FilesField extends BaseField
{

    public function defaultConfig()
    {
        return ArrayHelper::merge(parent::defaultConfig(), [
            'widgetConfig' => [
                'options' => [
                    'class' => 'mozzler-files-field-wrapper', // This is important otherwise the JS doesn't catch up .mozzler-files-field-wrapper
                    'data-attribute' => '{{ widget.attribute|escape(\'html_attr\') }}',
                    'data-' => '',
                ],
            ]
        ]);
    }

    /**
     * @param false $templatify
     * @return array
     *
     * As per https://www.yiiframework.com/doc/guide/2.0/en/input-file-upload
     * But we are allowing an array of files
     */
    public function code($templatify = true)
    {
        $config = $this->config(true);

        /** @var Model $model */
        $model = $config['model'];
        $attribute = $config['attribute'];


        // JS
        $view = \Yii::$app->controller->getView();
        $view->registerJsFile('https://unpkg.com/filepond/dist/filepond.min.js', ['position' => WebView::POS_END, 'depends' => ['yii\web\JqueryAsset']], 'filepond-main');
        $view->registerJsFile('https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.js', ['position' => WebView::POS_END, 'depends' => ['yii\web\JqueryAsset']], 'filepond-plugin-imagepreview');
        $view->registerJsFile('https://unpkg.com/jquery-filepond/filepond.jquery.js', ['position' => WebView::POS_END, 'depends' => ['yii\web\JqueryAsset']], 'filepond-jquery');

        // CSS
        $view->registerCssFile('https://unpkg.com/filepond/dist/filepond.css', ['position' => WebView::POS_HEAD], 'filepond-styling');
        $view->registerCssFile('https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css', ['position' => WebView::POS_HEAD], 'filepond-styling-plugin-imagepreview');
//
//        $view->registerJs('
//
//
//if (typeof FilePond === "undefined") {
//    console.error("ERROR: FilePond isn\'t installed");
//} else {
//	console.log("Adding FilePond file upload support");
//	FilePond.setOptions({
//	    allowDrop: true,
//	    allowReplace: true,
//	    instantUpload: true,
//	    allowMultiple: true,
//	    server: {
//	        process: \'/file/create\',
//	        revert: \'/file/delete\', // Allow deleting uploaded files
//	        load: \'/file/download?filepond=load&id=\', // Allow viewing previously uploaded files
//	        restore: \'/file/download?filepond=restore&id=\',
//	        fetch: null,
//	    },
//	});
//    // Allow image previews
//	$.fn.filepond.registerPlugin(FilePondPluginImagePreview);
//}
//', WebView::POS_READY, 'filepond-init'); // Also used for the Files field


        // -- For General file inputs
        $view->registerJs('


if (typeof FilePond !== "undefined" && $(".mozzler-files-field-wrapper").length > 0) {

    console.debug("Files Fields: ", $(\'.mozzler-files-field\').length);
	// ----------------------------------
	//  Add the Filepond FILES Add File Hook 
	// ----------------------------------
	// <button class="mozzler-files-field-delete-file-upload btn btn-warning" data-files-id="">x</button><input type="file" />
	
//	var $mozzlerFilesArray = $(".mozzler-files-field-wrapper");
//	// Turn input element into a filepond
//	var filePondArrays = [];
//	$mozzlerFilesArray.each(function(index, element) {

//	    console.log( "element: " , element);
//	    console.log( "index: " , index);
//	    console.log($(element).find(".mozzler-files-field-add-file") ); 

        
	    
//	    if (element.value) {
//	        console.log("The element value is: ", element.value);
//	    }
//	    });
//	if (element.value) {
//	 filePond = FilePond.create(element, {
//	 	\'files\': [
//	        {
//	            // The server file reference
//	            source: element.value,
//	            // Set type to indicate an already uploaded file
//	            options: {
//	                type: \'limbo\'
//	            }
//	        }
//	    ]
//	  });
//
//	} else {
//	 filePond = FilePond.create(element);
//	}
//	filePonds.push({\'element\': element, \'filePond\': filePond });
//	});

//	window.mozzler_filePondArrays = filePondArrays; // Make global var so devs can make their own changes if needed
}


', WebView::POS_READY, 'filepond-files-setup');

//        /** @var \yii\widgets\ActiveField $field */
//        $field = $config['form']->f ield($config['model'], $config['attribute'], ArrayHelper::merge($config['widgetConfig'], ['inputOptions' => ['value' => (string)$model->$attribute]])); // Ensure the value is saved as a normal string not oid reference
//        return $field->hiddenInput(ArrayHelper::merge($config['widgetConfig'], ['class' => 'mozzler-filepond-fileinput'])); // 'mozzler-filepond-fileinput' is what we'll use for triggering the filepond uploader
//        return parent::run();

        return $config;
    }

}
