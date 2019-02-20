## Installation

Add the following to you `config/params.php`:

```
    'mozzler.base' => [
        'email' => [
            'from' => ['noreply@acme.com' => 'No reply'],
            //'replyTo' => ['support@acme.com' => 'Support'],
        ]
    ]
```

Copy the `views/layouts/emails` directory across to `views/layouts` in your application.