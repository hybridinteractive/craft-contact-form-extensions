<?php
/**
 * schema plugin for Craft CMS 3.x.
 *
 * A fluent builder Schema.org types and ld+json generator based on Spatie's schema-org package
 *
 * @link      https://rias.be
 *
 * @copyright Copyright (c) 2017 Rias
 */

namespace rias\contactformextensions\variables;

use Craft;
use craft\elements\db\ElementQueryInterface;
use rias\contactformextensions\ContactFormExtensions;
use rias\contactformextensions\elements\ContactFormSubmission;

/**
 * @author    Rias
 *
 * @since     1.0.0
 */
class ContactFormExtensionsVariable
{
    public function name()
    {
        return ContactFormExtensions::$plugin->name;
    }

    public function recaptcha(string $localeOrAction = null)
    {
        if (ContactFormExtensions::$plugin->settings->recaptcha) {
            return ContactFormExtensions::$plugin->contactFormExtensionsService->getRecaptcha()->render($localeOrAction);
        }

        return '';
    }

    public function submissions($criteria = null): ElementQueryInterface
    {
        $query = ContactFormSubmission::find();

        if ($criteria) {
            Craft::configure($query, $criteria);
        }

        return $query;
    }
}
