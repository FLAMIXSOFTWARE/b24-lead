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

## Auto analytics

On `send()` the SDK enriches the lead with analytics data collected from the
visitor's cookies (unless disabled with `disableAutoAnalytics()`):

| Field | Source cookie | Platform |
| --- | --- | --- |
| `GA_UID` | `_ga` | Google Analytics |
| `FB_UID` | `_fbp` | Facebook Pixel |
| `YM_UID` | `_ym_uid` | Yandex Metrika |
| `TT_UID` | `_ttp` | TikTok Pixel |
| `ROISTAT_VISIT_ID` | `roistat_visit` | Roistat |

> Make sure the matching user fields exist on the Bitrix24 side, otherwise the
> values are ignored.

### Click ids

`Trace::init()` also captures ad click ids from the URL and stores them in a
7-day cookie, so they survive until the lead is submitted:

* `gclid` — Google Ads click id
* `ttclid` — TikTok click id

These click ids are folded into `UF_CRM_FX_CONVERSION` by `flamix/conversions`
(requires `^1.1` for TikTok support).