<?php

namespace Flamix\Bitrix24;

use UtmCookie\UtmCookie;

/** Saves the referer host as utm_source when a visit has no UTM, and reads host, IP and Roistat id for the lead. */
class SmartUTM
{
    /** Call on every page before output: it may set the utm cookie. */
    public static function init(): void
    {
        self::checkAndSave();
    }

    /** @return false|string SERVER_NAME without www. */
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

    /** @return false|string */
    public static function getMyIP()
    {
        if (! empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        if (! empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }

        return false;
    }

    /** The Roistat visit id cookie, or an empty string. */
    public static function getRoistatID(): string
    {
        return $_COOKIE['roistat_visit'] ?? '';
    }

    /** @return false|string the referer host without www. */
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

    /** Remove www. from a host. */
    private static function pretty(string $host): string
    {
        return str_replace('www.', '', $host);
    }

    /** Whether the visit already has a utm_source. */
    private static function isWeHaveUTM(): bool
    {
        return ! empty(UtmCookie::get('utm_source'));
    }

    /** Save the referer host as utm_source unless a UTM exists or the referer is this site. */
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
