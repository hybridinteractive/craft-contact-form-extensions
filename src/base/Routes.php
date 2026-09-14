<?php

/**
 * Contact Form Extensions plugin for Craft CMS 5.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions\base;

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

/**
 * CP URL route registration.
 *
 * @author Hybrid Interactive
 *
 * @since 5.0.0
 */
trait Routes
{
    // Protected Methods
    // =========================================================================

    /**
     * Registers Control Panel routes.
     *
     * @return void
     *
     * @author Hybrid Interactive
     *
     * @since 5.0.0
     */
    protected function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['contact-form-extensions'] = ['template' => 'contact-form-extensions/index'];
            $event->rules['contact-form-extensions/submissions/<elementId:\\d+>'] = 'elements/edit';
            $event->rules['contact-form-extensions/submissions/<elementId:\\d+>/<siteHandle:{handle}>'] = 'elements/edit';
            $event->rules['contact-form-extensions/tools'] = 'contact-form-extensions/tools/index';
        });
    }
}
