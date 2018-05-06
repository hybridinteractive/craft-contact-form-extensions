<?php

namespace rias\contactformextensions\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class ContactFormSubmissionQuery extends ElementQuery
{
    public $form;
    public $subject;
    public $fromName;
    public $fromEmail;
    public $message;

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
