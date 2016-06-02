Yii2 extension for Meest Express API
==================================

 > IMPORTANT! Extension is under development

### Installation via Composer

Run a composer command to install this extension:

> composer require webkadabra/yii2-meestexpress

### Setup

Setup your yii2 application configuration:
```php
'components' => [
    'novaposhta' => [
        'class' => 'webkadabra\meestexpress\Api',
        'api_login' => 'specify your api login',
        'api_pass' => 'specify your api pass',
    ]
]
```

More documentation and examples are coming...