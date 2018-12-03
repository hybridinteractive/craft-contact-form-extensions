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

namespace rias\contactformextensions\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;
use rias\contactformextensions\elements\db\ContactFormSubmissionQuery;

/**
 *  Element.
 *
 * Element is the base class for classes representing elements in terms of objects.
 *
 * @property FieldLayout|null      $fieldLayout           The field layout used by this element
 * @property array                 $htmlAttributes        Any attributes that should be included in the element’s DOM representation in the Control Panel
 * @property int[]                 $supportedSiteIds      The site IDs this element is available in
 * @property string|null           $uriFormat             The URI format used to generate this element’s URL
 * @property string|null           $url                   The element’s full URL
 * @property \Twig_Markup|null     $link                  An anchor pre-filled with this element’s URL and title
 * @property string|null           $ref                   The reference string to this element
 * @property string                $indexHtml             The element index HTML
 * @property bool                  $isEditable            Whether the current user can edit the element
 * @property string|null           $cpEditUrl             The element’s CP edit URL
 * @property string|null           $thumbUrl              The URL to the element’s thumbnail, if there is one
 * @property string|null           $iconUrl               The URL to the element’s icon image, if there is one
 * @property string|null           $status                The element’s status
 * @property Element               $next                  The next element relative to this one, from a given set of criteria
 * @property Element               $prev                  The previous element relative to this one, from a given set of criteria
 * @property Element               $parent                The element’s parent
 * @property mixed                 $route                 The route that should be used when the element’s URI is requested
 * @property int|null              $structureId           The ID of the structure that the element is associated with, if any
 * @property ElementQueryInterface $ancestors             The element’s ancestors
 * @property ElementQueryInterface $descendants           The element’s descendants
 * @property ElementQueryInterface $children              The element’s children
 * @property ElementQueryInterface $siblings              All of the element’s siblings
 * @property Element               $prevSibling           The element’s previous sibling
 * @property Element               $nextSibling           The element’s next sibling
 * @property bool                  $hasDescendants        Whether the element has descendants
 * @property int                   $totalDescendants      The total number of descendants that the element has
 * @property string                $title                 The element’s title
 * @property string|null           $serializedFieldValues Array of the element’s serialized custom field values, indexed by their handles
 * @property array                 $fieldParamNamespace   The namespace used by custom field params on the request
 * @property string                $contentTable          The name of the table this element’s content is stored in
 * @property string                $fieldColumnPrefix     The field column prefix this element’s content uses
 * @property string                $fieldContext          The field context this element’s content uses
 *
 * http://pixelandtonic.com/blog/craft-element-types
 *
 * @author    Rias
 *
 * @since     1.0.0
 */
class ContactFormSubmission extends Element
{
    // Public Properties
    // =========================================================================

    public $form;
    public $fromName;
    public $fromEmail;
    public $subject;
    public $message;

    public static function hasContent(): bool
    {
        return true;
    }

    public static function isLocalized(): bool
    {
        return false;
    }

    public static function find(): ElementQueryInterface
    {
        return new ContactFormSubmissionQuery(static::class);
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['form', 'subject', 'fromName', 'fromEmail'];
    }

    public function getIsEditable(): bool
    {
        return true;
    }

    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl('contact-form-extensions/submissions/'.$this->id);
    }

    protected static function defineSources(string $context = null): array
    {
        $forms = array_unique(array_map(function (ContactFormSubmission $submission) {
            return $submission->form;
        }, self::find()->all()));

        $sources = [
            [
                'key'      => '*',
                'label'    => Craft::t('contact-form-extensions', 'All submissions'),
                'criteria' => [],
            ],
        ];

        foreach ($forms as $formHandle) {
            $sources[] = [
                'key'      => $formHandle,
                'label'    => ucfirst($formHandle),
                'criteria' => ['form' => $formHandle],
            ];
        }

        return $sources;
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $actions[] = Craft::$app->getElements()->createAction([
            'type'                => Delete::class,
            'confirmationMessage' => Craft::t('app', 'Are you sure you want to delete the selected entries?'),
            'successMessage'      => Craft::t('app', 'Entries deleted.'),
        ]);

        return $actions;
    }

    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'id'          => Craft::t('contact-form-extensions', 'ID'),
            'form'        => Craft::t('contact-form-extensions', 'Form'),
            'subject'     => Craft::t('contact-form-extensions', 'Subject'),
            'fromName'    => Craft::t('contact-form-extensions', 'From Name'),
            'fromEmail'   => Craft::t('contact-form-extensions', 'From Email'),
            'message'     => Craft::t('contact-form-extensions', 'Message'),
            'dateCreated' => Craft::t('contact-form-extensions', 'Date Created'),
        ];

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'id',
            'form',
            'subject',
            'fromName',
            'fromEmail',
            'message',
            'dateCreated',
        ];
    }

    public function getTableAttributeHtml(string $attribute): string
    {
        if ($attribute == 'message') {
            $message = (array) json_decode($this->message);
            $html = '<ul>';
            foreach ($message as $key => $value) {
                if (is_string($value)) {
                    $shortened = trim(substr($value, 0, 30));
                    $html .= "<li><em>{$key}</em>: {$shortened}...</li>";
                }
            }
            $html .= '</ul>';

            return $html;
        }

        return parent::getTableAttributeHtml($attribute); // TODO: Change the autogenerated stub
    }

    protected static function defineSortOptions(): array
    {
        $sortOptions = parent::defineSortOptions();

        unset($sortOptions['dateCreated']);

        return $sortOptions;
    }

    /**
     * @param bool $isNew
     *
     * @throws \yii\db\Exception
     */
    public function afterSave(bool $isNew)
    {
        if ($isNew) {
            Craft::$app->db->createCommand()
                ->insert('{{%contactform_submissions}}', [
                    'id'        => $this->id,
                    'form'      => $this->form,
                    'subject'   => $this->subject,
                    'fromName'  => $this->fromName,
                    'fromEmail' => $this->fromEmail,
                    'message'   => $this->message,
                ])
                ->execute();
        } else {
            Craft::$app->db->createCommand()
                ->update('{{%contactform_submissions}}', [
                    'form'      => $this->form,
                    'subject'   => $this->subject,
                    'fromName'  => $this->fromName,
                    'fromEmail' => $this->fromEmail,
                    'message'   => $this->message,
                ], ['id' => $this->id])
                ->execute();
        }

        parent::afterSave($isNew);
    }
}
