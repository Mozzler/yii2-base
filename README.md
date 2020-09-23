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

