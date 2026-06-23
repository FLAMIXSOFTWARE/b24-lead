<?php

namespace Flamix\Bitrix24;

use UtmCookie\UtmCookie;

/**
 * Save UTM_SOURCE from HTTP_REFERER.
 *
 * @see https://lead.app.flamix.solutions/docs
 */
class SmartUTM
{
    /**
     * Init Smart UTM.
     *
     * @return void
     */
    public static function init(): void
    {
        self::checkAndSave();
    }

    /**
     * Get my hostname.
     *
     * @return bool|string
     *
     * @todo Check CloudFlare.
     */
    public static function getMyHostname()
    {
        $host = false;

        if (! empty($_SERVER['SERVER_NAME'])) {
            $host = $_SERVER['SERVER_NAME'];
        }

        if ($host) {
            return self::pretty($host);
        }

        return false;
    }

    /**
     * Get the user IP.
     *
     * @return bool|string
     */
    public static function getMyIP()
    {
        if (! empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        // CloudFlare.
        if (! empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }

        return false;
    }

    /**
     * ROISTAT - Russian analytic system.
     *
     * @return string
     */
    public static function getRoistatID(): string
    {
        return $_COOKIE['roistat_visit'] ?? '';
    }

    /**
     * Get the referer host and remove www.
     *
     * @return bool|string
     */
    public static function getReferer()
    {
        if (! empty($_SERVER['HTTP_REFERER'])) {
            $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);

            if (! empty($referer)) {
                return self::pretty($referer);
            }
        }

        return false;
    }

    /**
     * Remove the www. prefix from a host.
     *
     * @param  string  $host
     * @return string
     */
    private static function pretty(string $host): string
    {
        return str_replace('www.', '', $host);
    }

    /**
     * Do we already have a UTM source?
     *
     * @return bool
     */
    private static function isWeHaveUTM(): bool
    {
        return ! empty(UtmCookie::get('utm_source'));
    }

    /**
     * If we didn't have UTM, but have REFERER - save REFERER to UTM.
     *
     * @return bool
     */
    public static function checkAndSave(): bool
    {
        if (self::isWeHaveUTM()) {
            return true;
        }

        $referer = self::getReferer();
        $host = self::getMyHostname();

        if ($referer && $host && $referer !== $host) {
            UtmCookie::save(['utm_source' => $referer]);

            return true;
        }

        return false;
    }
}
