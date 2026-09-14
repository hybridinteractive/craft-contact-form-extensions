<?php

declare(strict_types=1);

namespace hybridinteractive\contactformextensions\tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Smoke tests for Submission element source building.
 *
 * @author Hybrid Interactive
 *
 * @since 5.1.0
 */
class SubmissionSourcesTest extends TestCase
{
    /**
     * @return void
     */
    public function testDefineSourcesUsesDistinctQuery(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/src/elements/Submission.php');

        self::assertIsString($source);
        self::assertStringNotContainsString('self::find()->all()', $source);
        self::assertStringContainsString('->distinct()', $source);
        self::assertStringContainsString('contactform_submissions', $source);
    }
}
