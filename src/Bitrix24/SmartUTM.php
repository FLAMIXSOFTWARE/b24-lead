<?php

namespace Flamix\Bitrix24;

/**
 * Save UTM_SOURCE from HTTP_REFERER
 *
 * Class SmartUTM
 * @package Flamix\Bitrix24
 */
class SmartUTM
{
    /**
     * Init Smart UTM
     */
    public static function init()
    {
        self::checkAndSave();
    }

    /**
     * Get My hostname
     * todo Check CloudFlare
     *
     * @return bool|string
     */
    public static function getMyHostname()
    {
        $host = false;
        if(!empty($_SERVER['SERVER_NAME']))
            $host = $_SERVER['SERVER_NAME'];

        //todo check CloudFlare

        if($host)
            return self::pretty($host);

        return false;
    }

    /**
     * Get User IP
     * IP - Internet Portal :)
     *
     * @return bool|string
     */
    public static function getMyIP()
    {
        if(!empty($_SERVER['REMOTE_ADDR']))
            return $_SERVER['REMOTE_ADDR'];

        //CloudFlare
        if($_SERVER['HTTP_CF_CONNECTING_IP'])
            return $_SERVER['HTTP_CF_CONNECTING_IP'];

        return false;
    }

    /**
     * ROISTAT - Russian analytic system
     *
     * @return string
     */
    public static function getRoistatID()
    {
        return isset($_COOKIE['roistat_visit']) ? $_COOKIE['roistat_visit'] : '';
    }

    /**
     * Get referer and remove www.
     *
     * @return bool|string
     */
    public static function getReferer()
    {
        if(!empty($_SERVER['HTTP_REFERER'])) {
            $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
            if(!empty($referer))
                return self::pretty($referer);
        }

        return false;
    }

    /**
     * Remove www.
     *
     * @param string $host
     * @return string
     */
    private static function pretty(string $host): string
    {
        return str_replace('www.', '', $host);
    }

    /**
     * If we already have UTM?
     *
     * @return bool
     */
    private static function isWeHaveUTM(): bool
    {
        $utm_source = \UtmCookie\UtmCookie::get('utm_source');
        if(isset($utm_source) && $utm_source != false && $utm_source != NULL)
            return true;

        return false;
    }

    /**
     * If we didn't have UTM, but have REFERER - Save REFERER to UTM
     *
     * @return bool
     */
    public static function checkAndSave()
    {
        if(self::isWeHaveUTM())
            return true;

        $referer = self::getReferer();
        $host = self::getMyHostname();

//        var_dump('Host:', $host);
//        var_dump('Referer:', $referer);

        if(isset($referer) && isset($host) && $referer !== $host)
            \UtmCookie\UtmCookie::save(['utm_source' => $referer]);
    }
}