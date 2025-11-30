<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\TwigAssertionsTrait;
use Tests\Support\KernelTestCase;

class TwigAssertionsTest extends KernelTestCase
{
    use TwigAssertionsTrait;

    protected bool $profilerEnabled = true;

    public function testDontSeeRenderedTemplate(): void
    {
        $this->client->request('GET', '/register');

        $this->dontSeeRenderedTemplate('security/login.html.twig');
    }

    public function testSeeCurrentTemplateIs(): void
    {
        $this->client->request('GET', '/login');

        $this->seeCurrentTemplateIs('security/login.html.twig');
    }

    public function testSeeRenderedTemplate(): void
    {
        $this->client->request('GET', '/login');

        $this->seeRenderedTemplate('layout.html.twig');
        $this->seeRenderedTemplate('security/login.html.twig');
    }
}
