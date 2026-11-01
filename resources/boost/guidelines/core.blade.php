@php /** @var \Laravel\Boost\Install\GuidelineAssist $assist */ @endphp
# Flamix B24 Lead

Plain-PHP client that posts a lead to a Flamix Bitrix24 website-integration app (`https://<subdomain>.app.flamix.solutions/api/v1/<action>`), plus visitor tracking for it. It does not call the Bitrix24 REST API and has no Laravel service provider, config or facade.

- Do not hand-roll an HTTP call to `*.app.flamix.solutions`: `Lead::getInstance()->changeSubDomain(...)->auth($domain, $token)->send($data, 'lead/add')`.
- `Lead` is a static singleton: domain, token, subdomain and the analytics switch persist for the whole PHP process. In a queue worker set all of them on every job.
- `send()` adds analytics from the current request (`$_COOKIE`, `$_SERVER`, `$_SESSION`) unless `disableAutoAnalytics()` is called. In jobs, commands and webhooks disable it and pass the visitor data yourself.
- `getInstance()` and `Trace::setPage()` use the native PHP session, not Laravel's; `Trace` and `SmartUTM` call `setcookie()`, not Laravel's cookie jar.
- Errors are plain `\Exception`s: missing domain or token, a cURL error, a non-JSON body. An app-level error comes back inside the decoded response, not as an exception.
- Full docs: `vendor/flamix/b24-lead/docs/*.md` (lead, tracking).

Detail lives in the skill: `b24-lead-send`.
