<?php

/**
 * Contact Form Extensions plugin for Craft CMS 5.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\web\CpScreenResponseBehavior;
use hybridinteractive\contactformextensions\ContactFormExtensions;
use hybridinteractive\contactformextensions\controllers\SubmissionsController;
use hybridinteractive\contactformextensions\controllers\ToolsController;
use hybridinteractive\contactformextensions\elements\db\SubmissionQuery;
use hybridinteractive\contactformextensions\exporters\FlatExporter;
use yii\db\Query;
use yii\web\Response;

/**
 * Submission element.
 *
 * @author Hybrid Interactive
 *
 * @since 5.0.0
 */
class Submission extends Element
{
    // Const Properties
    // =========================================================================

    public const STATUS_IS_SPAM = 'spam';
    public const STATUS_IS_NOT_SPAM = 'not-spam';

    // Public Properties
    // =========================================================================

    /**
     * @var string|null
     */
    public ?string $form = null;

    /**
     * @var string|null
     */
    public ?string $fromName = null;

    /**
     * @var string|null
     */
    public ?string $fromEmail = null;

    /**
     * @var string|null
     */
    public ?string $subject = null;

    /**
     * @var mixed
     */
    public mixed $message = null;

    /**
     * @var bool
     */
    public bool $isSpam = false;

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('contact-form-extensions', 'Submission');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('contact-form-extensions', 'Submissions');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): ?string
    {
        return 'submission';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_IS_NOT_SPAM => [
                'label' => Craft::t('contact-form-extensions', 'Not spam'),
                'color' => 'green',
            ],
            self::STATUS_IS_SPAM => [
                'label' => Craft::t('contact-form-extensions', 'Spam'),
                'color' => 'red',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function find(): ElementQueryInterface
    {
        return new SubmissionQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['form', 'subject', 'fromName', 'fromEmail'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(?string $context = null): array
    {
        $forms = (new Query())
            ->select(['s.form'])
            ->distinct()
            ->from(['s' => '{{%contactform_submissions}}'])
            ->innerJoin(['e' => '{{%elements}}'], '[[e.id]] = [[s.id]]')
            ->where([
                'e.type'        => static::class,
                'e.dateDeleted' => null,
            ])
            ->andWhere(['not', ['s.form' => null]])
            ->andWhere(['!=', 's.form', ''])
            ->orderBy(['s.form' => SORT_ASC])
            ->column();

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
                'label'    => ucfirst((string) $formHandle),
                'criteria' => ['form' => $formHandle],
            ];
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(?string $source = null): array
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
     * @inheritdoc
     */
    protected static function defineExporters(string $source): array
    {
        $exporters = parent::defineExporters($source);
        $exporters[] = FlatExporter::class;

        return $exporters;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'id'          => Craft::t('contact-form-extensions', 'ID'),
            'form'        => Craft::t('contact-form-extensions', 'Form'),
            'subject'     => Craft::t('contact-form-extensions', 'Subject'),
            'fromName'    => Craft::t('contact-form-extensions', 'From Name'),
            'fromEmail'   => Craft::t('contact-form-extensions', 'From Email'),
            'message'     => Craft::t('contact-form-extensions', 'Message'),
            'dateCreated' => Craft::t('contact-form-extensions', 'Date Created'),
        ];
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return parent::defineSortOptions();
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        return $this->isSpam ? self::STATUS_IS_SPAM : self::STATUS_IS_NOT_SPAM;
    }

    /**
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        return $user->can(SubmissionsController::PERMISSION_VIEW_SUBMISSIONS)
            || $user->can('accessPlugin-contact-form-extensions');
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        return $user->can(ToolsController::PERMISSION_DELETE_SUBMISSIONS)
            || $user->can('accessPlugin-contact-form-extensions');
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('contact-form-extensions/submissions/'.$this->id);
    }

    /**
     * @inheritdoc
     */
    public function prepareEditScreen(Response $response, string $containerId): void
    {
        /** @var CpScreenResponseBehavior|null $behavior */
        $behavior = $response->getBehavior(CpScreenResponseBehavior::NAME);
        if ($behavior === null) {
            return;
        }

        $behavior->addCrumb(
            Craft::t('contact-form-extensions', 'Form Submissions'),
            'contact-form-extensions'
        );

        $title = $this->subject ?: Craft::t('contact-form-extensions', 'Submission').' #'.$this->id;
        $behavior->title($title);

        $message = [];
        if (is_string($this->message) && $this->message !== '') {
            $decoded = json_decode($this->message, true);
            if (is_array($decoded)) {
                $message = ContactFormExtensions::$plugin->contactFormExtensionsService->utf8AllTheThings($decoded);
            }
        } elseif (is_array($this->message)) {
            $message = ContactFormExtensions::$plugin->contactFormExtensionsService->utf8AllTheThings($this->message);
        }

        $behavior->contentTemplate('contact-form-extensions/submissions/_show', [
            'submission'    => $this,
            'messageObject' => $message,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function attributeHtml(string $attribute): string
    {
        if ($attribute === 'message') {
            $message = [];
            if (is_string($this->message)) {
                $decoded = json_decode($this->message, true);
                $message = is_array($decoded) ? $decoded : [];
            } elseif (is_array($this->message)) {
                $message = $this->message;
            }

            $skipKeys = [
                'formName',
                'toEmail',
                'confirmationSubject',
                'confirmationTemplate',
                'notificationTemplate',
                'disableRecaptcha',
                'disableConfirmation',
                'disableSaveSubmission',
            ];

            $html = '<ul>';
            foreach ($message as $key => $value) {
                if (is_string($value) && !in_array($key, $skipKeys, true)) {
                    $shortened = trim(substr($value, 0, 30));
                    $html .= "<li><em>{$key}</em>: {$shortened}...</li>";
                }
            }
            $html .= '</ul>';

            return StringHelper::convertToUtf8($html);
        }

        return parent::attributeHtml($attribute);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        $data = [
            'form'      => $this->form,
            'subject'   => $this->subject,
            'fromName'  => $this->fromName,
            'fromEmail' => $this->fromEmail,
            'message'   => $this->message,
            'isSpam'    => $this->isSpam,
        ];

        if ($isNew) {
            $data['id'] = $this->id;
            Craft::$app->getDb()->createCommand()
                ->insert('{{%contactform_submissions}}', $data)
                ->execute();
        } else {
            Craft::$app->getDb()->createCommand()
                ->update('{{%contactform_submissions}}', $data, ['id' => $this->id])
                ->execute();
        }

        parent::afterSave($isNew);
    }
}
