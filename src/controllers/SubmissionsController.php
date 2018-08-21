<?php

namespace rias\contactformextensions\controllers;

use craft\web\Controller;
use rias\contactformextensions\ContactFormExtensions;
use rias\contactformextensions\elements\ContactFormSubmission;
use rias\contactformextensions\elements\db\ContactFormSubmissionQuery;

class SubmissionsController extends Controller
{
    /**
     * @param string|null $submissionId
     * @param string|null $siteHandle
     *
     * @return \yii\web\Response
     */
    public function actionShowSubmission(string $submissionId = null, string $siteHandle = null)
    {
        $query = new ContactFormSubmissionQuery(ContactFormSubmission::class);
        $query->id = $submissionId;

        /* @var ContactFormSubmission $submission */
        $submission = $query->one();

        $messageObject = ContactFormExtensions::$plugin->contactFormExtensionsService->utf8AllTheThings((array) json_decode($submission->message));
        $variables = [
            'submission'    => $submission,
            'siteHandle'    => $siteHandle,
            'messageObject' => $messageObject,
        ];

        return $this->renderTemplate('contact-form-extensions/submissions/_show', $variables);
    }
}
