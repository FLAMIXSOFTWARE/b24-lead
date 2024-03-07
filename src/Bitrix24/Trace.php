<?php

namespace Flamix\Bitrix24;

/**
 * Trace for Bitrix24
 *
 * Class Trace
 * @package Flamix\Bitrix24
 */
class Trace
{
    /**
     * Start trace.
     *
     * @param string|null $pageName
     * @param string|null $url
     * @return void
     */
    public static function init(?string $pageName = null, ?string $url = null)
    {
        // Init SmartUTM.
        SmartUTM::init();

        // Save GCLID from URL.
        if (isset($_GET['gclid'])) {
            self::setGCLID($_GET['gclid']);
        }

        self::setPage($pageName, $url);
    }

    /**
     * Set visited pages.
     *
     * @param string|null $pageName
     * @param string|null $url
     * @return false|void
     */
    public static function setPage(?string $pageName = null, ?string $url = null)
    {
        if (!$pageName) return false;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $url = $url ?: self::getCurrentURL();
        $time = time();

        if (!isset($_SESSION['FLAMIX_PAGES'])) {
            $_SESSION['FLAMIX_PAGES'] = [];
        }

        $_SESSION['FLAMIX_PAGES'][$time] = [$url, $time, $pageName];
    }

    /**
     * Get all visited pages
     *
     * @return bool|array
     */
    public static function getPages()
    {
        if (!empty($_SESSION['FLAMIX_PAGES'])) {
            return array_reverse($_SESSION['FLAMIX_PAGES']);
        }

        return false;
    }

    /**
     * Current URL.
     *
     * @return string
     */
    public static function getCurrentURL(): string
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * Get base params. Device, UTM, Client to Bitrix24 Tracer.
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

        // UTM
        $utm = \UtmCookie\UtmCookie::get();
        $trace['tags']['list'] = !empty($utm) ? $utm : null;

        //client
        $client = \Flamix\Conversions\Conversion::getFromCookie();

        $trace['client']['gaId'] = !empty($client['_ga']) ? self::parseClientID($client['_ga']) : null;
        $trace['client']['yaId'] = !empty($client['_ym_uid']) ? $client['_ym_uid'] : null;

        return $trace;
    }

    /**
     * Get full result, witch we can send to Bitrix24.
     *
     * @param bool $json
     * @return array|bool|string
     */
    public static function get($json = false)
    {
        $pages = self::getPages();
        if (!$pages) return false;

        $base = self::getBase();
        if (empty($base)) return false;

        $base['pages'] = ['list' => $pages];

        $gid = self::getGID();
        $base['pages']['gid'] = $gid ?: null;

        if ($json) {
            return json_encode($base);
        }

        return $base;
    }

    /**
     * Get GID from cookie.
     */
    public static function getGID(): ?string
    {
        return $_COOKIE['b24_crm_guest_id'] ?? null;
    }

    /**
     * Save GCLID to cookie for 7 days.
     *
     * @param string $gclid
     * @return bool
     */
    public static function setGCLID(string $gclid): bool
    {
        return setcookie('gclid', $gclid, time() + 604800, '/');
    }

    /**
     * Return pure Client ID from.
     *
     * @param string $cid
     * @return string|null
     */
    private static function parseClientID(string $cid): ?string
    {
        preg_match("/(?:GA\d\.\d\.|)(\d+\.\d+)/", $cid, $matches);
        return $matches[1] ?? null;
    }

    /**
     * Check is mobile device.
     *
     * @return bool
     */
    private static function isMobile(): bool
    {
        return preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $_SERVER['HTTP_USER_AGENT'] ?? '');
    }
}