<?php

namespace hybridinteractive\contactformextensions\base;

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

trait Routes
{
    // Private Methods
    // =========================================================================

    /**
     * Control Panel routes.
     *
     * @return void
     */
    public function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            $event->rules['contact-form-extensions/submissions/<elementId:\d+>'] = 'elements/edit';
            $event->rules['contact-form-extensions/submissions/<elementId:\d+>/<siteHandle:{handle}>'] = 'elements/edit';
        });
    }
}
