<?php

namespace Flamix\Bitrix24;

use Flamix\Conversions\Conversion;
use UtmCookie\UtmCookie;

/**
 * Trace for Bitrix24.
 *
 * Collects visited pages, device and client identifiers so the visitor
 * journey can be reconstructed on the Bitrix24 side.
 *
 * @see https://lead.app.flamix.solutions/docs
 */
class Trace
{
    /**
     * Start trace.
     *
     * @param  string|null  $pageName
     * @param  string|null  $url
     * @return void
     */
    public static function init(?string $pageName = null, ?string $url = null): void
    {
        // Init SmartUTM.
        SmartUTM::init();

        // Save GCLID from URL.
        if (isset($_GET['gclid'])) {
            self::setGCLID($_GET['gclid']);
        }

        // Save TTCLID (TikTok click id) from URL.
        if (isset($_GET['ttclid'])) {
            self::setTTCLID($_GET['ttclid']);
        }

        self::setPage($pageName, $url);
    }

    /**
     * Set visited pages.
     *
     * @param  string|null  $pageName
     * @param  string|null  $url
     * @return false|void
     */
    public static function setPage(?string $pageName = null, ?string $url = null)
    {
        if (! $pageName) {
            return false;
        }

        if (session_status() === PHP_SESSION_NONE) {
            @session_start(); // Some times it's generated warning!
        }

        $url = $url ?: self::getCurrentURL();
        $time = time();

        if (! isset($_SESSION['FLAMIX_PAGES'])) {
            $_SESSION['FLAMIX_PAGES'] = [];
        }

        $_SESSION['FLAMIX_PAGES'][$time] = [$url, $time, $pageName];
    }

    /**
     * Get all visited pages.
     *
     * @return array|bool
     */
    public static function getPages()
    {
        if (! empty($_SESSION['FLAMIX_PAGES'])) {
            return array_reverse($_SESSION['FLAMIX_PAGES']);
        }

        return false;
    }

    /**
     * Get the current URL.
     *
     * @return string
     */
    public static function getCurrentURL(): string
    {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

        return $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * Get base params: device, UTM and client for the Bitrix24 tracer.
     *
     * @return array
     */
    public static function getBase(): array
    {
        $trace = [
            'url' => self::getCurrentURL(),
            'device' => [
                'isMobile' => static::isMobile(),
            ],
            'tags' => ['ts' => time()],
        ];

        // UTM.
        $utm = UtmCookie::get();
        $trace['tags']['list'] = ! empty($utm) ? $utm : null;

        // Client.
        $client = Conversion::getFromCookie();

        $trace['client']['gaId'] = ! empty($client['_ga']) ? self::parseClientID($client['_ga']) : null;
        $trace['client']['yaId'] = ! empty($client['_ym_uid']) ? $client['_ym_uid'] : null;
        $trace['client']['ttId'] = ! empty($_COOKIE['_ttp']) ? $_COOKIE['_ttp'] : null;

        return $trace;
    }

    /**
     * Get the full result, which we can send to Bitrix24.
     *
     * @param  bool  $json
     * @return array|bool|string
     */
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

    /**
     * Get GID from cookie.
     *
     * @return string|null
     */
    public static function getGID(): ?string
    {
        return $_COOKIE['b24_crm_guest_id'] ?? null;
    }

    /**
     * Save GCLID (Google Ads click id) to cookie for 7 days.
     *
     * @param  string  $gclid
     * @return bool
     */
    public static function setGCLID(string $gclid): bool
    {
        return setcookie('gclid', $gclid, time() + 604800, '/');
    }

    /**
     * Save TTCLID (TikTok click id) to cookie for 7 days.
     *
     * @param  string  $ttclid
     * @return bool
     */
    public static function setTTCLID(string $ttclid): bool
    {
        return setcookie('ttclid', $ttclid, time() + 604800, '/');
    }

    /**
     * Return the pure Client ID.
     *
     * @param  string  $cid
     * @return string|null
     */
    private static function parseClientID(string $cid): ?string
    {
        preg_match("/(?:GA\d\.\d\.|)(\d+\.\d+)/", $cid, $matches);

        return $matches[1] ?? null;
    }

    /**
     * Check if the device is mobile.
     *
     * @return bool
     */
    private static function isMobile(): bool
    {
        return (bool) preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $_SERVER['HTTP_USER_AGENT'] ?? '');
    }
}
