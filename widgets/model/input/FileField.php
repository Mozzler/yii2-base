<?php

namespace mozzler\base\widgets\model\input;

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
        \Yii::debug("The filefield config is: " . json_encode($config));
//        $config['widgetConfig'] = ArrayHelper::merge($config['widgetConfig'], ['class' => 'mozzler-filepond']);

        // JS
        $view = \Yii::$app->controller->getView();
        $view->registerJsFile('https://unpkg.com/filepond/dist/filepond.min.js', ['position' => WebView::POS_END, 'depends' => ['yii\web\JqueryAsset']], 'filepond-main');
        $view->registerJsFile('https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.js', ['position' => WebView::POS_END, 'depends' => ['yii\web\JqueryAsset']], 'filepond-plugin-imagepreview');
        $view->registerJsFile('https://unpkg.com/jquery-filepond/filepond.jquery.js', ['position' => WebView::POS_END, 'depends' => ['yii\web\JqueryAsset']], 'filepond-jquery');

        // CSS
        $view->registerCssFile('https://unpkg.com/filepond/dist/filepond.css', ['position' => WebView::POS_HEAD], 'filepond-styling');
        $view->registerCssFile('https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css', ['position' => WebView::POS_HEAD], 'filepond-styling-plugin-imagepreview');


        //<link href="https://unpkg.com/filepond/dist/filepond.css" rel="stylesheet">
        //<link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css" rel="stylesheet">

        // Include FilePond library as per https://github.com/pqina/jquery-filepond
        // <script src="https://unpkg.com/filepond/dist/filepond.min.js"></script>

        // Include FilePond plugins
        // <script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.js"></script>

        // Include FilePond jQuery adapter -->
        // <script src="https://unpkg.com/jquery-filepond/filepond.jquery.js"></script>

        $view->registerJs('
    if (typeof FilePond === "undefined") {
        console.error("ERROR: FilePond isn\'t installed");
    } else {
        console.debug("Adding FilePond file upload support");
        FilePond.setOptions({
            allowDrop: true,
            allowReplace: true,
            instantUpload: true,
            allowMultiple: false,
            server: {
                process: \'/file/create\',
                revert: \'/file/delete\', // Allow deleting uploaded files
                restore: null,
                fetch: null,
            },
//            onprocessfile: function (error, file) {
//                // Get the associated hidden input
//                // @warning: This process doesn\'t allow multiple file uploads on the same page as it\'ll only get the first filepond... not sure how to select the one associated with this specific instance.
//                $(\'.filepond--root\').siblings(\'input[type=hidden]\').val(file.serverId);
//            }
        });

        // First register any plugins
        $.fn.filepond.registerPlugin(FilePondPluginImagePreview);

        var $mozzlerFilePond = $(\'input[type=file]\');
        // Turn input element into a filepond
        $mozzlerFilePond.filepond();

        // Listen for addfile event
        $mozzlerFilePond.on(\'FilePond:onprocessfile\', function (e) {
            console.log(\'file processed with event\', e);
        });
    }
        ', WebView::POS_READY, 'filepond-setup');

        $field = $config['form']->field($config['model'], $config['attribute']);
//        \Yii::warning("Returning the activeHiddenInput fileInput: " . $field->activeHiddenInput($config['widgetConfig']));
        return $field->fileInput($config['widgetConfig']);
    }

}
