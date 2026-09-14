<?php
/**
 * Contact Form Extensions plugin for Craft CMS 5.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions\services;

use Craft;
use craft\base\Component;
use craft\contactform\models\Submission as CraftContactFormSubmission;
use craft\helpers\App;
use craft\helpers\StringHelper;
use hybridinteractive\contactformextensions\ContactFormExtensions;
use hybridinteractive\contactformextensions\elements\Submission;
use hybridinteractive\contactformextensions\models\RecaptchaV2;
use hybridinteractive\contactformextensions\models\RecaptchaV3;
use hybridinteractive\contactformextensions\models\Settings;
use yii\base\Exception;

/**
 * Contact Form Extensions service.
 *
 * @author Hybrid Interactive
 * @since 5.0.0
 */
class ContactFormExtensionsService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Saves a Craft Contact Form submission as a CFE Submission element.
     *
     * @param CraftContactFormSubmission $submission
     * @param bool $isSpam
     * @return Submission
     * @throws Exception
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\InvalidConfigException
     *
     * @author Hybrid Interactive
     * @since 5.0.0
     */
    public function saveSubmission(CraftContactFormSubmission $submission, bool $isSpam = false): Submission
    {
        $contactFormSubmission = new Submission();
        $contactFormSubmission->form = $submission->message['formName'] ?? 'contact';
        $contactFormSubmission->fromName = $submission->fromName;
        $contactFormSubmission->fromEmail = $submission->fromEmail;
        $contactFormSubmission->subject = $submission->subject;
        $contactFormSubmission->isSpam = $isSpam;

        if (!is_array($submission->message)) {
            $submission->message = ['message' => $this->utf8Value($submission->message)];
        }

        $message = $this->utf8AllTheThings($submission->message);
        $contactFormSubmission->message = json_encode($message);

        if (Craft::$app->getElements()->saveElement($contactFormSubmission)) {
            return $contactFormSubmission;
        }

        throw new Exception(json_encode($contactFormSubmission->errors));
    }

    /**
     * Returns a RecaptchaV2 or RecaptchaV3 instance based on settings.
     *
     * @return RecaptchaV2|RecaptchaV3
     * @throws \yii\base\InvalidConfigException
     *
     * @author Hybrid Interactive
     * @since 5.0.0
     */
    public function getRecaptcha(): RecaptchaV2|RecaptchaV3
    {
        /** @var Settings $settings */
        $settings = ContactFormExtensions::$plugin->getSettings();

        $siteKey = App::parseEnv($settings->recaptchaSiteKey);
        $secretKey = App::parseEnv($settings->recaptchaSecretKey);

        $recaptchaUrl = 'https://www.google.com/recaptcha/api.js';
        $recaptchaVerificationUrl = 'https://www.google.com/recaptcha/api/siteverify';

        if ($settings->enableRecaptchaOverride === true) {
            $recaptchaUrl = App::parseEnv($settings->recaptchaUrl);
            $recaptchaVerificationUrl = App::parseEnv($settings->recaptchaVerificationUrl);
        }

        if ($settings->recaptchaVersion === '3') {
            return new RecaptchaV3(
                (string)$siteKey,
                (string)$secretKey,
                (string)$recaptchaUrl,
                (string)$recaptchaVerificationUrl,
                (float)$settings->recaptchaThreshold,
                (int)$settings->recaptchaTimeout,
                (bool)$settings->recaptchaHideBadge
            );
        }

        return new RecaptchaV2(
            (string)$siteKey,
            (string)$secretKey,
            (string)$recaptchaUrl,
            (string)$recaptchaVerificationUrl,
            (bool)$settings->recaptchaHideBadge,
            (string)$settings->recaptchaDataBadge,
            (int)$settings->recaptchaTimeout,
            (bool)$settings->recaptchaDebug
        );
    }

    /**
     * @param array $things
     * @return array
     *
     * @author Hybrid Interactive
     * @since 5.0.0
     */
    public function utf8AllTheThings(array $things): array
    {
        foreach ($things as $key => $value) {
            $things[$key] = $this->utf8Value($value);
        }

        return $things;
    }

    /**
     * @param array|string $value
     * @return array|string
     *
     * @author Hybrid Interactive
     * @since 5.0.0
     */
    public function utf8Value(array|string $value): array|string
    {
        if (is_array($value)) {
            return $this->utf8AllTheThings($value);
        }

        return StringHelper::convertToUtf8($value);
    }
}
