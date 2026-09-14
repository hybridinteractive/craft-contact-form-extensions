<?php

/**
 * Contact Form Extensions plugin for Craft CMS 5.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions\variables;

use Craft;
use craft\elements\db\ElementQueryInterface;
use hybridinteractive\contactformextensions\ContactFormExtensions;
use hybridinteractive\contactformextensions\elements\Submission;
use hybridinteractive\contactformextensions\models\RecaptchaV3;
use hybridinteractive\contactformextensions\models\Settings;

/**
 * Twig variable for Contact Form Extensions.
 *
 * @author Hybrid Interactive
 *
 * @since 5.0.0
 */
class ContactFormExtensionsVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @return string|null
     *
     * @author Hybrid Interactive
     *
     * @since 5.0.0
     */
    public function name(): ?string
    {
        return ContactFormExtensions::$plugin->name;
    }

    /**
     * @return Settings
     *
     * @author Hybrid Interactive
     *
     * @since 5.1.0
     */
    public function settings(): Settings
    {
        /** @var Settings $settings */
        $settings = ContactFormExtensions::$plugin->getSettings();

        return $settings;
    }

    /**
     * @param string|null $localeOrAction
     *
     * @return string
     *
     * @author Hybrid Interactive
     *
     * @since 5.0.0
     */
    public function recaptcha(?string $localeOrAction = null): string
    {
        /** @var Settings $settings */
        $settings = ContactFormExtensions::$plugin->getSettings();

        if (!$settings->recaptcha) {
            return '';
        }

        $recaptcha = ContactFormExtensions::$plugin->contactFormExtensionsService->getRecaptcha();
        if ($recaptcha instanceof RecaptchaV3) {
            return $recaptcha->render($localeOrAction ?: 'homepage');
        }

        return $recaptcha->render($localeOrAction);
    }

    /**
     * @param array $criteria
     *
     * @return ElementQueryInterface
     *
     * @author Hybrid Interactive
     *
     * @since 5.0.0
     */
    public function submissions(array $criteria = []): ElementQueryInterface
    {
        $query = Submission::find();

        if (!empty($criteria)) {
            Craft::configure($query, $criteria);
        }

        return $query;
    }
}
