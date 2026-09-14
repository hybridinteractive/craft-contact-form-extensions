<?php

/**
 * Contact Form Extensions plugin for Craft CMS 5.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions\variables;

use Craft;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\App;
use hybridinteractive\contactformextensions\ContactFormExtensions;
use hybridinteractive\contactformextensions\controllers\SubmissionsController;
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
     * Returns public (non-secret) settings safe for site templates.
     *
     * @return array{
     *     enableDatabase: bool,
     *     enableConfirmationEmail: bool,
     *     enableTemplateOverwrite: bool,
     *     enableSaveSpam: bool,
     *     recaptcha: bool,
     *     recaptchaVersion: string|null,
     *     recaptchaSiteKey: string|null,
     *     recaptchaHideBadge: bool,
     *     recaptchaDataBadge: string,
     *     recaptchaThreshold: float
     * }
     *
     * @author Hybrid Interactive
     *
     * @since 5.1.0
     */
    public function settings(): array
    {
        /** @var Settings $settings */
        $settings = ContactFormExtensions::$plugin->getSettings();

        return [
            'enableDatabase' => $settings->enableDatabase,
            'enableConfirmationEmail' => $settings->enableConfirmationEmail,
            'enableTemplateOverwrite' => $settings->enableTemplateOverwrite,
            'enableSaveSpam' => $settings->enableSaveSpam,
            'recaptcha' => $settings->recaptcha,
            'recaptchaVersion' => $settings->recaptchaVersion,
            'recaptchaSiteKey' => App::parseEnv($settings->recaptchaSiteKey),
            'recaptchaHideBadge' => $settings->recaptchaHideBadge,
            'recaptchaDataBadge' => $settings->recaptchaDataBadge,
            'recaptchaThreshold' => $settings->recaptchaThreshold,
        ];
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

        // Site templates are trusted by the project owner; CP queries still require view permission.
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $user = Craft::$app->getUser()->getIdentity();
            if ($user === null || !$user->can(SubmissionsController::PERMISSION_VIEW_SUBMISSIONS)) {
                return $query->id(false);
            }
        }

        if (!empty($criteria)) {
            Craft::configure($query, $criteria);
        }

        return $query;
    }
}
