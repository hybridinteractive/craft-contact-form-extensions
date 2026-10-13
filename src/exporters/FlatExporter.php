<?php

/**
 * Contact Form Extensions plugin for Craft CMS 5.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions\exporters;

use Craft;
use craft\base\ElementExporter;
use craft\elements\db\ElementQueryInterface;

/**
 * Flat CSV exporter for submissions.
 *
 * @author Hybrid Interactive
 *
 * @since 5.1.0
 */
class FlatExporter extends ElementExporter
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('contact-form-extensions', 'Flat');
    }

    /**
     * @inheritdoc
     */
    public function export(ElementQueryInterface $query): mixed
    {
        $results = [];

        foreach ($query->asArray()->all() as $element) {
            /** @var array $element */
            $message = $element['message'] ?? '';
            if (!$this->_isJson($message)) {
                $message = ['message' => $message];
            } else {
                $message = json_decode($message, true);
            }

            $results[] = [
                'id' => $element['id'],
                'uid' => $element['uid'],
                'form' => $element['form'],
                'fromName' => $element['fromName'],
                'fromEmail' => $element['fromEmail'],
                'subject' => $element['subject'],
                'isSpam' => $element['isSpam'] ?? false,
                ...$message,
                'dateCreated' => $element['dateCreated'],
                'dateUpdated' => $element['dateUpdated'],
            ];
        }

        return $results;
    }

    // Private Methods
    // =========================================================================

    /**
     * @param mixed $string
     *
     * @return bool
     */
    private function _isJson(mixed $string): bool
    {
        if (!is_string($string)) {
            return false;
        }

        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
