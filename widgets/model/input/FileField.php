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
        $config['widgetConfig'] = ArrayHelper::merge($config['widgetConfig'], ['class' => 'mozzler-filepond', 'name' => 'filepond']);

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
        FilePond.setOptions({
            allowDrop: true,
            allowReplace: true,
            instantUpload: true,
            allowMultiple: false,
            server: {
                // url: \'http://192.168.33.10\',
                process: \'/file/create\',
                revert: \'/flie/delete\',
                // restore: \'/file/update?id=\',
                // fetch: \'./file/list\'
            }
        });

        // First register any plugins
        $.fn.filepond.registerPlugin(FilePondPluginImagePreview);

        // Turn input element into a pond
        $(\'input.mozzler-filepond\').filepond();

        // Listen for addfile event
        $(\'input.mozzler-filepond\').on(\'FilePond:addfile\', function(e) {
            console.log(\'file added event\', e);
        });
        ', WebView::POS_READY, 'filepond-setup');

        $field = $config['form']->field($config['model'], $config['attribute']);
        return $field->fileInput($config['widgetConfig']);
    }

}
