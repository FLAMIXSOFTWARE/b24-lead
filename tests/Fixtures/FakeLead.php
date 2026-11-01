<?php

namespace Flamix\Bitrix24\Tests\Fixtures;

use Flamix\Bitrix24\Lead;

/** Lead whose post() records the request and returns a canned body instead of calling the Flamix app. */
class FakeLead extends Lead
{
    /** @var list<array{url: string, data: array}> */
    public static array $sent = [];

    public static string $response = '{"result":true}';

    public static function post(string $url, array $data = []): ?string
    {
        self::$sent[] = ['url' => $url, 'data' => $data];

        return self::$response;
    }
}
