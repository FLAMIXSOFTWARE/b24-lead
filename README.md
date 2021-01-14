![Screenshot](img/header.jpg)

Site - https://flamix.solutions/bitrix24/lead_add.php

## Install

```php
composer require flamix/b24-lead
```

## Usage

```php
try {
        \Flamix\Bitrix24\Lead::getInstance()->setDomain('YOUR.BITRIX24.COM')->setToken('YOUR.API.KEY')->send(['field' => 'value']);
    } catch (\Exception $e) {
        $e->getMessage();
    }
```