# Tracking

Collect UTM, click ids and visited pages while the visitor browses, so `Lead::send()` can attach them. Call before any output: these set cookies.

## Every page

```php
\Flamix\Bitrix24\Trace::init('Pricing'); // SmartUTM + click ids + this page
```

## SmartUTM only

No UTM in the visit, but a referer from another site: the referer host becomes `utm_source`.

```php
\Flamix\Bitrix24\SmartUTM::init(); // https://www.google.com/... → utm_source=google.com
```

## Pages only

```php
use Flamix\Bitrix24\Trace;

Trace::setPage('Pricing');                                  // URL of the current request
Trace::setPage('Thank you', 'https://shop.example.com/ok'); // explicit URL
Trace::setPage(null);                                       // records nothing

Trace::getPages(); // [[url, timestamp, name], ...] newest first, or false
```

Pages live in the native PHP session (`$_SESSION['FLAMIX_PAGES']`).

## Click ids

`Trace::init()` copies `gclid` and `ttclid` from the query string into 7-day cookies, which `flamix/conversions` (`^1.1` for TikTok) folds into `UF_CRM_FX_CONVERSION`.

```php
Trace::setGCLID($gclid);   // the same, by hand
Trace::setTTCLID($ttclid);
```

## What is sent

```php
Trace::get();     // false when no page was recorded
Trace::get(true); // the JSON that Lead::send() puts in TRACE
```

```json
{
    "url": "https://shop.example.com/pricing",
    "device": {"isMobile": false},
    "tags": {"ts": 1767225600, "list": {"utm_campaign": null, "utm_medium": null, "utm_source": "google.com", "utm_term": null, "utm_content": null}},
    "client": {"gaId": "111.222", "yaId": "444", "ttId": "tt-555"},
    "pages": {"list": [["https://shop.example.com/pricing", 1767225600, "Pricing"]], "gid": "b24_crm_guest_id cookie or null"}
}
```

`gaId` is the client id part of `_ga`: `GA1.1.111.222` gives `111.222`.
