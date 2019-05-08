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

namespace rias\contactformextensions;

use Craft;
use craft\base\Plugin;
use craft\contactform\events\SendEvent;
use craft\contactform\Mailer;
use craft\contactform\models\Submission;
use craft\events\RegisterUrlRulesEvent;
use craft\events\TemplateEvent;
use craft\helpers\App;
use craft\mail\Message;
use craft\services\Plugins;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use rias\contactformextensions\models\Settings;
use rias\contactformextensions\services\ContactFormExtensionsService as ContactFormExtensionsServiceService;
use rias\contactformextensions\variables\ContactFormExtensionsVariable;
use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    Rias
 *
 * @since     1.0.0
 *
 * @property  ContactFormExtensionsServiceService $contactFormExtensionsService
 * @property  Settings $settings
 *
 * @method    Settings getSettings()
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

    public $name;

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
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        if (!Craft::$app->plugins->isPluginInstalled('contact-form') && !Craft::$app->request->getIsConsoleRequest()) {
            Craft::$app->session->setNotice(Craft::t('contact-form-extensions', 'The Contact Form plugin is not installed or activated, Contact Form Extensions does not work without it.'));
        }

        Event::on(View::class, View::EVENT_BEFORE_RENDER_TEMPLATE, function (TemplateEvent $e) {
            if (
                $e->template === 'settings/plugins/_settings' &&
                $e->variables['plugin'] === $this
            ) {
                // Add the tabs
                $e->variables['tabs'] = [
                    ['label' => 'Settings', 'url' => '#settings-tab-settings'],
                    ['label' => 'reCAPTCHA', 'url' => '#settings-tab-recaptcha'],
                ];
            }
        });

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'contact-form-extensions/submissions/<submissionId:\d+>'                       => 'contact-form-extensions/submissions/show-submission',
                'contact-form-extensions/submissions/<submissionId:\d+>/<siteHandle:{handle}>' => 'contact-form-extensions/submissions/show-submission',
            ]);
        });

        Event::on(Mailer::class, Mailer::EVENT_BEFORE_SEND, function (SendEvent $e) {
            if ($e->isSpam) {
                return;
            }

            if ($this->settings->recaptcha) {
                $recaptcha = $this->contactFormExtensionsService->getRecaptcha();
                $captchaResponse = Craft::$app->request->getParam('g-recaptcha-response');

                if (!$recaptcha->verifyResponse($captchaResponse, $_SERVER['REMOTE_ADDR'])) {
                    $e->isSpam = true;
                    $e->handled = true;

                    return;
                }
            }

            $submission = $e->submission;
            if ($this->settings->enableDatabase) {
                $this->contactFormExtensionsService->saveSubmission($submission);
            }

            // Set the overridden "toEmail" setting
            if (is_array($e->submission->message) && array_key_exists('toEmail', $e->submission->message)) {
                $email = Craft::$app->security->validateData($e->submission->message['toEmail']);
                $e->toEmails = explode(',', $email);
            }

            if ($this->settings->enableTemplateOverwrite) {
                // First set the template mode to the Site templates
                Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_SITE);

                // Render the set template
                $html = Craft::$app->view->renderTemplate(
                    $this->settings->notificationTemplate,
                    ['submission' => $e->submission]
                );

                // Update the message body
                $e->message->setHtmlBody($html);

                // Set the template mode back to Control Panel
                Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_CP);
            }
        });

        Event::on(Mailer::class, Mailer::EVENT_AFTER_SEND, function (SendEvent $e) {
            if ($this->settings->enableConfirmationEmail) {
                // First set the template mode to the Site templates
                Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_SITE);

                // Check if template is overridden in form
                $template = null;
                if (is_array($e->submission->message) && array_key_exists('template', $e->submission->message)) {
                    $template = '_emails\\'.Craft::$app->security->validateData($e->submission->message['template']);
                } else {
                    // Render the set template
                    $template = $this->settings->confirmationTemplate;
                }
                $html = Craft::$app->view->renderTemplate(
                    $template,
                    ['submission' => $e->submission]
                );

                // Create the confirmation email
                $message = new Message();
                $message->setTo($e->submission->fromEmail);
                if (isset(App::mailSettings()->fromEmail)) {
                    $message->setFrom(App::mailSettings()->fromEmail);
                } else {
                    $message->setFrom($e->message->getTo());
                }
                $message->setHtmlBody($html);
                $message->setSubject($this->settings->getConfirmationSubject());

                // Send the mail
                Craft::$app->mailer->send($message);

                // Set the template mode back to Control Panel
                Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_CP);
            }
        });

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('contactFormExtensions', ContactFormExtensionsVariable::class);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getCpNavItem()
    {
        if (!$this->settings->enableDatabase) {
            return;
        }

        $navItem = parent::getCpNavItem();

        $navItem['label'] = Craft::t('contact-form-extensions', 'Form Submissions');

        return $navItem;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): string
    {
        // Get and pre-validate the settings
        $settings = $this->getSettings();
        $settings->validate();

        // Get the settings that are being defined by the config file
        $overrides = Craft::$app->getConfig()->getConfigFromFile(strtolower($this->handle));

        return Craft::$app->view->renderTemplate(
            'contact-form-extensions/settings',
            [
                'settings'  => $this->getSettings(),
                'overrides' => array_keys($overrides),
            ]
        );
    }
}
