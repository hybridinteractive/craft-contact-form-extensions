<?php

declare(strict_types=1);

namespace hybridinteractive\contactformextensions\tests\Unit;

use hybridinteractive\contactformextensions\exporters\FlatExporter;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for message keys in the Flat exporter.
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
        self::assertSame('contact', $row['formName']);
        self::assertArrayNotHasKey('toEmail', $row);
        self::assertSame(
            ['id', 'uid', 'form', 'fromName', 'fromEmail', 'subject', 'isSpam', 'message_id', 'body', 'formName', 'dateCreated', 'dateUpdated'],
            array_keys($row)
        );
    }

    /**
     * @return void
     */
    public function testExportRowPrefixesTrailingAndRepeatedCollisions(): void
    {
        $row = (new FlatExporter())->buildRow([
            'id' => 5,
            'dateCreated' => '2026-11-13 10:00:00',
        ], [
            'dateCreated' => 'yesterday',
            'id' => 'first',
            'message_id' => 'second',
        ]);

        self::assertSame(5, $row['id']);
        self::assertSame('2026-11-13 10:00:00', $row['dateCreated']);
        self::assertSame('yesterday', $row['message_dateCreated']);
        self::assertSame('first', $row['message_id']);
        self::assertSame('second', $row['message_message_id']);
    }
}
