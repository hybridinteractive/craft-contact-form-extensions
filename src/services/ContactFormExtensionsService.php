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
use rias\contactformextensions\ContactFormExtensions;
use rias\contactformextensions\elements\ContactFormSubmission;
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
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     * @throws \yii\base\ExitException
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
        $contactFormSubmission->message = json_encode($submission->message);

        if (Craft::$app->elements->saveElement($contactFormSubmission)) {
            return $contactFormSubmission;
        }

        throw new Exception(json_encode($contactFormSubmission->errors));
    }

    public function getRecaptcha()
    {
        $siteKey = ContactFormExtensions::$plugin->settings->recaptchaSiteKey;
        $secretKey = ContactFormExtensions::$plugin->settings->recaptchaSecretKey;
        $options = [
            'hideBadge' => ContactFormExtensions::$plugin->settings->recaptchaHideBadge,
            'dataBadge' => ContactFormExtensions::$plugin->settings->recaptchaDataBadge,
            'timeout'   => ContactFormExtensions::$plugin->settings->recaptchaTimeout,
            'debug'     => ContactFormExtensions::$plugin->settings->recaptchaDebug,
        ];

        return new \AlbertCht\InvisibleReCaptcha\InvisibleReCaptcha($siteKey, $secretKey, $options);
    }
}
