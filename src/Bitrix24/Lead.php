<?php

namespace Flamix\Bitrix24;

use \GuzzleHttp\Client as Http;

class Lead
{
    private static $instances;
    private static $url = 'https://lead.app.flamix.solutions/api/v1/';
    private static $token;
    private static $domain;


    protected function __construct() {}
    protected function __clone() {}
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }


    public static function getInstance(): Lead
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
    public static function setToken(string $token)
    {
        self::$token = $token;
        return self::$instances;
    }

    /**
     * Set your DOMAIN (for auth)
     *
     * @param string $domain
     * @return mixed
     */
    public static function setDomain(string $domain)
    {
        self::$domain = $domain;
        return self::$instances;
    }

    public static function send(array $data = [], string $actions = 'lead/add')
    {
        if(empty(self::$domain))
            throw new \Exception('Empty DOMAIN!');

        if(empty(self::$token))
            throw new \Exception('Empty api_token!');

        $data = array_merge($data, [
            'DOMAIN' => self::$domain,
            'api_token' => self::$token,
        ]);

        //Add user UID
        if(empty($data['UF_CRM_FX_CONVERSION']))
            $data['UF_CRM_FX_CONVERSION'] = \Flamix\Conversions\Conversion::getPreparedUID();

        //Add UTM if PHP is good
        if(empty($data['UTM']) && version_compare(PHP_VERSION, '7.2.0') >= 0)
            $data['UTM'] = \UtmCookie\UtmCookie::get();

        $http = new Http(['base_uri' => self::$url]);
        $res = $http->request('POST', $actions, ['query' => $data]);

        //DEBUG
        //var_dump($res->getBody()->getContents());

        return $res->getStatusCode();
    }
}
