## Installation

Add the following to your `config/params.php`:

```
    'mozzler.base' => [
        'email' => [
            'from' => ['noreply@acme.com' => 'No reply'],
            //'replyTo' => ['support@acme.com' => 'Support'],
        ]
    ]
```

Copy the `views/layouts/emails` directory across to `views/layouts` in your application.



Script / Cron Principles
------------------------

- Always write a script
- Link a cron job to run a script
- If we need the ability run via command, create a command that runs the script




Notes on using a Widget
========================

You Need a .twig file for a Widget, should have a .php file and can have a .css and .js file.

If you don't have a .twig file you are likely to get a weird PHP Compile error like: `Cannot declare class mozzler\base\widgets\model\common\ToggleFieldVisibility, because the name is already in use`

The minimum for the twig would be

    <{{ widget.tag }} id="{{ widget.id }}" {{ html.renderTagAttributes(widget.options) }}></{{ widget.tag }}>

You can put custom PHP into the code method e.g

    public function code($templatify = false) { 
        $config = $this->config();
        /** @var Model $model */
        $model = $config['model'];
        
        $this->outputJsData([
            // .. Info here
        ]);
    }

Have a look at the 

The JS file contents will be included only once.
Use the widget name and .ready or an alternative to include it in a different spot.

JS types:

    'ready' => WebView::POS_READY,
    'begin' => WebView::POS_BEGIN,
    'end' => WebView::POS_END,
    'head' => WebView::POS_HEAD,
    'load' => WebView::POS_LOAD


Example showing how to use widgets in JS

    $('.widget-option-class').each(function() { // Put in the CSS class as defined in the defaultConfig method's ['options']['class'] 
        const id = $(this).attr('id');
        const widgetData = m.widgets[id];
        console.log(widgetData);
        
        // Custom JS Widget logic here... Run for each instance of the widget on the page, but can have custom per widget data
    });


