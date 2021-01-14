<?php

namespace Flamix\Bitrix24;

class Lead
{
    private static $instances;
    private static $url = 'https://lead.app.flamix.solutions/api/v1/';
    private static $code;
    private static $domain;


    protected function __construct() {}
    protected function __clone() {}
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }


    public static function getInstance(): Conversion
    {
        if( empty(self::$instances) )
            self::$instances = new static;

        return self::$instances;
    }

    /**
     * Set your API code
     *
     * @param string $code
     * @return mixed
     */
    public static function setCode( string $code )
    {
        self::$code = $code;
        return self::$instances;
    }

    /**
     * Set your DOMAIN (for auth)
     *
     * @param string $domain
     * @return mixed
     */
    public static function setDomain( string $domain )
    {
        self::$domain = $domain;
        return self::$instances;
    }

    public static function add( $uid, $price = 0, $currency = '' )
    {

    }

    public static function url(string $actions = 'lead/add')
    {
        return 'https://lead.app.flamix.solutions/api/v1/' . $actions;
    }
}
