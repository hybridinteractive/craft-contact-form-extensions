<?php
/**
 * Craft Contact Form Extensions plugin for Craft CMS 3.x.
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

    public $enableTemplateOverwrite = true;
    public $enableConfirmationEmail = true;
    public $notificationTemplate = '';
    public $confirmationTemplate = '';
    public $confirmationSubject = '';

    public $recaptcha = false;
    public $enableRecaptchaOverride = false;
    public $recaptchaUrl = '';
    public $recaptchaVerificationUrl = '';
    public $recaptchaVersion = '';
    public $recaptchaSiteKey = '';
    public $recaptchaSecretKey = '';
    public $recaptchaHideBadge = false;
    public $recaptchaDataBadge = 'bottomright';
    public $recaptchaTimeout = 5;
    public $recaptchaDebug = false;
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
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
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
