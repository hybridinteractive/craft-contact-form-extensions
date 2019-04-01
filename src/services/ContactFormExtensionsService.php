<?php
/**
 * Craft Contact Form Extensions plugin for Craft CMS 3.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 *
 * @link      https://rias.be
 *
 * @copyright Copyright (c) 2018 Rias
 */

namespace rias\contactformextensions\services;

use Craft;
use craft\base\Component;
use craft\contactform\models\Submission;
use craft\helpers\StringHelper;
use rias\contactformextensions\ContactFormExtensions;
use rias\contactformextensions\elements\ContactFormSubmission;
use rias\contactformextensions\models\RecaptchaV3;
use yii\base\Exception;

/**
 * CraftContactFormExtensionsService Service.
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Rias
 *
 * @since     1.0.0
 */
class ContactFormExtensionsService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want.
     *
     * From any other plugin file, call it like this:
     *
     *     CraftContactFormExtensions::$plugin->craftContactFormExtensionsService->exampleService()
     *
     * @param Submission $submission
     *
     * @throws Exception
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     *
     * @return mixed
     */
    public function saveSubmission(Submission $submission)
    {
        $contactFormSubmission = new ContactFormSubmission();
        $contactFormSubmission->form = $submission->message['formName'] ?? 'contact';
        $contactFormSubmission->fromName = $submission->fromName;
        $contactFormSubmission->fromEmail = $submission->fromEmail;
        $contactFormSubmission->subject = $submission->subject;

        if (!is_array($submission->message)) {
            $submission->message = ['message' => $this->utf8Value($submission->message)];
        }

        $message = $this->utf8AllTheThings($submission->message);
        $contactFormSubmission->message = json_encode($message);

        if (Craft::$app->elements->saveElement($contactFormSubmission)) {
            return $contactFormSubmission;
        }

        throw new Exception(json_encode($contactFormSubmission->errors));
    }

    public function getRecaptcha()
    {
        $siteKey = Craft::parseEnv(ContactFormExtensions::$plugin->settings->recaptchaSiteKey);
        $secretKey = Craft::parseEnv(ContactFormExtensions::$plugin->settings->recaptchaSecretKey);

        if (ContactFormExtensions::$plugin->settings->recaptchaVersion === '3') {
            $recaptcha = new RecaptchaV3(
                $siteKey,
                $secretKey,
                ContactFormExtensions::$plugin->settings->recaptchaThreshold,
                ContactFormExtensions::$plugin->settings->recaptchaTimeout,
                ContactFormExtensions::$plugin->settings->recaptchaHideBadge
            );

            return $recaptcha;
        }

        $options = [
            'hideBadge' => ContactFormExtensions::$plugin->settings->recaptchaHideBadge,
            'dataBadge' => ContactFormExtensions::$plugin->settings->recaptchaDataBadge,
            'timeout'   => ContactFormExtensions::$plugin->settings->recaptchaTimeout,
            'debug'     => ContactFormExtensions::$plugin->settings->recaptchaDebug,
        ];

        return new \AlbertCht\InvisibleReCaptcha\InvisibleReCaptcha($siteKey, $secretKey, $options);
    }

    /**
     * @param array $things
     *
     * @return array
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
     *
     * @return array|string
     */
    public function utf8Value($value)
    {
        if (is_array($value)) {
            return $this->utf8AllTheThings($value);
        }

        return StringHelper::convertToUtf8($value);
    }
}
