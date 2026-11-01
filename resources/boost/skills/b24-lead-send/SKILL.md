---
name: b24-lead-send
description: "Flamix b24-lead sending and tracking. Use when calling Lead::send(), auth(), changeSubDomain(), disableAutoAnalytics(), prepareData(), Trace::init(), Trace::setPage() or SmartUTM::init(), or wiring a lead form, a queue job or a webhook to a Flamix website-integration app. Also use for the symptoms: a lead from a job carries another job's portal or token, HOSTNAME/USER_IP/TRACE empty or wrong, USER_IP is a Cloudflare address, a page missing from the trace, the trace URL is http on an https site, 'Empty DOMAIN!', 'Bad JSON format!', a PHPSESSID cookie next to the Laravel session. Skip for sending conversions by themselves (flamix/conversions) and for the Bitrix24 REST API."
---

# B24 Lead: sending and tracking

Full docs: vendor/flamix/b24-lead/docs/lead.md and docs/tracking.md. Below is what the docs omit.

## A queue job

The previous job's portal, token, subdomain and `disableAutoAnalytics()` are still set, so set all four every time.

```php
Lead::getInstance()
    ->changeSubDomain($subDomain)
    ->setDomain($domain)
    ->setToken($token)
    ->disableAutoAnalytics();

$response = Lead::send($data); // cURL: 10 s connect, 30 s total
```

The visitor data has to be collected in the visitor's request (`Trace::get(true)`, the cookies, the IP) and passed in `$data`. A key you set wins for analytics keys; `DOMAIN` and `api_token` always come from `auth()`.

`TRACE` is `false` when no page was recorded, and `UTM` is five keys that are null without a `utm` cookie.

## Session and cookies

- In Laravel the native session is a second one beside Laravel's, with its own PHPSESSID cookie.
- After output has started `setcookie()` only warns, and the UTM or click id is lost.

## Known wrong values

- Two `Trace::setPage()` calls in the same second keep only the last page: pages are keyed by `time()`.
- `USER_IP` is `REMOTE_ADDR` first, so behind Cloudflare it is the edge address. Pass `FIELDS[USER_IP]` yourself.
- The trace URL is https only when `$_SERVER['HTTPS'] === 'on'`; behind a TLS-terminating proxy it is http.
- `HOSTNAME` and `REFERER` drop every `www.` in the host, not only the leading one.
