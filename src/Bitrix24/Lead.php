<?php

namespace Flamix\Bitrix24;

use \GuzzleHttp\Client as Http;

class Lead
{
    private static $instances;
    private static $url = '.app.flamix.solutions/api/';
    private static $token;
    private static $domain;
    private static $subdomain = 'lead';
    private static $version = 'v1';


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
     * @param string $token
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

    /**
     * Change SubDomain if we have APP on unother portal
     *
     * @param string $subdomain
     * @return mixed
     */
    public static function changeSubDomain(string $subdomain)
    {
        self::$subdomain = $subdomain;
        return self::$instances;
    }

    /**
     * Create and return URL
     *
     * @return string
     */
    public static function getURL()
    {
        return 'https://' . self::$subdomain . self::$url . self::$version . '/';
    }

    /**
     * Prepare DATA (UTM+AUTH+UF_CRM_FX_CONVERSION)
     *
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public static function prepareData(array $data = [])
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

        //TRACE
        if(empty($data['TRACE']))
            $data['TRACE'] = Trace::get(true);

        return $data;
    }

    /**
     * @param array $data
     * @param string $actions
     * @return mixed
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function send(array $data = [], string $actions = 'lead/add')
    {
        $data = self::prepareData($data);

        $http = new Http(['base_uri' => self::getURL()]);
        $res = $http->request('POST', $actions, ['form_params' => $data]);

        //DEBUG
        //var_dump($res->getBody()->getContents());

        $json = json_decode($res->getBody(), 1);

        if(json_last_error())
            throw new \Exception('Bad JSON format');

        return $json;
    }
}
