<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PageControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }

    public function testPostShowNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/post/99999');

        self::assertResponseStatusCodeSame(404);
    }
}
