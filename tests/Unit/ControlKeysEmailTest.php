<?php

declare(strict_types=1);

namespace hybridinteractive\contactformextensions\tests\Unit;

use Craft;
use craft\contactform\events\SendEvent;
use craft\contactform\Mailer;
use craft\contactform\models\Submission;
use craft\helpers\ArrayHelper;
use craft\mail\Message;
use hybridinteractive\contactformextensions\services\ContactFormExtensionsService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for removing control keys from the notification email body.
 *
 * @author Hybrid Interactive
 *
 * @since 5.1.2
 */
class ControlKeysEmailTest extends TestCase
{
    // Private Properties
    // =========================================================================

    /**
     * @var array
     */
    private array $_message = [
        'body' => 'Hello',
        'phone' => '555-0100',
        'toEmail' => 'HASHED-TO-EMAIL',
        'disableConfirmation' => '1',
    ];

    // Public Methods
    // =========================================================================

    /**
     * @return void
     */
    public function testStockBodiesLoseControlKeys(): void
    {
        $e = $this->_event($this->_message);

        (new ContactFormExtensionsService())->removeControlKeysFromEmail($e);

        $this->_assertClean($this->_text($e));
        $this->_assertClean($this->_html($e));
        self::assertStringContainsString('**phone:** 555-0100', $this->_text($e));
        self::assertStringContainsString('555-0100', $this->_html($e));
        self::assertArrayHasKey('toEmail', $e->submission->message, 'Submission must keep overrides for later use');
    }

    /**
     * @return void
     */
    public function testCustomHtmlBodyIsKept(): void
    {
        $e = $this->_event($this->_message);
        $e->message->setHtmlBody('<p>CUSTOM HTML</p>');

        (new ContactFormExtensionsService())->removeControlKeysFromEmail($e);

        self::assertSame('<p>CUSTOM HTML</p>', $this->_html($e));
        $this->_assertClean($this->_text($e));
    }

    /**
     * @return void
     */
    public function testDefaultHtmlIsCleanedWhenOnlyTextWasCustomized(): void
    {
        $e = $this->_event($this->_message);
        $e->message->setTextBody('Custom text body');

        (new ContactFormExtensionsService())->removeControlKeysFromEmail($e);

        self::assertSame('Custom text body', $this->_text($e));
        $this->_assertClean($this->_html($e));
        self::assertStringContainsString('555-0100', $this->_html($e));
    }

    /**
     * @return void
     */
    public function testBodiesAreCleanedWhenAnotherListenerChangedTheMessage(): void
    {
        $e = $this->_event($this->_message);
        $e->submission->message = $this->_message + ['ip' => '127.0.0.1'];

        (new ContactFormExtensionsService())->removeControlKeysFromEmail($e);

        $this->_assertClean($this->_text($e));
        $this->_assertClean($this->_html($e));
    }

    /**
     * @return void
     */
    public function testMessagesWithoutControlKeysAreUntouched(): void
    {
        $e = $this->_event(['body' => 'Hello', 'phone' => '555-0100']);
        $text = $this->_text($e);
        $html = $this->_html($e);

        (new ContactFormExtensionsService())->removeControlKeysFromEmail($e);

        self::assertSame($text, $this->_text($e));
        self::assertSame($html, $this->_html($e));
    }

    /**
     * @return void
     */
    public function testEventWithoutMailerSenderIsIgnored(): void
    {
        $e = $this->_event($this->_message);
        $e->sender = null;
        $text = $this->_text($e);
        $html = $this->_html($e);

        (new ContactFormExtensionsService())->removeControlKeysFromEmail($e);

        self::assertSame($text, $this->_text($e));
        self::assertSame($html, $this->_html($e));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function controlValueProvider(): array
    {
        return [
            'string' => ['HASHED-TO-EMAIL'],
            'true' => [true],
            'false' => [false],
            'null' => [null],
            'int' => [5],
            'flat array' => [['HASHED-TO-EMAIL', 'b']],
            'nested array' => [[['HASHED-TO-EMAIL']]],
            'multi-line' => ["HASHED-TO-EMAIL\nsecond"],
            'trailing CRLF' => ["HASHED-TO-EMAIL\r\n"],
        ];
    }

    /**
     * @dataProvider controlValueProvider
     *
     * @param mixed $value
     *
     * @return void
     */
    public function testControlValuesOfAnyShapeAreRemoved(mixed $value): void
    {
        $e = $this->_event(['body' => 'Hello', 'phone' => '555-0100', 'toEmail' => $value]);

        (new ContactFormExtensionsService())->removeControlKeysFromEmail($e);

        foreach ([$this->_text($e), $this->_html($e)] as $body) {
            self::assertStringNotContainsString('toEmail', $body);
            self::assertStringNotContainsString('HASHED-TO-EMAIL', $body);
            self::assertStringContainsString('555-0100', $body);
            self::assertStringContainsString('Hello', $body);
        }
    }

    /**
     * @return void
     */
    public function testControlKeyOnlyMessageKeepsBodyLayout(): void
    {
        $e = $this->_event(['toEmail' => 'HASHED-TO-EMAIL', 'body' => 'Hello']);

        (new ContactFormExtensionsService())->removeControlKeysFromEmail($e);

        self::assertSame("- **Name:** Test\n- **Email:** test@example.com\n\nHello", $this->_text($e));
    }

    /**
     * @return void
     */
    public function testControlKeysExcludedByAllowedMessageFieldsAreANoOp(): void
    {
        $e = $this->_event($this->_message, ['phone']);
        $text = $this->_text($e);
        $html = $this->_html($e);

        (new ContactFormExtensionsService())->removeControlKeysFromEmail($e);

        self::assertSame($text, $this->_text($e));
        self::assertSame($html, $this->_html($e));
    }

    /**
     * @return void
     */
    public function testAppendedTextIsKeptWhileControlLinesAreRemoved(): void
    {
        $e = $this->_event($this->_message);
        $e->message->setTextBody($this->_text($e) . "\n\nIP: 127.0.0.1");

        (new ContactFormExtensionsService())->removeControlKeysFromEmail($e);

        $this->_assertClean($this->_text($e));
        self::assertStringEndsWith("\n\nIP: 127.0.0.1", $this->_text($e));
    }

    // Private Methods
    // =========================================================================

    /**
     * Builds a send event the way Contact Form does before triggering EVENT_BEFORE_SEND.
     *
     * @param array         $message
     * @param string[]|null $allowedMessageFields
     *
     * @return SendEvent
     */
    private function _event(array $message, ?array $allowedMessageFields = null): SendEvent
    {
        $mailer = new class() extends Mailer {
            /**
             * @var string[]|null
             */
            public ?array $allowedMessageFields = null;

            /**
             * Follows Contact Form 3.x `Mailer::compileTextBody()` line for line, with the
             * plugin settings lookup replaced by `$allowedMessageFields`. Nested arrays
             * render as "Array", as PHP's implode() does (without its warning).
             *
             * @param Submission $submission
             *
             * @return string
             */
            public function compileTextBody(Submission $submission): string
            {
                $fields = [];

                if ($submission->fromName) {
                    $fields[Craft::t('contact-form', 'Name')] = $submission->fromName;
                }

                $fields[Craft::t('contact-form', 'Email')] = $submission->fromEmail;

                $messageFields = array_merge((array) $submission->message);
                $body = ArrayHelper::remove($messageFields, 'body', '');
                foreach ($messageFields as $key => $value) {
                    if ($this->allowedMessageFields === null || in_array($key, $this->allowedMessageFields)) {
                        $fields[Craft::t('site', $key)] = $value;
                    }
                }

                $text = '';

                foreach ($fields as $key => $value) {
                    $text .= ($text ? "\n" : '') . "- **{$key}:** ";
                    if (is_array($value)) {
                        $text .= implode(', ', array_map(fn($item) => is_array($item) ? 'Array' : $item, $value));
                    } else {
                        $text .= $value;
                    }
                }

                if ($body !== '') {
                    $body = preg_replace('/\R/u', "\n", $body);
                    $text .= "\n\n" . $body;
                }

                return $text;
            }
        };
        $mailer->allowedMessageFields = $allowedMessageFields;

        $submission = new Submission();
        $submission->fromName = 'Test';
        $submission->fromEmail = 'test@example.com';
        $submission->message = $message;

        $text = $mailer->compileTextBody($submission);
        $email = (new Message())
            ->setTextBody($text)
            ->setHtmlBody($mailer->compileHtmlBody($text));

        return new SendEvent([
            'submission' => $submission,
            'message' => $email,
            'sender' => $mailer,
        ]);
    }

    /**
     * @param SendEvent $e
     *
     * @return string
     */
    private function _text(SendEvent $e): string
    {
        return (string) $e->message->getSymfonyEmail()->getTextBody();
    }

    /**
     * @param SendEvent $e
     *
     * @return string
     */
    private function _html(SendEvent $e): string
    {
        return (string) $e->message->getSymfonyEmail()->getHtmlBody();
    }

    /**
     * @param string $body
     *
     * @return void
     */
    private function _assertClean(string $body): void
    {
        self::assertStringNotContainsString('HASHED-TO-EMAIL', $body);
        self::assertStringNotContainsString('disableConfirmation', $body);
    }
}
