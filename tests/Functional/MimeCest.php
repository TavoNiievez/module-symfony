<?php
namespace Tests\Functional;

use Symfony\Component\Mime\Email;
use Tests\FunctionalTester;

class MimeCest
{
    public function _before(FunctionalTester $I): void
    {
        $logger = $I->grabService('mailer.message_logger_listener');
        $logger->reset();

        $I->amOnRoute('send_email');
    }

    public function assertEmailAddressContains(FunctionalTester $I): void
    {
        $I->assertEmailAddressContains('To', 'jane_doe@example.com');
    }

    public function assertEmailAttachmentCount(FunctionalTester $I): void
    {
        $I->assertEmailAttachmentCount(1);
    }

    public function assertEmailHasHeader(FunctionalTester $I): void
    {
        $I->assertEmailHasHeader('To');
    }

    public function assertEmailHeaderSame(FunctionalTester $I): void
    {
        $I->assertEmailHeaderSame('To', 'jane_doe@example.com');
    }

    public function assertEmailHeaderNotSame(FunctionalTester $I): void
    {
        $I->assertEmailHeaderNotSame('To', 'john_doe@example.com');
    }

    public function assertEmailHtmlBodyContains(FunctionalTester $I): void
    {
        $I->assertEmailHtmlBodyContains('Example Email');
    }

    public function assertEmailHtmlBodyNotContains(FunctionalTester $I): void
    {
        $I->assertEmailHtmlBodyNotContains('userpassword');
    }

    public function assertEmailNotHasHeader(FunctionalTester $I): void
    {
        $I->assertEmailNotHasHeader('Bcc');
    }

    public function assertEmailTextBodyContains(FunctionalTester $I): void
    {
        $I->assertEmailTextBodyContains('Example text body');
    }

    public function assertEmailTextBodyNotContains(FunctionalTester $I): void
    {
        $I->assertEmailTextBodyNotContains('My secret text body');
    }

    public function assertionsWorkWithProvidedEmail(FunctionalTester $I): void
    {
        $email = (new Email())
            ->from('custom@example.com')
            ->to('custom@example.com')
            ->text('Custom body text');

        $I->assertEmailAddressContains('To', 'custom@example.com', $email);
        $I->assertEmailTextBodyContains('Custom body text', $email);
        $I->assertEmailNotHasHeader('Cc', $email);
    }
}
