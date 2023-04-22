<?php

namespace hybridinteractive\contactformextensions\exporters;

use Craft;
use craft\base\ElementExporter;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;

class FlatExporter extends ElementExporter
{
    public static function displayName(): string
    {
        return Craft::t('contact-form-extensions', 'Flat');
    }

    private function isJson($string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function export(ElementQueryInterface $query): mixed
    {
        $results = [];

        /** @var ElementQuery $query */
        foreach ($query->asArray()->all() as $element) {
            /** @var array $element */
            $message = $element['message'];
            if (!$this->isJson($message)) {
                $message = ['message' => $message];
            } else {
                $message = json_decode($message, true);
            }

            $results[] = [
                'id'        => $element['id'],
                'uid'       => $element['uid'],
                'form'      => $element['form'],
                'fromName'  => $element['fromName'],
                'fromEmail' => $element['fromEmail'],
                'subject'   => $element['subject'],
                ...$message,
                'dateCreated' => $element['dateCreated'],
                'dateUpdated' => $element['dateUpdated'],
            ];
        }

        return $results;
    }
}
