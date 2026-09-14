<?php

declare(strict_types=1);

namespace hybridinteractive\contactformextensions\tests\Unit;

use hybridinteractive\contactformextensions\models\RecaptchaV2;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RecaptchaV2.
 *
 * @author Hybrid Interactive
 *
 * @since 5.1.0
 */
class RecaptchaV2Test extends TestCase
{
    /**
     * @return void
     */
    public function testEmptyResponseIsRejected(): void
    {
        $recaptcha = new RecaptchaV2(
            'site',
            'secret',
            'https://www.google.com/recaptcha/api.js',
            'https://www.google.com/recaptcha/api/siteverify',
            false,
            'bottomright',
            5,
            false
        );

        self::assertFalse($recaptcha->verifyResponse(null));
        self::assertFalse($recaptcha->verifyResponse(''));
    }

    /**
     * @return void
     */
    public function testRenderIncludesCdnjsPolyfill(): void
    {
        $recaptcha = new RecaptchaV2(
            'site-key',
            'secret',
            'https://www.google.com/recaptcha/api.js',
            'https://www.google.com/recaptcha/api/siteverify',
            true,
            'bottomright',
            5,
            false
        );

        $html = $recaptcha->render();

        self::assertStringContainsString('cdnjs.cloudflare.com/polyfill', $html);
        self::assertStringContainsString('site-key', $html);
        self::assertStringContainsString('render=explicit', $html);
        self::assertStringNotContainsString('polyfill.io', $html);
        self::assertMatchesRegularExpression('/id="_g-recaptcha[a-f0-9]+"/', $html);
        self::assertMatchesRegularExpression('/grecaptcha\.execute\(widgetId\)/', $html);

        $second = $recaptcha->render();
        preg_match('/id="(_g-recaptcha[a-f0-9]+)"/', $html, $firstId);
        preg_match('/id="(_g-recaptcha[a-f0-9]+)"/', $second, $secondId);
        self::assertNotSame($firstId[1], $secondId[1]);
    }
}
