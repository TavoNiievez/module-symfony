<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class MimeCest
{
    public function mimeAssertions(FunctionalTester $I): void
    {
        $logger = $I->grabService('mailer.message_logger_listener');
        $logger->reset();

        $I->amOnRoute('send_email');

        $I->assertEmailAddressContains('To', 'jane_doe@example.com');
        $I->assertEmailAttachmentCount(1);
        $I->assertEmailHasHeader('To');
        $I->assertEmailHeaderSame('To', 'jane_doe@example.com');
        $I->assertEmailHeaderNotSame('To', 'john_doe@example.com');
        $I->assertEmailHtmlBodyContains('HTML body');
        $I->assertEmailHtmlBodyNotContains('password');
        $I->assertEmailNotHasHeader('Bcc');
        $I->assertEmailTextBodyContains('Example text body');
        $I->assertEmailTextBodyNotContains('Secret');
    }
}
