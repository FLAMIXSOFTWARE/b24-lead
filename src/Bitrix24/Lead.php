<?php

namespace Flamix\Bitrix24;

use Exception;
use Flamix\Conversions\Conversion;
use Throwable;
use UtmCookie\UtmCookie;

/**
 * Lead SDK for the Flamix Bitrix24 integrations.
 *
 * Builds a lead payload (auth + analytics) and sends it to the Flamix Lead
 * service, which forwards it to the connected Bitrix24 portal.
 *
 * @see https://lead.app.flamix.solutions/docs
 */
class Lead
{
    /**
     * The shared singleton instance.
     *
     * @var self|null
     */
    private static ?self $instances = null;

    /**
     * The base URL suffix of the Lead service.
     *
     * @var string
     */
    private static string $url = '.app.flamix.solutions/api/';

    /**
     * The API token used to authenticate requests.
     *
     * @var string|null
     */
    private static ?string $token = null;

    /**
     * The Bitrix24 domain (used for auth).
     *
     * @var string|null
     */
    private static ?string $domain = null;

    /**
     * The service subdomain (which APP to talk to).
     *
     * @var string
     */
    private static string $subdomain = 'lead';

    /**
     * The API version.
     *
     * @var string
     */
    private static string $version = 'v1';

    /**
     * Whether to auto-collect trace, host, IP and analytics data.
     *
     * @var bool
     */
    private static bool $send_prepared_anal_data = true;

    /**
     * Prevent direct construction — use getInstance() instead.
     *
     * @return void
     */
    protected function __construct() {}

    /**
     * Prevent cloning of the singleton.
     *
     * @return void
     */
    protected function __clone() {}

    /**
     * Prevent unserialization of the singleton.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize a singleton.");
    }

    /**
     * Get the shared singleton instance.
     *
     * @return self
     */
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
                @session_start(); // Some times it's generated warning!
            }

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Set auth (domain + token).
     *
     * @param  string  $domain
     * @param  string  $token
     * @return self
     */
    public static function auth(string $domain, string $token): self
    {
        self::setDomain($domain);

        return self::setToken($token);
    }

    /**
     * Set your DOMAIN (for auth).
     *
     * @param  string  $domain
     * @return self
     */
    public static function setDomain(string $domain): self
    {
        self::$domain = $domain;

        return self::$instances;
    }

    /**
     * Set your API token.
     *
     * @param  string  $token
     * @return self
     */
    public static function setToken(string $token): self
    {
        self::$token = $token;

        return self::$instances;
    }

    /**
     * Change SubDomain if we have APP on another portal.
     *
     * @param  string  $subdomain
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
     * Create and return the request URL.
     *
     * @return string
     */
    public static function getURL(): string
    {
        return 'https://' . self::$subdomain . self::$url . self::$version . '/';
    }

    /**
     * Prepare base analytics data.
     *
     * @param  array  $data
     * @return void
     */
    private static function prepareAnalyticsData(array &$data): void
    {
        if (empty($data['UF_CRM_FX_CONVERSION'])) {
            $data['UF_CRM_FX_CONVERSION'] = Conversion::getPreparedUID();
        }

        // Add UTM if PHP is good.
        if (empty($data['UTM']) && version_compare(PHP_VERSION, '7.2.0') >= 0) {
            $data['UTM'] = UtmCookie::get();
        }

        // Trace.
        if (empty($data['TRACE'])) {
            $data['TRACE'] = Trace::get(true);
        }

        // Hostname.
        if (empty($data['FIELDS']['HOSTNAME']) && ! empty(SmartUTM::getMyHostname())) {
            $data['FIELDS']['HOSTNAME'] = SmartUTM::getMyHostname();
        }

        // Referer.
        if (empty($data['FIELDS']['REFERER']) && ! empty(SmartUTM::getReferer())) {
            $data['FIELDS']['REFERER'] = SmartUTM::getReferer();
        }

        // User IP.
        if (empty($data['FIELDS']['USER_IP']) && ! empty(SmartUTM::getMyIP())) {
            $data['FIELDS']['USER_IP'] = SmartUTM::getMyIP();
        }

        // Google.
        if (empty($data['FIELDS']['GA_UID']) && ! empty($_COOKIE['_ga'] ?? null)) {
            $data['FIELDS']['GA_UID'] = $_COOKIE['_ga'];
        }

        // Facebook.
        if (empty($data['FIELDS']['FB_UID']) && ! empty($_COOKIE['_fbp'] ?? null)) {
            $data['FIELDS']['FB_UID'] = $_COOKIE['_fbp'];
        }

        // Roistat.
        if (empty($data['FIELDS']['ROISTAT_VISIT_ID']) && ! empty(SmartUTM::getRoistatID())) {
            $data['FIELDS']['ROISTAT_VISIT_ID'] = SmartUTM::getRoistatID();
        }

        // Yandex.
        if (empty($data['FIELDS']['YM_UID']) && ! empty($_COOKIE['_ym_uid'] ?? null)) {
            $data['FIELDS']['YM_UID'] = $_COOKIE['_ym_uid'];
        }

        // TikTok.
        if (empty($data['FIELDS']['TT_UID']) && ! empty($_COOKIE['_ttp'] ?? null)) {
            $data['FIELDS']['TT_UID'] = $_COOKIE['_ttp'];
        }
    }

    /**
     * Prepare DATA (UTM + AUTH + UF_CRM_FX_CONVERSION).
     *
     * @param  array  $data
     * @return array
     *
     * @throws \Exception
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
     * Prepare and send a lead to the service.
     *
     * @param  array  $data
     * @param  string  $actions
     * @return mixed
     *
     * @throws \Exception
     */
    public static function send(array $data = [], string $actions = 'lead/add')
    {
        $data = self::prepareData($data);
        $res = self::post(self::getURL() . $actions, $data);

        $json = json_decode($res, 1);

        if (json_last_error()) {
            throw new Exception('Bad JSON format!');
        }

        return $json;
    }

    /**
     * Send POST request.
     *
     * @param  string  $url
     * @param  array  $data
     * @return string|null
     *
     * @throws \Exception
     */
    public static function post(string $url, array $data = []): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $output = curl_exec($ch);

        if ($output === false) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }

        curl_close($ch);

        return $output;
    }
}
