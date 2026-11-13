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
use hybridinteractive\contactformextensions\services\ContactFormExtensionsService;

/**
 * Flat CSV exporter for submissions.
 *
 * @author Hybrid Interactive
 *
 * @since 5.1.0
 */
class FlatExporter extends ElementExporter
{
    // Const Properties
    // =========================================================================

    /**
     * Prefix added to message fields whose names collide with a submission column.
     *
     * @var string
     *
     * @since 5.1.2
     */
    public const MESSAGE_COLUMN_PREFIX = 'message_';

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
            $decoded = $this->_isJson($message) ? json_decode($message, true) : null;
            $message = is_array($decoded) ? $decoded : ['message' => $message];

            $results[] = $this->buildRow($element, $message);
        }

        return $results;
    }

    /**
     * Builds one export row. Message fields never overwrite submission columns or each
     * other, and control keys (hashed overrides, per-form switches) are left out.
     *
     * @param array $element
     * @param array $message
     *
     * @return array
     *
     * @author Hybrid Interactive
     *
     * @since 5.1.2
     */
    public function buildRow(array $element, array $message): array
    {
        $row = [
            'id' => $element['id'] ?? null,
            'uid' => $element['uid'] ?? null,
            'form' => $element['form'] ?? null,
            'fromName' => $element['fromName'] ?? null,
            'fromEmail' => $element['fromEmail'] ?? null,
            'subject' => $element['subject'] ?? null,
            'isSpam' => $element['isSpam'] ?? false,
        ];
        $trailing = [
            'dateCreated' => $element['dateCreated'] ?? null,
            'dateUpdated' => $element['dateUpdated'] ?? null,
        ];

        foreach ($message as $key => $value) {
            if (in_array($key, ContactFormExtensionsService::CONTROL_MESSAGE_KEYS, true)) {
                continue;
            }

            $column = (string) $key;
            while (array_key_exists($column, $row) || array_key_exists($column, $trailing)) {
                $column = self::MESSAGE_COLUMN_PREFIX . $column;
            }

            $row[$column] = $value;
        }

        return $row + $trailing;
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
