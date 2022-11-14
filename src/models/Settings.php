<?php
/**
 * Craft Contact Form Extensions plugin for Craft CMS 4.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions\models;

use Craft;
use craft\base\Model;

/**
 * CraftContactFormExtensions Settings Model.
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var bool
     */
    public $enableDatabase = true;

    /**
     * @var bool
     */
    public $enableTemplateOverwrite = true;

    /**
     * @var bool
     */
    public $enableConfirmationEmail = true;

    /**
     * @var string|null
     */
    public $notificationTemplate = '';

    /**
     * @var string|null
     */
    public $confirmationTemplate = '';

    /**
     * @var string|null
     */
    public $confirmationSubject = '';

    /**
     * @var bool
     */
    public $recaptcha = false;

    /**
     * @var bool
     */
    public $enableRecaptchaOverride = false;

    /**
     * @var string|null
     */
    public $recaptchaUrl = '';

    /**
     * @var string|null
     */
    public $recaptchaVerificationUrl = '';

    /**
     * @var string|null
     */
    public $recaptchaVersion = '';

    /**
     * @var string|null
     */
    public $recaptchaSiteKey = '';

    /**
     * @var string|null
     */
    public $recaptchaSecretKey = '';

    /**
     * @var bool
     */
    public $recaptchaHideBadge = false;

    /**
     * @var string
     */
    public $recaptchaDataBadge = 'bottomright';

    /**
     * @var int
     */
    public $recaptchaTimeout = 5;

    /**
     * @var bool
     */
    public $recaptchaDebug = false;

    /**
     * @var int
     */
    public $recaptchaThreshold = 0.5;

    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public function getConfirmationSubject(): string
    {
        if (is_array($this->confirmationSubject)) {
            return $this->confirmationSubject[Craft::$app->sites->currentSite->handle];
        }

        return $this->confirmationSubject;
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        return [
            [['enableDatabase', 'enableTemplateOverwrite', 'enableConfirmationEmail', 'recaptcha', 'enableRecaptchaOverride', 'recaptchaHideBadge', 'recaptchaDebug'], 'boolean'],

            [['notificationTemplate', 'confirmationTemplate', 'confirmationSubject', 'recaptchaUrl', 'recaptchaVerificationUrl', 'recaptchaSiteKey', 'recaptchaSecretKey', 'recaptchaDataBadge'], 'string'],

            ['recaptchaTimeout', 'integer'],
            ['recaptchaThreshold', 'double', 'max' => 1, 'min' => 0],

            [['confirmationTemplate', 'confirmationSubject'], 'required', 'when' => static function ($model) {
                return $model->enableConfirmationEmail == true;
            }],

            ['notificationTemplate', 'required', 'when' => static function ($model) {
                return $model->enableTemplateOverwrite == true;
            }],

            [['recaptchaSiteKey', 'recaptchaSecretKey'], 'required', 'when' => static function ($model) {
                return $model->recaptcha == true;
            }],

            [['recaptchaUrl', 'recaptchaVerificationUrl'], 'required', 'when' => static function ($model) {
                return $model->enableRecaptchaOverride == true;
            }],

        ];
    }
}
