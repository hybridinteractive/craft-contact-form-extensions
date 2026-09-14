<?php

/**
 * Contact Form Extensions plugin for Craft CMS 5.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use hybridinteractive\contactformextensions\controllers\SubmissionsController;
use hybridinteractive\contactformextensions\elements\Submission;

/**
 * Submission element query.
 *
 * @author Hybrid Interactive
 *
 * @since 5.0.0
 */
class SubmissionQuery extends ElementQuery
{
    // Public Properties
    // =========================================================================

    /**
     * @var mixed
     */
    public mixed $form = null;

    /**
     * @var mixed
     */
    public mixed $subject = null;

    /**
     * @var mixed
     */
    public mixed $fromName = null;

    /**
     * @var mixed
     */
    public mixed $fromEmail = null;

    /**
     * @var mixed
     */
    public mixed $message = null;

    /**
     * @var mixed
     */
    public mixed $isSpam = null;

    /**
     * When false, CP queries are not restricted to users with view permission.
     * Used by Tools clear so delete-only users can still load rows to delete.
     *
     * @var bool
     */
    public bool $enforceViewPermission = true;

    // Public Methods
    // =========================================================================

    /**
     * @param mixed $value
     *
     * @return static
     *
     * @author Hybrid Interactive
     *
     * @since 5.0.0
     */
    public function form(mixed $value): static
    {
        $this->form = $value;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return static
     *
     * @author Hybrid Interactive
     *
     * @since 5.0.0
     */
    public function subject(mixed $value): static
    {
        $this->subject = $value;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return static
     *
     * @author Hybrid Interactive
     *
     * @since 5.0.0
     */
    public function fromName(mixed $value): static
    {
        $this->fromName = $value;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return static
     *
     * @author Hybrid Interactive
     *
     * @since 5.0.0
     */
    public function fromEmail(mixed $value): static
    {
        $this->fromEmail = $value;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return static
     *
     * @author Hybrid Interactive
     *
     * @since 5.0.0
     */
    public function message(mixed $value): static
    {
        $this->message = $value;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return static
     *
     * @author Hybrid Interactive
     *
     * @since 5.1.0
     */
    public function isSpam(mixed $value): static
    {
        $this->isSpam = $value;

        return $this;
    }

    /**
     * Allows CP queries without the view-submissions permission check.
     *
     * @return static
     *
     * @author Hybrid Interactive
     *
     * @since 5.1.0
     */
    public function withoutViewPermissionCheck(): static
    {
        $this->enforceViewPermission = false;

        return $this;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function statusCondition(string $status): mixed
    {
        return match ($status) {
            Submission::STATUS_IS_SPAM => ['contactform_submissions.isSpam' => true],
            Submission::STATUS_IS_NOT_SPAM => ['contactform_submissions.isSpam' => false],
            default => parent::statusCondition($status),
        };
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('contactform_submissions');

        $this->query->addSelect([
            'contactform_submissions.form',
            'contactform_submissions.subject',
            'contactform_submissions.fromName',
            'contactform_submissions.fromEmail',
            'contactform_submissions.message',
            'contactform_submissions.isSpam',
        ]);

        // Element indexes/exports do not check canView() per row; deny unauthorized CP users here.
        // Tools clear uses withoutViewPermissionCheck() so delete-only users can load rows to delete.
        if (
            $this->enforceViewPermission
            && Craft::$app->getRequest()->getIsCpRequest()
        ) {
            $user = Craft::$app->getUser()->getIdentity();
            if ($user !== null && !$user->can(SubmissionsController::PERMISSION_VIEW_SUBMISSIONS)) {
                $this->subQuery->andWhere('0=1');
            }
        }

        if ($this->form) {
            $this->subQuery->andWhere(Db::parseParam('contactform_submissions.form', $this->form));
        }

        if ($this->subject) {
            $this->subQuery->andWhere(Db::parseParam('contactform_submissions.subject', $this->subject));
        }

        if ($this->fromName) {
            $this->subQuery->andWhere(Db::parseParam('contactform_submissions.fromName', $this->fromName));
        }

        if ($this->fromEmail) {
            $this->subQuery->andWhere(Db::parseParam('contactform_submissions.fromEmail', $this->fromEmail));
        }

        if ($this->message) {
            $this->subQuery->andWhere(Db::parseParam('contactform_submissions.message', $this->message));
        }

        if ($this->isSpam !== null) {
            $this->subQuery->andWhere(Db::parseParam('contactform_submissions.isSpam', $this->isSpam));
        }

        return parent::beforePrepare();
    }
}
