<?php
/**
 * Craft Contact Form Extensions plugin for Craft CMS 4.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use hybridinteractive\contactformextensions\elements\db\SubmissionQuery;

class Submission extends Element
{
    // Public Properties
    // =========================================================================

    public ?string $form;
    public ?string $fromName;
    public ?string $fromEmail;
    public ?string $subject;
    public $message;

    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function isLocalized(): bool
    {
        return false;
    }

    public function canView($user): bool
    {
        return true;
    }

    public function canDelete($user): bool
    {
        return true;
    }

    public static function find(): ElementQueryInterface
    {
        return new SubmissionQuery(static::class);
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['form', 'subject', 'fromName', 'fromEmail'];
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('contact-form-extensions/submissions/'.$this->id);
    }

    /**
     * @inheritDoc
     */
    protected static function defineSources(string $context = null): array
    {
        $forms = array_unique(array_map(function (self $submission) {
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

    /**
     * @inheritDoc
     */
    protected static function defineActions(string $source = null): array
    {
        $elementsService = Craft::$app->getElements();

        $actions = parent::defineActions($source);

        $actions[] = $elementsService->createAction([
            'type'                => Delete::class,
            'confirmationMessage' => Craft::t('contact-form-extensions', 'Are you sure you want to delete the selected submissions?'),
            'successMessage'      => Craft::t('contact-form-extensions', 'Submissions deleted.'),
        ]);

        return $actions;
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function getTableAttributeHtml(string $attribute): string
    {
        if ($attribute == 'message') {
            $message = (array) json_decode($this->message);
            $html = '<ul>';
            foreach ($message as $key => $value) {
                if (is_string($value) && $key != 'formName' && $key != 'toEmail' && $key != 'confirmationSubject' && $key != 'confirmationTemplate' && $key != 'notificationTemplate' && $key != 'disableRecaptcha' && $key != 'disableConfirmation') {
                    $shortened = trim(substr($value, 0, 30));
                    $html .= "<li><em>{$key}</em>: {$shortened}...</li>";
                }
            }
            $html .= '</ul>';

            return StringHelper::convertToUtf8($html);
        }

        return parent::getTableAttributeHtml($attribute);
    }

    /**
     * @inheritDoc
     */
    protected static function defineSortOptions(): array
    {
        $sortOptions = parent::defineSortOptions();

        return $sortOptions;
    }

    /**
     * @param bool $isNew
     *
     * @throws \yii\db\Exception
     */
    public function afterSave(bool $isNew): void
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
