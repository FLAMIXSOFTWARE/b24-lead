<?php

namespace Flamix\Bitrix24;

use Flamix\Conversions\Conversion;
use UtmCookie\UtmCookie;

/** Collects visited pages (native session), device, UTM and analytics ids into the Bitrix24 tracer format. */
class Trace
{
    /** Call on every page before output: sets cookies. */
    public static function init(?string $pageName = null, ?string $url = null): void
    {
        SmartUTM::init();

        if (isset($_GET['gclid'])) {
            self::setGCLID($_GET['gclid']);
        }

        if (isset($_GET['ttclid'])) {
            self::setTTCLID($_GET['ttclid']);
        }

        self::setPage($pageName, $url);
    }

    /**
     * Record a visited page in the session; without a name nothing is recorded.
     *
     * @return false|void
     */
    public static function setPage(?string $pageName = null, ?string $url = null)
    {
        if (! $pageName) {
            return false;
        }

        if (session_status() === PHP_SESSION_NONE) {
            // @: warns once headers are sent, and a page must not break on it.
            @session_start();
        }

        $url = $url ?: self::getCurrentURL();
        $time = time();

        if (! isset($_SESSION['FLAMIX_PAGES'])) {
            $_SESSION['FLAMIX_PAGES'] = [];
        }

        $_SESSION['FLAMIX_PAGES'][$time] = [$url, $time, $pageName];
    }

    /** @return false|list<array{0: string, 1: int, 2: string}> pages as [url, timestamp, name], newest first */
    public static function getPages()
    {
        if (! empty($_SESSION['FLAMIX_PAGES'])) {
            return array_reverse($_SESSION['FLAMIX_PAGES']);
        }

        return false;
    }

    public static function getCurrentURL(): string
    {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

        return $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /** The tracer base: url, device, UTM tags and client ids. */
    public static function getBase(): array
    {
        $trace = [
            'url' => self::getCurrentURL(),
            'device' => [
                'isMobile' => static::isMobile(),
            ],
            'tags' => ['ts' => time()],
        ];

        $utm = UtmCookie::get();
        $trace['tags']['list'] = ! empty($utm) ? $utm : null;

        $client = Conversion::getFromCookie();

        $trace['client']['gaId'] = ! empty($client['_ga']) ? self::parseClientID($client['_ga']) : null;
        $trace['client']['yaId'] = ! empty($client['_ym_uid']) ? $client['_ym_uid'] : null;
        $trace['client']['ttId'] = ! empty($_COOKIE['_ttp']) ? $_COOKIE['_ttp'] : null;

        return $trace;
    }

    /** @return false|array|string the tracer data (JSON when $json), false when no page was recorded */
    public static function get(bool $json = false)
    {
        $pages = self::getPages();
        if (! $pages) {
            return false;
        }

        $base = self::getBase();
        if (empty($base)) {
            return false;
        }

        $base['pages'] = ['list' => $pages];

        $gid = self::getGID();
        $base['pages']['gid'] = $gid ?: null;

        if ($json) {
            return json_encode($base, JSON_UNESCAPED_UNICODE);
        }

        return $base;
    }

    /** The Bitrix24 guest id cookie. */
    public static function getGID(): ?string
    {
        return $_COOKIE['b24_crm_guest_id'] ?? null;
    }

    /** Save the Google Ads click id to a 7-day cookie. */
    public static function setGCLID(string $gclid): bool
    {
        return setcookie('gclid', $gclid, time() + 604800, '/');
    }

    /** Save the TikTok click id to a 7-day cookie. */
    public static function setTTCLID(string $ttclid): bool
    {
        return setcookie('ttclid', $ttclid, time() + 604800, '/');
    }

    /** The client id from a _ga cookie: GA1.1.123.456 gives 123.456. */
    private static function parseClientID(string $cid): ?string
    {
        preg_match("/(?:GA\d\.\d\.|)(\d+\.\d+)/", $cid, $matches);

        return $matches[1] ?? null;
    }

    private static function isMobile(): bool
    {
        return (bool) preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $_SERVER['HTTP_USER_AGENT'] ?? '');
    }
}
