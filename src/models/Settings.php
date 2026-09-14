<?php

/**
 * Contact Form Extensions plugin for Craft CMS 5.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions\models;

use Craft;
use craft\base\Model;

/**
 * Settings model for Contact Form Extensions.
 *
 * @author Hybrid Interactive
 *
 * @since 5.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var bool
     */
    public bool $enableDatabase = true;

    /**
     * @var bool
     */
    public bool $enableTemplateOverwrite = true;

    /**
     * @var bool
     */
    public bool $enableConfirmationEmail = true;

    /**
     * When true, submissions marked as spam are still saved so they can be reviewed in the CP.
     *
     * @var bool
     */
    public bool $enableSaveSpam = false;

    /**
     * @var string|null
     */
    public ?string $notificationTemplate = '';

    /**
     * @var string|null
     */
    public ?string $confirmationTemplate = '';

    /**
     * @var string|array|null
     */
    public string|array|null $confirmationSubject = '';

    /**
     * @var bool
     */
    public bool $recaptcha = false;

    /**
     * @var bool
     */
    public bool $enableRecaptchaOverride = false;

    /**
     * @var string|null
     */
    public ?string $recaptchaUrl = '';

    /**
     * @var string|null
     */
    public ?string $recaptchaVerificationUrl = '';

    /**
     * @var string|null
     */
    public ?string $recaptchaVersion = '';

    /**
     * @var string|null
     */
    public ?string $recaptchaSiteKey = '';

    /**
     * @var string|null
     */
    public ?string $recaptchaSecretKey = '';

    /**
     * @var bool
     */
    public bool $recaptchaHideBadge = false;

    /**
     * @var string
     */
    public string $recaptchaDataBadge = 'bottomright';

    /**
     * @var int
     */
    public int $recaptchaTimeout = 5;

    /**
     * @var bool
     */
    public bool $recaptchaDebug = false;

    /**
     * @var float
     */
    public float $recaptchaThreshold = 0.5;

    // Public Methods
    // =========================================================================

    /**
     * Returns the confirmation subject for the current site.
     *
     * @return string
     *
     * @author Hybrid Interactive
     *
     * @since 5.0.0
     */
    public function getConfirmationSubject(): string
    {
        if (is_array($this->confirmationSubject)) {
            $handle = Craft::$app->getSites()->getCurrentSite()->handle;

            return (string) ($this->confirmationSubject[$handle] ?? '');
        }

        return (string) $this->confirmationSubject;
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['enableDatabase', 'enableTemplateOverwrite', 'enableConfirmationEmail', 'enableSaveSpam', 'recaptcha', 'enableRecaptchaOverride', 'recaptchaHideBadge', 'recaptchaDebug'], 'boolean'],
            [['notificationTemplate', 'confirmationTemplate', 'recaptchaUrl', 'recaptchaVerificationUrl', 'recaptchaSiteKey', 'recaptchaSecretKey', 'recaptchaDataBadge', 'recaptchaVersion'], 'string'],
            ['recaptchaTimeout', 'integer'],
            ['recaptchaThreshold', 'double', 'max' => 1, 'min' => 0],
            [['confirmationTemplate', 'confirmationSubject'], 'required', 'when' => static function ($model) {
                return $model->enableConfirmationEmail === true;
            }],
            ['notificationTemplate', 'required', 'when' => static function ($model) {
                return $model->enableTemplateOverwrite === true;
            }],
            [['recaptchaSiteKey', 'recaptchaSecretKey'], 'required', 'when' => static function ($model) {
                return $model->recaptcha === true;
            }],
            [['recaptchaUrl', 'recaptchaVerificationUrl'], 'required', 'when' => static function ($model) {
                return $model->enableRecaptchaOverride === true;
            }],
        ]);
    }
}
