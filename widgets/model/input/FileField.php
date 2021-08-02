<?php

namespace mozzler\base\widgets\model\input;

use mozzler\base\models\Model;
use yii\helpers\ArrayHelper;
use yii\web\View as WebView;

class FileField extends BaseField
{
    /**
     * @return string
     *
     * As per https://www.yiiframework.com/doc/guide/2.0/en/input-file-upload
     */
    public function run()
    {
        $config = $this->config();

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

        $view->registerJs('

document.addEventListener(\'FilePond:loaded\', function (e) {
    console.log("FilePond:loaded"); // Doesn\'t seem to fire?
});

if (typeof FilePond === "undefined") {
    console.error("ERROR: FilePond isn\'t installed"); // This won\'t be called because the event won\'t fire 
} else {
    console.log("Adding FilePond file upload support");
    FilePond.setOptions({
        allowDrop: true,
        allowReplace: true,
        instantUpload: true,
        allowMultiple: false,
        server: {
            process: \'/file/create\',
            revert: \'/file/delete\', // Allow deleting uploaded files
            load: \'/file/download?filepond=load&id=\', // Allow viewing previously uploaded files
            restore: \'/file/download?filepond=restore&id=\',
            fetch: null,
        },
    });

    // Allow image previews
    $.fn.filepond.registerPlugin(FilePondPluginImagePreview);

    // ----------------------------------
    //  Instantiate Filepond entries
    // ----------------------------------
    var $mozzlerFilePond = $(\'input.mozzler-filepond-fileinput\');
    // Turn input element into a filepond
    var filePonds = [];
    $mozzlerFilePond.each(function(index, element) {

    if (element.value) {
     filePond = FilePond.create(element, {
        \'files\': [
            {
                // The server file reference
                source: element.value,
                // Set type to indicate an already uploaded file
                options: {
                    type: \'limbo\'
                }
            }
        ]
      });

    } else {
     filePond = FilePond.create(element);
    }
    filePonds.push({\'element\': element, \'filePond\': filePond });
    });

    window.mozzler_filePonds = filePonds; // Make global var so devs can make their own changes if needed
}


', WebView::POS_READY, 'filepond-setup');

        /** @var \yii\widgets\ActiveField $field */
        $field = $config['form']->field($config['model'], $config['attribute'], ArrayHelper::merge($config['widgetConfig'], ['inputOptions' => ['value' => (string)$model->$attribute]])); // Ensure the value is saved as a normal string not oid reference
        return $field->hiddenInput(ArrayHelper::merge($config['widgetConfig'], ['class' => 'mozzler-filepond-fileinput'])); // 'mozzler-filepond-fileinput' is what we'll use for triggering the filepond uploader
    }

}
