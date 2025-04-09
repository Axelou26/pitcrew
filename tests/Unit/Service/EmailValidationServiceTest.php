<?php

namespace App\Tests\Unit\Service;

use App\Service\EmailValidationService;
use PHPUnit\Framework\TestCase;

class EmailValidationServiceTest extends TestCase
{
    private EmailValidationService $emailValidationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emailValidationService = new EmailValidationService('test');
    }

    /**
     * @dataProvider validEmailProvider
     */
    public function testValidEmails(string $email): void
    {
        $this->assertTrue($this->emailValidationService->isValidEmail($email));
        $this->assertEmpty($this->emailValidationService->getValidationErrors($email));
    }

    /**
     * @dataProvider invalidEmailProvider
     */
    public function testInvalidEmails(string $email): void
    {
        $this->assertFalse($this->emailValidationService->isValidEmail($email));
        $this->assertNotEmpty($this->emailValidationService->getValidationErrors($email));
    }

    public function validEmailProvider(): array
    {
        return [
            ['test@example.com'],
            ['user.name@example.com'],
            ['user+tag@example.com'],
            ['very.common@example.com'],
            ['disposable.style.email.with+symbol@example.com'],
            ['other.email-with-hyphen@example.com'],
            ['fully-qualified-domain@example.com'],
            ['user.name+tag+sorting@example.com'],
            ['x@example.com'],
            ['example-indeed@strange-example.com'],
        ];
    }

    public function invalidEmailProvider(): array
    {
        return [
            ['plainaddress'],
            ['@missinguser.com'],
            ['user@'],
            ['.user@example.com'],
            ['user.@example.com'],
            ['user..name@example.com'],
            ['user@example..com'],
            ['user@.example.com'],
            ['user@example.'],
            ['user name@example.com'],
            ['user@example.com.'],
            ['user@-example.com'],
            ['user@example-.com'],
            [str_repeat('a', 255) . '@example.com'], // Email trop long
            ['user@' . str_repeat('a', 255) . '.com'], // Domaine trop long
        ];
    }
}
