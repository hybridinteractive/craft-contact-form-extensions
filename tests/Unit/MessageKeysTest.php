<?php

declare(strict_types=1);

namespace hybridinteractive\contactformextensions\tests\Unit;

use hybridinteractive\contactformextensions\exporters\FlatExporter;
use hybridinteractive\contactformextensions\services\ContactFormExtensionsService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for hidden message keys in emails and exports.
 *
 * @author Hybrid Interactive
 *
 * @since 5.1.2
 */
class MessageKeysTest extends TestCase
{
    /**
     * @return void
     */
    public function testStripControlKeysKeepsSubmittedFields(): void
    {
        $service = new ContactFormExtensionsService();

        $stripped = $service->stripControlKeys([
            'formName' => 'contact',
            'body' => 'Hello',
            'toEmail' => 'hash',
            'confirmationTemplate' => 'hash',
            'confirmationSubject' => 'hash',
            'notificationTemplate' => 'hash',
            'disableRecaptcha' => '1',
            'disableConfirmation' => '1',
            'disableSaveSubmission' => '1',
            'phone' => ['home' => '123'],
        ]);

        self::assertSame([
            'formName' => 'contact',
            'body' => 'Hello',
            'phone' => ['home' => '123'],
        ], $stripped);
    }

    /**
     * @return void
     */
    public function testExportRowDoesNotLetMessageFieldsOverwriteColumns(): void
    {
        $row = (new FlatExporter())->buildRow([
            'id' => 5,
            'uid' => 'abc',
            'form' => 'contact',
            'fromName' => 'Jane',
            'fromEmail' => 'jane@example.com',
            'subject' => 'Hi',
            'isSpam' => false,
            'dateCreated' => '2026-11-13 10:00:00',
            'dateUpdated' => '2026-11-13 10:00:00',
        ], [
            'id' => 'member-42',
            'body' => 'Hello',
            'formName' => 'contact',
            'toEmail' => 'hash',
        ]);

        self::assertSame(5, $row['id']);
        self::assertSame('member-42', $row['message_id']);
        self::assertSame('Hello', $row['body']);
        self::assertArrayNotHasKey('formName', $row);
        self::assertArrayNotHasKey('toEmail', $row);
        self::assertSame(
            ['id', 'uid', 'form', 'fromName', 'fromEmail', 'subject', 'isSpam', 'message_id', 'body', 'dateCreated', 'dateUpdated'],
            array_keys($row)
        );
    }
}
