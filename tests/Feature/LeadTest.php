<?php

namespace Flamix\Bitrix24\Tests\Feature;

use Exception;
use Flamix\Bitrix24\Lead;
use Flamix\Bitrix24\Tests\Fixtures\FakeLead;
use Flamix\Bitrix24\Trace;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

require_once __DIR__ . '/../Fixtures/FakeLead.php';

/** Lead::send() and the tracking it attaches, with post() faked. */
class LeadTest extends TestCase
{
    protected function setUp(): void
    {
        $defaults = [
            'instances' => null,
            'token' => null,
            'domain' => null,
            'subdomain' => 'lead',
            'send_prepared_anal_data' => true,
        ];

        $lead = new ReflectionClass(Lead::class);

        foreach ($defaults as $property => $value) {
            $reflection = $lead->getProperty($property);
            $reflection->setAccessible(true);
            $reflection->setValue(null, $value);
        }

        FakeLead::$sent = [];
        FakeLead::$response = '{"result":true}';
        $_COOKIE = [];
        $_GET = [];
        $_SESSION = [];
        unset($_SERVER['SERVER_NAME'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_REFERER'], $_SERVER['HTTP_CF_CONNECTING_IP'], $_SERVER['HTTPS']);
        $_SERVER['HTTP_HOST'] = 'shop.example.com';
        $_SERVER['REQUEST_URI'] = '/pricing';
    }

    private function lastRequest(): array
    {
        $this->assertCount(1, FakeLead::$sent);

        return FakeLead::$sent[0];
    }

    public function test_send_posts_the_fields_with_auth_to_lead_add(): void
    {
        $response = FakeLead::getInstance()->auth('portal.bitrix24.com', 'secret')->disableAutoAnalytics()
            ->send(['FIELDS' => ['NAME' => 'Roman']]);

        $request = $this->lastRequest();
        $this->assertSame(['result' => true], $response);
        $this->assertSame('https://lead.app.flamix.solutions/api/v1/lead/add', $request['url']);
        $this->assertSame([
            'FIELDS' => ['NAME' => 'Roman'],
            'DOMAIN' => 'portal.bitrix24.com',
            'api_token' => 'secret',
        ], $request['data']);
    }

    public function test_sub_domain_and_action_choose_the_endpoint(): void
    {
        FakeLead::getInstance()->changeSubDomain('leadframework')->setDomain('portal.bitrix24.com')->setToken('secret');
        FakeLead::send(['ID' => 7], 'status/change');

        $this->assertSame('https://leadframework.app.flamix.solutions/api/v1/status/change', $this->lastRequest()['url']);
    }

    public function test_static_setters_work_before_get_instance(): void
    {
        FakeLead::auth('portal.bitrix24.com', 'secret');

        $this->assertSame('portal.bitrix24.com', FakeLead::prepareData()['DOMAIN']);
    }

    public function test_send_without_auth_throws_before_any_request(): void
    {
        try {
            FakeLead::getInstance()->setToken('secret')->send();
            $this->fail('No exception without a domain.');
        } catch (Exception $e) {
            $this->assertSame('Empty DOMAIN!', $e->getMessage());
        }

        $this->assertSame([], FakeLead::$sent);
    }

    public function test_a_non_json_response_throws(): void
    {
        FakeLead::$response = '<html>502 Bad Gateway</html>';

        $this->expectExceptionMessage('Bad JSON format!');

        FakeLead::getInstance()->auth('portal.bitrix24.com', 'secret')->send();
    }

    public function test_auto_analytics_fill_only_the_fields_the_caller_left_empty(): void
    {
        $_SERVER['SERVER_NAME'] = 'www.shop.example.com';
        $_SERVER['REMOTE_ADDR'] = '203.0.113.5';
        $_SERVER['HTTP_REFERER'] = 'https://www.google.com/search?q=crm';
        $_COOKIE = [
            '_ga' => 'GA1.1.111.222',
            '_fbp' => 'fb.1.333',
            '_ym_uid' => '444',
            '_ttp' => 'tt-555',
            'roistat_visit' => '666',
        ];

        FakeLead::getInstance()->auth('portal.bitrix24.com', 'secret')
            ->send(['FIELDS' => ['NAME' => 'Roman', 'GA_UID' => 'from-form']]);

        $data = $this->lastRequest()['data'];
        $this->assertSame([
            'NAME' => 'Roman',
            'GA_UID' => 'from-form',
            'HOSTNAME' => 'shop.example.com',
            'REFERER' => 'google.com',
            'USER_IP' => '203.0.113.5',
            'FB_UID' => 'fb.1.333',
            'ROISTAT_VISIT_ID' => '666',
            'YM_UID' => '444',
            'TT_UID' => 'tt-555',
        ], $data['FIELDS']);
        $this->assertSame('444;GA1.1.111.222;fb.1.333;tt-555', $data['UF_CRM_FX_CONVERSION']);
    }

    public function test_recorded_pages_go_to_the_lead_as_trace_json(): void
    {
        $_SERVER['HTTPS'] = 'on';
        $_COOKIE = ['_ga' => 'GA1.1.111.222', 'b24_crm_guest_id' => 'g-1'];
        Trace::setPage('Pricing');

        FakeLead::getInstance()->auth('portal.bitrix24.com', 'secret')->send();

        $trace = json_decode($this->lastRequest()['data']['TRACE'], true);
        $this->assertSame('https://shop.example.com/pricing', $trace['url']);
        $this->assertSame('111.222', $trace['client']['gaId']);
        $this->assertSame('g-1', $trace['pages']['gid']);
        $this->assertSame(['https://shop.example.com/pricing', 'Pricing'], [$trace['pages']['list'][0][0], $trace['pages']['list'][0][2]]);
    }

    public function test_two_pages_in_the_same_second_are_both_traced(): void
    {
        $this->markTestIncomplete('Trace.php:50 keys FLAMIX_PAGES by time(), so a second page within the same second overwrites the first.');

        Trace::setPage('Home', 'https://shop.example.com/');
        Trace::setPage('Pricing', 'https://shop.example.com/pricing');

        $this->assertCount(2, Trace::getPages());
    }

    public function test_user_ip_behind_cloudflare_is_the_visitor_ip(): void
    {
        $this->markTestIncomplete('SmartUTM.php:35 returns REMOTE_ADDR first, so behind Cloudflare USER_IP is the Cloudflare edge, never HTTP_CF_CONNECTING_IP.');

        $_SERVER['REMOTE_ADDR'] = '172.68.0.1';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '203.0.113.5';

        FakeLead::getInstance()->auth('portal.bitrix24.com', 'secret')->send();

        $this->assertSame('203.0.113.5', $this->lastRequest()['data']['FIELDS']['USER_IP']);
    }
}
