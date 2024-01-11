<?php

namespace hybridinteractive\contactformextensions\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use hybridinteractive\contactformextensions\elements\Submission;

class SubmissionQuery extends ElementQuery
{
    public $form;
    public $subject;
    public $fromName;
    public $fromEmail;
    public $message;
    public $isSpam;

    public function form($value)
    {
        $this->form = $value;

        return $this;
    }

    public function subject($value)
    {
        $this->subject = $value;

        return $this;
    }

    public function fromName($value)
    {
        $this->fromName = $value;

        return $this;
    }

    public function fromEmail($value)
    {
        $this->fromEmail = $value;

        return $this;
    }

    public function message($value)
    {
        $this->message = $value;

        return $this;
    }

    public function isSpam($value)
    {
        $this->isSpam = $value;

        return $this;
    }

    protected function statusCondition(string $status): mixed
    {
        switch ($status) {
            case Submission::STATUS_IS_SPAM:
                return ['isSpam' => true];
            case Submission::STATUS_IS_NOT_SPAM:
                return ['isSpam' => false];
            default:
                return parent::statusCondition($status);
        }
    }

    protected function beforePrepare(): bool
    {
        // join in the products table
        $this->joinElementTable('contactform_submissions');

        // select the columns
        $this->query->select([
            'contactform_submissions.form',
            'contactform_submissions.subject',
            'contactform_submissions.fromName',
            'contactform_submissions.fromEmail',
            'contactform_submissions.message',
            'contactform_submissions.isSpam',
        ]);

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

        return parent::beforePrepare();
    }
}
