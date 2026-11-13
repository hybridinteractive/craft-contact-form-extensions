<?php

declare(strict_types=1);

namespace hybridinteractive\contactformextensions\tests\Unit;

use craft\contactform\events\SendEvent;
use craft\contactform\Mailer;
use craft\contactform\models\Submission;
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

        (new ContactFormExtensionsService())->removeControlKeysFromEmail($e);

        self::assertSame($text, $this->_text($e));
    }

    // Private Methods
    // =========================================================================

    /**
     * Builds a send event the way Contact Form does before triggering EVENT_BEFORE_SEND.
     *
     * @param array $message
     *
     * @return SendEvent
     */
    private function _event(array $message): SendEvent
    {
        $mailer = new class() extends Mailer {
            /**
             * Mirrors Contact Form's text body format without needing its plugin settings.
             *
             * @param Submission $submission
             *
             * @return string
             */
            public function compileTextBody(Submission $submission): string
            {
                $fields = (array) $submission->message;
                $body = $fields['body'] ?? '';
                unset($fields['body']);

                $lines = ['- **Name:** Test', '- **Email:** test@example.com'];
                foreach ($fields as $key => $value) {
                    $lines[] = "- **{$key}:** " . (is_array($value) ? implode(', ', $value) : $value);
                }

                return implode("\n", $lines) . "\n\n" . $body;
            }
        };

        $submission = new Submission();
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
