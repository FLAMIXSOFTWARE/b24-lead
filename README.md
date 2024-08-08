![Screenshot](img/header.jpg)

Site - https://flamix.solutions/bitrix24/integrations/site/
Docs with API and examples - https://lead.app.flamix.solutions/docs

## Install

```php
composer require flamix/b24-lead
```

## Usage

```php
try {
    //If need change APP use changeSubDomain('wpapp') method
    \Flamix\Bitrix24\Lead::getInstance()->auth('YOUR.BITRIX24.COM', 'YOUR.API.KEY')->send(['FIELDS' => ['name' => 'Roman']]);
} catch (\Exception $e) {
    $e->getMessage();
}
```

## Switch plugin

This SDK can works with many all our "Website Integration". For default its work with general app - Site integrations. If you want switch to another module, please, youse method change changeSubDomain(). For example, if you installed "Integration with frameworks: Laravel, Symfony, Zend and Yii":

```php
try {
    //If need change APP use changeSubDomain() method
    \Flamix\Bitrix24\Lead::getInstance()->changeSubDomain('leadframework')->auth('YOUR.BITRIX24.COM', 'YOUR.API.KEY')->send(['FIELDS' => ['name' => 'Roman']]);
} catch (\Exception $e) {
    $e->getMessage();
}
```

#### Module domains:

* lead - Website Integration (Default);
* leadframework - Integration with frameworks: Laravel, Symfony, Zend and Yii

## SmartUTM

When we didn't have UTM source, but have REFERER (for example, facebook.com) - we can set UTM_SOURCE=facebook.com
Put this code in header sections in every page.

```php
\Flamix\Bitrix24\SmartUTM::init();
```

## Trace

Trace save visited pages and user devise.

```php
\Flamix\Bitrix24\Trace::setPage('Your page title');
```

## Trace & SmartUTM

```php
\Flamix\Bitrix24\Trace::init('Your page title');
```