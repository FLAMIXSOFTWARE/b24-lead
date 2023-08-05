<?php

namespace Flamix\Bitrix24;

use Flamix\Conversions\Conversion;
use UtmCookie\UtmCookie;
use GuzzleHttp\Client as Http;
use Exception;

class Lead
{
    private static $instances;
    private static $url = '.app.flamix.solutions/api/';
    private static $token;
    private static $domain;
    private static $subdomain = 'lead';
    private static $version = 'v1';
    private static $send_prepared_anal_data = true;

    protected function __construct()
    {
    }

    protected function __clone()
    {
    }

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize a singleton.");
    }

    public static function getInstance(): self
    {
        if (empty(self::$instances)) {
            self::$instances = new static;
        }

        self::session();
        return self::$instances;
    }

    /**
     * Ensure that the session starts. If not started - run!
     *
     * @return bool
     */
    private static function session(): bool
    {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Set your API code.
     *
     * @param string $token
     * @return self
     */
    public static function setToken(string $token): self
    {
        self::$token = $token;
        return self::$instances;
    }

    /**
     * Set your DOMAIN (for auth).
     *
     * @param string $domain
     * @return self
     */
    public static function setDomain(string $domain): self
    {
        self::$domain = $domain;
        return self::$instances;
    }

    /**
     * Set extra fields to Lead.
     *
     * @param array $fields
     * @return self
     */
    public static function setExtraFields(array $fields): self
    {
        self::$extra_fields = $fields;
        return self::$instances;
    }

    /**
     * Change SubDomain if we have APP on another portal.
     *
     * @param string $subdomain
     * @return self
     */
    public static function changeSubDomain(string $subdomain): self
    {
        self::$subdomain = $subdomain;
        return self::$instances;
    }

    /**
     * Disable auto get trace, host, IP, etc.
     *
     * @return self
     */
    public static function disableAutoAnalytics(): self
    {
        self::$send_prepared_anal_data = false;
        return self::$instances;
    }

    /**
     * Enable auto-get trace, host, IP, etc.
     *
     * @return self
     */
    public static function enableAutoAnalytics(): self
    {
        self::$send_prepared_anal_data = true;
        return self::$instances;
    }

    /**
     * Create and return URL
     *
     * @return string
     */
    public static function getURL(): string
    {
        return 'https://' . self::$subdomain . self::$url . self::$version . '/';
    }

    /**
     * Prepare base analytics data
     *
     * @param $data
     */
    private static function prepareAnalyticsData(&$data)
    {
        if (empty($data['UF_CRM_FX_CONVERSION'])) {
            $data['UF_CRM_FX_CONVERSION'] = Conversion::getPreparedUID();
        }

        // Add UTM if PHP is good
        if (empty($data['UTM']) && version_compare(PHP_VERSION, '7.2.0') >= 0) {
            $data['UTM'] = UtmCookie::get();
        }

        // TRACE
        if (empty($data['TRACE'])) {
            $data['TRACE'] = Trace::get(true);
        }

        //HOSTNAME
        if (empty($data['FIELDS']['HOSTNAME']) && !empty(SmartUTM::getMyHostname())) {
            $data['FIELDS']['HOSTNAME'] = SmartUTM::getMyHostname();
        }

        //REFERER
        if (empty($data['FIELDS']['REFERER']) && !empty(SmartUTM::getReferer())) {
            $data['FIELDS']['REFERER'] = SmartUTM::getReferer();
        }

        //USER IP
        if (empty($data['FIELDS']['USER_IP']) && !empty(SmartUTM::getMyIP())) {
            $data['FIELDS']['USER_IP'] = SmartUTM::getMyIP();
        }

        //ROISTAT_VISIT_ID
        if (empty($data['FIELDS']['ROISTAT_VISIT_ID']) && !empty(SmartUTM::getRoistatID())) {
            $data['FIELDS']['ROISTAT_VISIT_ID'] = SmartUTM::getRoistatID();
        }
    }

    /**
     * Prepare DATA (UTM+AUTH+UF_CRM_FX_CONVERSION)
     *
     * @param array $data
     * @return array
     * @throws Exception
     */
    public static function prepareData(array $data = []): array
    {
        if (empty(self::$domain)) {
            throw new Exception('Empty DOMAIN!');
        }

        if (empty(self::$token)) {
            throw new Exception('Empty api_token!');
        }

        $data = array_merge($data, [
            'DOMAIN' => self::$domain,
            'api_token' => self::$token,
        ]);

        if (self::$send_prepared_anal_data) {
            self::prepareAnalyticsData($data);
        }

        return $data;
    }

    /**
     * @param array $data
     * @param string $actions
     * @return mixed
     * @throws Exception
     */
    public static function send(array $data = [], string $actions = 'lead/add')
    {
        $data = self::prepareData($data);

        $http = new Http(['base_uri' => self::getURL(), 'verify' => false]);
        $res = $http->request('POST', $actions, ['form_params' => $data]);

        //DEBUG
        //var_dump($res->getBody()->getContents());

        $json = json_decode($res->getBody(), 1);

        if (json_last_error()) {
            throw new Exception('Bad JSON format!');
        }

        return $json;
    }
}
