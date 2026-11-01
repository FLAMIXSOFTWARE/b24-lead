![Screenshot](img/header.jpg)

# B24 Lead

PHP SDK for the Flamix Bitrix24 website integrations: send a lead with UTM, click ids and visited pages. https://flamix.solutions/bitrix24/integrations/site/

## Install

```bash
composer require flamix/b24-lead
```

```php
\Flamix\Bitrix24\Trace::init('Page title'); // every page, before output

\Flamix\Bitrix24\Lead::getInstance()->auth('portal.bitrix24.com', 'YOUR.API.KEY')->send(['FIELDS' => ['NAME' => 'Roman']]);
```

## Documentation

See [docs/](docs/) for full documentation.

AI: Laravel Boost guideline and skills in resources/boost/.

- [Lead](docs/lead.md) — auth, apps and actions, auto analytics, the payload
- [Tracking](docs/tracking.md) — `Trace`, `SmartUTM`, click ids, what goes into `TRACE`
