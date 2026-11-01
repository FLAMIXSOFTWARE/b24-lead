<?php

namespace Flamix\Bitrix24;

use Exception;
use Flamix\Conversions\Conversion;
use Throwable;
use UtmCookie\UtmCookie;

/** Static client that posts a lead (auth + visitor analytics) to a Flamix Lead app, which forwards it to Bitrix24. */
class Lead
{
    private static ?self $instances = null;

    private static string $url = '.app.flamix.solutions/api/';

    private static ?string $token = null;

    private static ?string $domain = null;

    private static string $subdomain = 'lead';

    private static string $version = 'v1';

    private static bool $send_prepared_anal_data = true;

    protected function __construct() {}

    protected function __clone() {}

    /** @throws \Exception always: the singleton is not unserializable. */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize a singleton.");
    }

    /** The shared instance; also starts the native PHP session. */
    public static function getInstance(): self
    {
        if (empty(self::$instances)) {
            self::$instances = new static;
        }

        self::session();

        return self::$instances;
    }

    /** Start the native PHP session if none is active. */
    private static function session(): bool
    {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                // @: warns once headers are sent, and a lead form must not break on it.
                @session_start();
            }

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /** Set the Bitrix24 domain and the app token. */
    public static function auth(string $domain, string $token): self
    {
        self::setDomain($domain);

        return self::setToken($token);
    }

    /** Set the Bitrix24 portal domain. */
    public static function setDomain(string $domain): self
    {
        self::$domain = $domain;

        return self::getInstance();
    }

    /** Set the app API token. */
    public static function setToken(string $token): self
    {
        self::$token = $token;

        return self::getInstance();
    }

    /** Switch to another Flamix app, e.g. leadframework. */
    public static function changeSubDomain(string $subdomain): self
    {
        self::$subdomain = $subdomain;

        return self::getInstance();
    }

    /** Stop send() adding trace, host, IP and analytics ids. */
    public static function disableAutoAnalytics(): self
    {
        self::$send_prepared_anal_data = false;

        return self::getInstance();
    }

    /** Make send() add trace, host, IP and analytics ids again (the default). */
    public static function enableAutoAnalytics(): self
    {
        self::$send_prepared_anal_data = true;

        return self::getInstance();
    }

    /** The API base URL, ending with a slash. */
    public static function getURL(): string
    {
        return 'https://' . self::$subdomain . self::$url . self::$version . '/';
    }

    /** Fill every analytics key the caller left empty. */
    private static function prepareAnalyticsData(array &$data): void
    {
        if (empty($data['UF_CRM_FX_CONVERSION'])) {
            $data['UF_CRM_FX_CONVERSION'] = Conversion::getPreparedUID();
        }

        if (empty($data['UTM'])) {
            $data['UTM'] = UtmCookie::get();
        }

        if (empty($data['TRACE'])) {
            $data['TRACE'] = Trace::get(true);
        }

        if (empty($data['FIELDS']['HOSTNAME']) && ! empty(SmartUTM::getMyHostname())) {
            $data['FIELDS']['HOSTNAME'] = SmartUTM::getMyHostname();
        }

        if (empty($data['FIELDS']['REFERER']) && ! empty(SmartUTM::getReferer())) {
            $data['FIELDS']['REFERER'] = SmartUTM::getReferer();
        }

        if (empty($data['FIELDS']['USER_IP']) && ! empty(SmartUTM::getMyIP())) {
            $data['FIELDS']['USER_IP'] = SmartUTM::getMyIP();
        }

        if (empty($data['FIELDS']['GA_UID']) && ! empty($_COOKIE['_ga'] ?? null)) {
            $data['FIELDS']['GA_UID'] = $_COOKIE['_ga'];
        }

        if (empty($data['FIELDS']['FB_UID']) && ! empty($_COOKIE['_fbp'] ?? null)) {
            $data['FIELDS']['FB_UID'] = $_COOKIE['_fbp'];
        }

        if (empty($data['FIELDS']['ROISTAT_VISIT_ID']) && ! empty(SmartUTM::getRoistatID())) {
            $data['FIELDS']['ROISTAT_VISIT_ID'] = SmartUTM::getRoistatID();
        }

        if (empty($data['FIELDS']['YM_UID']) && ! empty($_COOKIE['_ym_uid'] ?? null)) {
            $data['FIELDS']['YM_UID'] = $_COOKIE['_ym_uid'];
        }

        if (empty($data['FIELDS']['TT_UID']) && ! empty($_COOKIE['_ttp'] ?? null)) {
            $data['FIELDS']['TT_UID'] = $_COOKIE['_ttp'];
        }
    }

    /**
     * The payload send() posts: the data plus DOMAIN, api_token and, unless disabled, analytics.
     *
     * @throws \Exception when the domain or the token is not set
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
     * Post the prepared data to an action of the app and return the decoded JSON.
     *
     * @return mixed
     *
     * @throws \Exception on missing auth, a cURL error or a non-JSON response
     */
    public static function send(array $data = [], string $actions = 'lead/add')
    {
        $data = self::prepareData($data);
        // static::, not self::: the tests replace post() in a subclass to stay off the network.
        $res = static::post(self::getURL() . $actions, $data);

        $json = json_decode($res, 1);

        if (json_last_error()) {
            throw new Exception('Bad JSON format!');
        }

        return $json;
    }

    /**
     * Form-encoded POST over cURL; returns the raw body.
     *
     * @throws \Exception on a cURL error
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
