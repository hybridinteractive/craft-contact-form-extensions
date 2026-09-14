<?php

/**
 * Contact Form Extensions plugin for Craft CMS 5.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions;

use Craft;
use craft\base\Plugin;
use craft\contactform\events\SendEvent as CraftContactFormSendEvent;
use craft\contactform\Mailer as CraftContactFormMailer;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\TemplateEvent;
use craft\helpers\App;
use craft\mail\Message;
use craft\services\Plugins;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use craft\web\View;
use hybridinteractive\contactformextensions\base\Routes;
use hybridinteractive\contactformextensions\controllers\SubmissionsController;
use hybridinteractive\contactformextensions\controllers\ToolsController;
use hybridinteractive\contactformextensions\models\Settings;
use hybridinteractive\contactformextensions\services\ContactFormExtensionsService;
use hybridinteractive\contactformextensions\variables\ContactFormExtensionsVariable;
use yii\base\Event;

/**
 * Contact Form Extensions plugin.
 *
 * @property-read Settings $settings
 * @property-read ContactFormExtensionsService $contactFormExtensionsService
 *
 * @method Settings getSettings()
 *
 * @author Hybrid Interactive
 *
 * @since 5.0.0
 */
class ContactFormExtensions extends Plugin
{
    // Traits
    // =========================================================================

    use Routes;

    // Static Properties
    // =========================================================================

    /**
     * @var ContactFormExtensions
     */
    public static ContactFormExtensions $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string|null
     */
    public ?string $name = null;

    /**
     * @inheritdoc
     */
    public bool $hasCpSettings = true;

    /**
     * @inheritdoc
     */
    public bool $hasCpSection = true;

    /**
     * @inheritdoc
     */
    public string $schemaVersion = '1.1.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->controllerNamespace = 'hybridinteractive\\contactformextensions\\controllers';

        $this->_registerVariable();
        $this->_registerContactFormEventListeners();
        $this->_registerSettings();
        $this->_registerCraftContactFormCheck();
        $this->_registerPermissions();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpRoutes();
        }
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): ?array
    {
        /** @var Settings $settings */
        $settings = $this->getSettings();
        if (!$settings->enableDatabase) {
            return null;
        }

        $nav = parent::getCpNavItem();
        $nav['label'] = Craft::t('contact-form-extensions', 'Form Submissions');
        $nav['fontIcon'] = 'envelope';
        $nav['subnav'] = [
            'submissions' => [
                'label' => Craft::t('contact-form-extensions', 'Submissions'),
                'url'   => 'contact-form-extensions',
            ],
            'tools' => [
                'label' => Craft::t('contact-form-extensions', 'Tools'),
                'url'   => 'contact-form-extensions/tools',
            ],
        ];

        return $nav;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?Settings
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        $settings = $this->getSettings();
        $settings->validate();

        /** @var \craft\web\Application $app */
        $app = Craft::$app;
        $overrides = $app->getConfig()->getConfigFromFile(strtolower($this->handle));

        return $app->getView()->renderTemplate('contact-form-extensions/_settings', [
            'settings'  => $settings,
            'overrides' => array_keys($overrides),
            'readOnly'  => !Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
        ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * @return void
     */
    private function _registerSettings(): void
    {
        Event::on(View::class, View::EVENT_BEFORE_RENDER_TEMPLATE, function (TemplateEvent $e) {
            if (
                $e->template === 'settings/plugins/_settings.twig' &&
                isset($e->variables['plugin']) &&
                $e->variables['plugin']->name === 'Contact Form Extensions'
            ) {
                $e->variables['tabs'] = [
                    ['label' => 'Settings', 'url' => '#settings-tab-settings'],
                    ['label' => 'reCAPTCHA', 'url' => '#settings-tab-recaptcha'],
                ];
            }
        });
    }

    /**
     * @return void
     */
    private function _registerVariable(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function (Event $event) {
            /** @var CraftVariable $variable */
            $variable = $event->sender;
            $variable->set('contactFormExtensions', ContactFormExtensionsVariable::class);
        });
    }

    /**
     * @return void
     */
    private function _registerPermissions(): void
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading'     => Craft::t('contact-form-extensions', 'Contact Form Extensions'),
                    'permissions' => [
                        SubmissionsController::PERMISSION_VIEW_SUBMISSIONS => [
                            'label' => Craft::t('contact-form-extensions', 'View form submissions'),
                        ],
                        ToolsController::PERMISSION_DELETE_SUBMISSIONS => [
                            'label' => Craft::t('contact-form-extensions', 'Delete form submissions'),
                        ],
                    ],
                ];
            }
        );
    }

    /**
     * Registers Contact Form mailer listeners after all plugins have loaded
     * so other spam plugins can mark submissions first.
     *
     * @return void
     */
    private function _registerContactFormEventListeners(): void
    {
        Event::on(Plugins::class, Plugins::EVENT_AFTER_LOAD_PLUGINS, function () {
            Event::on(CraftContactFormMailer::class, CraftContactFormMailer::EVENT_BEFORE_SEND, function (CraftContactFormSendEvent $e) {
                /** @var Settings $settings */
                $settings = $this->getSettings();
                /** @var \craft\web\Application|\craft\console\Application $app */
                $app = Craft::$app;

                if (!$e->isSpam) {
                    $disableRecaptcha = false;
                    if (is_array($e->submission->message) && array_key_exists('disableRecaptcha', $e->submission->message)) {
                        $disableRecaptcha = filter_var($e->submission->message['disableRecaptcha'], FILTER_VALIDATE_BOOLEAN);
                    }

                    if ($settings->recaptcha && $disableRecaptcha !== true) {
                        $recaptcha = $this->contactFormExtensionsService->getRecaptcha();
                        $captchaResponse = $app->getRequest()->getParam('g-recaptcha-response');
                        $remoteIp = $app->getRequest()->getUserIP() ?? '';

                        if (!$recaptcha->verifyResponse($captchaResponse, $remoteIp)) {
                            $e->isSpam = true;
                            $e->handled = true;
                        }
                    }
                }

                $disableSaveSubmission = false;
                if (is_array($e->submission->message) && array_key_exists('disableSaveSubmission', $e->submission->message)) {
                    $disableSaveSubmission = filter_var($e->submission->message['disableSaveSubmission'], FILTER_VALIDATE_BOOLEAN);
                }

                $shouldSave = $settings->enableDatabase && $disableSaveSubmission !== true;
                if ($shouldSave && (!$e->isSpam || $settings->enableSaveSpam)) {
                    $this->contactFormExtensionsService->saveSubmission($e->submission, (bool) $e->isSpam);
                }

                if ($e->isSpam) {
                    return;
                }

                if (is_array($e->submission->message) && array_key_exists('toEmail', $e->submission->message)) {
                    $email = Craft::$app->getSecurity()->validateData($e->submission->message['toEmail']);
                    $e->toEmails = explode(',', (string) $email);
                }

                if ($settings->enableTemplateOverwrite) {
                    $app->getView()->setTemplateMode(View::TEMPLATE_MODE_SITE);

                    if (is_array($e->submission->message) && array_key_exists('notificationTemplate', $e->submission->message)) {
                        $template = '_emails/'.Craft::$app->getSecurity()->validateData($e->submission->message['notificationTemplate']);
                    } else {
                        $template = App::parseEnv($settings->notificationTemplate);
                    }

                    $html = $app->getView()->renderTemplate(
                        (string) $template,
                        ['submission' => $e->submission]
                    );

                    $e->message->setHtmlBody($html);

                    if ($app->getRequest()->getIsCpRequest()) {
                        $app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
                    }
                }
            });

            Event::on(CraftContactFormMailer::class, CraftContactFormMailer::EVENT_AFTER_SEND, function (CraftContactFormSendEvent $e) {
                /** @var Settings $settings */
                $settings = $this->getSettings();
                /** @var \craft\web\Application|\craft\console\Application $app */
                $app = Craft::$app;

                $disableConfirmation = false;
                if (is_array($e->submission->message) && array_key_exists('disableConfirmation', $e->submission->message)) {
                    $disableConfirmation = filter_var($e->submission->message['disableConfirmation'], FILTER_VALIDATE_BOOLEAN);
                }

                if (!$settings->enableConfirmationEmail || $disableConfirmation === true) {
                    return;
                }

                $app->getView()->setTemplateMode(View::TEMPLATE_MODE_SITE);

                if (is_array($e->submission->message) && array_key_exists('confirmationTemplate', $e->submission->message)) {
                    $template = '_emails/'.Craft::$app->getSecurity()->validateData($e->submission->message['confirmationTemplate']);
                } else {
                    $template = App::parseEnv($settings->confirmationTemplate);
                }

                $html = $app->getView()->renderTemplate(
                    (string) $template,
                    ['submission' => $e->submission]
                );

                $message = new Message();
                $message->setTo($e->submission->fromEmail);

                $mailer = $app->getMailer();
                if (isset($mailer->from)) {
                    $message->setFrom($mailer->from);
                } else {
                    $message->setFrom($e->message->getTo());
                }

                $message->setHtmlBody($html);

                if (is_array($e->submission->message) && array_key_exists('confirmationSubject', $e->submission->message)) {
                    $confirmationSubject = Craft::$app->getSecurity()->validateData($e->submission->message['confirmationSubject']);
                } else {
                    $confirmationSubject = App::parseEnv($settings->getConfirmationSubject());
                }
                $message->setSubject((string) $confirmationSubject);

                $app->getMailer()->send($message);

                if ($app->getRequest()->getIsCpRequest()) {
                    $app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
                }
            });
        });
    }

    /**
     * @return void
     */
    private function _registerCraftContactFormCheck(): void
    {
        /** @var \craft\web\Application|\craft\console\Application $app */
        $app = Craft::$app;

        if (!$app->getPlugins()->isPluginInstalled('contact-form') && !$app->getRequest()->getIsConsoleRequest()) {
            $app->getSession()->setNotice(Craft::t(
                'contact-form-extensions',
                'The Contact Form plugin is not installed or activated, Contact Form Extensions does not work without it.'
            ));
        }
    }
}
