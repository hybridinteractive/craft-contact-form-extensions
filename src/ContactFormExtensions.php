<?php
/**
 * Craft Contact Form Extensions plugin for Craft CMS 4.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions;

use Craft;
use craft\base\Plugin;
use craft\contactform\events\SendEvent as CraftContactFormSendEvent;
use craft\contactform\Mailer as CraftContactFormMailer;
use craft\events\TemplateEvent;
use craft\helpers\App;
use craft\mail\Message;
use craft\web\twig\variables\CraftVariable;
use craft\web\View;
use hybridinteractive\contactformextensions\base\Routes;
use hybridinteractive\contactformextensions\models\Settings;
use hybridinteractive\contactformextensions\variables\ContactFormExtensionsVariable;
use yii\base\Event;

/**
 * Class ContactFormExtensions.
 */
class ContactFormExtensions extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * ContactFormExtensions::$plugin.
     *
     * @var ContactFormExtensions
     */
    public static $plugin;

    public ?string $name;

    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;
    public string $schemaVersion = '1.0.1';

    // Traits
    // =========================================================================

    use Routes;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * CraftContactFormExtensions::$plugin.
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     */
    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_registerVariable();
        $this->_registerContactFormEventListeners();
        $this->_registerSettings();
        $this->_registerCraftContactFormCheck();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpRoutes();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCpNavItem(): ?array
    {
        if (!$this->settings->enableDatabase) {
            return null;
        }

        $nav = parent::getCpNavItem();

        $nav['label'] = Craft::t('contact-form-extensions', 'Form Submissions');

        // $nav['subnav']['submissions'] = [
        //     'label' => Craft::t('contact-form-extensions', 'Submissions'),
        //     'url' => 'contact-form-extensions/',
        // ];

        // if (Craft::$app->getUser()->getIsAdmin()) {
        //     $nav['subnav']['settings'] = [
        //         'label' => Craft::t('contact-form-extensions', 'Settings'),
        //         'url' => 'contact-form-extensions/settings',
        //     ];
        // }

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
        // Get and pre-validate the settings
        $settings = $this->getSettings();
        $settings->validate();

        // Get the settings that are being defined by the config file
        $overrides = Craft::$app->getConfig()->getConfigFromFile(strtolower($this->handle));

        return Craft::$app->view->renderTemplate('contact-form-extensions/_settings', [
            'settings'  => $settings,
            'overrides' => array_keys($overrides),
        ]);
    }

    // Private Methods
    // =========================================================================

    private function _registerSettings(): void
    {
        // Settings Template
        Event::on(View::class, View::EVENT_BEFORE_RENDER_TEMPLATE, function (TemplateEvent $e) {
            if (
                $e->template == 'settings/plugins/_settings.twig' &&
                $e->variables['plugin']->name == 'Contact Form Extensions'
            ) {
                // Add the tabs
                $e->variables['tabs'] = [
                    ['label' => 'Settings', 'url' => '#settings-tab-settings'],
                    ['label' => 'reCAPTCHA', 'url' => '#settings-tab-recaptcha'],
                ];
            }
        });
    }

    private function _registerVariable(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function (Event $event) {
            /** @var CraftVariable $variable */
            $variable = $event->sender;
            $variable->set('contactFormExtensions', ContactFormExtensionsVariable::class);
        });
    }

    private function _registerContactFormEventListeners(): void
    {
        // Capture Before Send Event from Craft Contact Form plugin
        Event::on(CraftContactFormMailer::class, CraftContactFormMailer::EVENT_BEFORE_SEND, function (CraftContactFormSendEvent $e) {
            if ($e->isSpam) {
                return;
            }

            // Disable Recaptcha
            $disableRecaptcha = false;
            if (is_array($e->submission->message) && array_key_exists('disableRecaptcha', $e->submission->message)) {
                $disableRecaptcha = filter_var($e->submission->message['disableRecaptcha'], FILTER_VALIDATE_BOOLEAN);
            }

            if ($this->settings->recaptcha && $disableRecaptcha != true) {
                $recaptcha = $this->contactFormExtensionsService->getRecaptcha();
                $captchaResponse = Craft::$app->request->getParam('g-recaptcha-response');

                if (!$recaptcha->verifyResponse($captchaResponse, $_SERVER['REMOTE_ADDR'])) {
                    $e->isSpam = true;
                    $e->handled = true;

                    return;
                }
            }

            // Disable Saving Submission to DB
            $disableSaveSubmission = false;
            if (is_array($e->submission->message) && array_key_exists('disableSaveSubmission', $e->submission->message)) {
                $disableSaveSubmission = filter_var($e->submission->message['disableSaveSubmission'], FILTER_VALIDATE_BOOLEAN);
            }

            $submission = $e->submission;
            if ($this->settings->enableDatabase && $disableSaveSubmission != true) {
                $this->contactFormExtensionsService->saveSubmission($submission);
            }

            // Override toEmail setting
            if (is_array($e->submission->message) && array_key_exists('toEmail', $e->submission->message)) {
                $email = Craft::$app->security->validateData($e->submission->message['toEmail']);
                $e->toEmails = explode(',', $email);
            }

            // Notification Template and overrides
            if ($this->settings->enableTemplateOverwrite) {
                // First set the template mode to the Site templates
                Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_SITE);

                // Check if template is overridden in form
                if (is_array($e->submission->message) && array_key_exists('notificationTemplate', $e->submission->message)) {
                    $template = '_emails\\'.Craft::$app->security->validateData($e->submission->message['notificationTemplate']);
                } else {
                    // Render the set template
                    $template = $this->settings->notificationTemplate;
                }

                // Render the set template
                $html = Craft::$app->view->renderTemplate(
                    $template,
                    ['submission' => $e->submission]
                );

                // Update the message body
                $e->message->setHtmlBody($html);

                // Set the template mode back to Control Panel
                if (Craft::$app->request->isCpRequest) {
                    Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_CP);
                }
            }
        });

        // Capture After Send Event from Craft Contact Form plugin
        Event::on(CraftContactFormMailer::class, CraftContactFormMailer::EVENT_AFTER_SEND, function (CraftContactFormSendEvent $e) {
            // Disable confirmation
            $disableConfirmation = false;
            if (is_array($e->submission->message) && array_key_exists('disableConfirmation', $e->submission->message)) {
                $disableConfirmation = filter_var($e->submission->message['disableConfirmation'], FILTER_VALIDATE_BOOLEAN);
            }

            // Confirmation Template and overrides
            if ($this->settings->enableConfirmationEmail && $disableConfirmation != true) {
                // First set the template mode to the Site templates
                Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_SITE);

                // Check if template is overridden in form
                $template = null;
                if (is_array($e->submission->message) && array_key_exists('confirmationTemplate', $e->submission->message)) {
                    $template = '_emails\\'.Craft::$app->security->validateData($e->submission->message['confirmationTemplate']);
                } else {
                    // Render the set template
                    $template = $this->settings->confirmationTemplate;
                }

                $html = Craft::$app->view->renderTemplate(
                    $template,
                    ['submission' => $e->submission]
                );

                // Check fromEmail
                $message = new Message();
                $message->setTo($e->submission->fromEmail);

                if (isset(App::mailSettings()->fromEmail)) {
                    $message->setFrom([Craft::parseEnv(App::mailSettings()->fromEmail) => Craft::parseEnv(App::mailSettings()->fromName)]);
                } else {
                    $message->setFrom($e->message->getTo());
                }
                $message->setHtmlBody($html);

                // Check for subject override
                $confirmationSubject = null;
                if (is_array($e->submission->message) && array_key_exists('confirmationSubject', $e->submission->message)) {
                    $confirmationSubject = Craft::$app->security->validateData($e->submission->message['confirmationSubject']);
                } else {
                    $confirmationSubject = $this->settings->getConfirmationSubject();
                }
                $message->setSubject($confirmationSubject);

                // Send the mail
                Craft::$app->mailer->send($message);

                // Set the template mode back to Control Panel
                if (Craft::$app->request->isCpRequest) {
                    Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_CP);
                }
            }
        });
    }

    private function _registerCraftContactFormCheck(): void
    {
        // Check that Craft Contact Form plugin is installed as this plugin adds to it
        if (!Craft::$app->plugins->isPluginInstalled('contact-form') && !Craft::$app->request->getIsConsoleRequest()) {
            Craft::$app->session->setNotice(Craft::t('contact-form-extensions', 'The Contact Form plugin is not installed or activated, Contact Form Extensions does not work without it.'));
        }
    }
}
