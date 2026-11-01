# Lead

Send a lead to a Flamix "Website Integration" app, which creates it in Bitrix24. API reference: https://lead.app.flamix.solutions/docs

## Send

```php
use Flamix\Bitrix24\Lead;

try {
    $response = Lead::getInstance()
        ->auth('portal.bitrix24.com', 'YOUR.API.KEY') // the portal domain and the app's API key
        ->send(['FIELDS' => ['NAME' => 'Roman', 'PHONE' => '+380...']]);
} catch (\Exception $e) {
    // 'Empty DOMAIN!', 'Empty api_token!', 'Curl error: …' or 'Bad JSON format!'
}
```

`send()` returns the decoded JSON of the app. The payload is posted form-encoded (`FIELDS[NAME]=Roman`).

## Another app or action

```php
Lead::getInstance()
    ->changeSubDomain('leadframework') // https://leadframework.app.flamix.solutions/api/v1/
    ->setDomain('portal.bitrix24.com')
    ->setToken('YOUR.API.KEY')
    ->send(['STATUS' => 'paid', 'ORDER_ID' => 7, 'HOSTNAME' => 'shop.example.com'], 'status/change'); // default action: 'lead/add'
```

| Subdomain | App |
| --- | --- |
| `lead` (default) | Website Integration |
| `leadframework` | Integration with frameworks: Laravel, Symfony, Zend and Yii |

## Auto analytics

Unless disabled, `send()` fills every key below that the data left empty, from the current request.

| Key | Source |
| --- | --- |
| `UF_CRM_FX_CONVERSION` | `Conversion::getPreparedUID()` of `flamix/conversions`: `_ym_uid;_ga|GCLID.…;_fbp;_ttp|TTCLID.…` |
| `UTM` | the `utm` cookie or `utm_*` of the current query (`xsuchy09/utm-cookie`) |
| `TRACE` | `Trace::get(true)`, see [tracking.md](tracking.md) |
| `FIELDS[HOSTNAME]` | `SERVER_NAME` without `www.` |
| `FIELDS[REFERER]` | the `HTTP_REFERER` host without `www.` |
| `FIELDS[USER_IP]` | `REMOTE_ADDR`, else `HTTP_CF_CONNECTING_IP` |
| `FIELDS[GA_UID]` | `_ga` cookie |
| `FIELDS[FB_UID]` | `_fbp` cookie |
| `FIELDS[YM_UID]` | `_ym_uid` cookie |
| `FIELDS[TT_UID]` | `_ttp` cookie |
| `FIELDS[ROISTAT_VISIT_ID]` | `roistat_visit` cookie |

```php
Lead::getInstance()->auth($domain, $token)
    ->send(['FIELDS' => ['NAME' => 'Roman', 'GA_UID' => $fromForm]]); // GA_UID from the form wins over the cookie

// Outside the visitor's request (a queue job, a webhook): send only what you pass.
Lead::getInstance()->auth($domain, $token)->disableAutoAnalytics()->send($data);
```

The matching user fields must exist in Bitrix24, otherwise the values are ignored.

## Inspect the payload

```php
$payload = Lead::getInstance()->auth($domain, $token)->prepareData(['FIELDS' => ['NAME' => 'Roman']]);
// ['FIELDS' => [...], 'DOMAIN' => 'portal.bitrix24.com', 'api_token' => '…', 'UF_CRM_FX_CONVERSION' => …, 'UTM' => …, 'TRACE' => …]
```
