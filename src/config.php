<?php

/**
 * Craft Contact Form Extensions plugin for Craft CMS 3.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

/**
 * Craft Contact Form Extensions config.php.
 *
 * This file exists only as a template for the Craft Contact Form Extensions settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'contact-form-extensions.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    'enableDatabase'          => true,
    'enableConfirmationEmail' => true,
    'enableTemplateOverwrite' => true,
    'notificationTemplate'    => '',
    'confirmationTemplate'    => '',
    'confirmationSubject'     => '',

    'recaptcha'               => false,
    'enableRecaptchaOverride' => false,
    'recaptchaUrl'            => '',
    'recaptchaVerificationUrl'=> '',
    'recaptchaVersion'        => '',
    'recaptchaSiteKey'        => '',
    'recaptchaSecretKey'      => '',
    'recaptchaHideBadge'      => false,
    'recaptchaDataBadge'      => 'bottomright',
    'recaptchaTimeout'        => 5,
    'recaptchaThreshold'      => .5,
    'recaptchaDebug'          => false,
];
