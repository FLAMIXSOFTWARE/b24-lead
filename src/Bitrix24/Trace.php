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
    public static function init($pageName = false, $url = false)
    {
        SmartUTM::init();
        self::setPage($pageName, $url);
    }

    /**
     * Set visited pages
     *
     * @param bool $pageName
     * @param bool $url
     */
    public static function setPage($pageName = false, $url = false) {
        if(!$pageName)
            return false;

        if(session_status() === PHP_SESSION_NONE)
            session_start();

        if(!$url)
            $url = self::getCurrentURL();

        $time = time();

        if(!isset($_SESSION['FLAMIX_PAGES']))
            $_SESSION['FLAMIX_PAGES'] = [];

        $_SESSION['FLAMIX_PAGES'][$time] = [
            $url,
            $time,
            $pageName,
        ];
    }

    /**
     * Get all visited pages
     *
     * @return bool
     */
    public static function getPages()
    {
        if(!empty($_SESSION['FLAMIX_PAGES']))
            return array_reverse($_SESSION['FLAMIX_PAGES']);

        return false;
    }

    /**
     * Получить текущий URL
     *
     * @return string
     */
    public static function getCurrentURL()
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * Get base params
     *
     * @return array
     */
    public static function getBase()
    {
        $trace = [];
        $trace['url'] = self::getCurrentURL();

        //Devise
        $detect = new \Mobile_Detect;
        $trace['device'] = ['isMobile' => $detect->isMobile()];

        $trace['tags'] = ['ts' => time()];

        //UTM
        $utm = \UtmCookie\UtmCookie::get();
        if(!empty($utm))
            $trace['tags']['list'] = $utm;
        else
            $trace['tags']['list'] = null;

        //Google Click ID
        if(isset($_COOKIE['gclid']) && !empty($_COOKIE['gclid']))
            $trace['tags']['gclid'] = $_COOKIE['gclid'];
        else
            $trace['tags']['gclid'] = null;

        //client
        $client = \Flamix\Conversions\Conversion::getFromCookie();
        $trace['client'] = [];

        if(!empty($client['_ga'])) {
            $tmp = explode('.', $client['_ga']);
            $trace['client']['gaId'] = $tmp['2'] . '.' . $tmp['3'];
            unset($tmp);
        } else
            $trace['client']['gaId'] = null;

        if(!empty($client['_ym_uid']))
            $trace['client']['yaId'] = $client['_ym_uid'];
        else
            $trace['client']['yaId'] = null;

        return $trace;
    }

    /**
     * Get full result, witch we can send to bitrxi24
     *
     * @param bool $json
     * @return array|bool|string
     */
    public static function get($json = false)
    {
        $pages = self::getPages();
        if(!$pages)
            return false;

        $base = self::getBase();
        if(empty($base))
            return false;

        $base['pages'] = ['list' => $pages];

        $gid = self::getGID();
        if($gid)
            $base['pages']['gid'] = $gid;
        else
            $base['pages']['gid'] = null;

        if($json)
            return json_encode($base);

        return $base;
    }

    /**
     * Get GID
     *
     * @return bool
     */
    public static function getGID()
    {
        if(isset($_COOKIE['b24_crm_guest_id']) && !empty($_COOKIE['b24_crm_guest_id']))
            return $_COOKIE['b24_crm_guest_id'];

        return false;
    }
}