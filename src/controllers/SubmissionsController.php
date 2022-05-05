<?php

namespace hybridinteractive\contactformextensions\controllers;

use craft\web\Controller;
use hybridinteractive\contactformextensions\ContactFormExtensions;
use hybridinteractive\contactformextensions\elements\ContactFormSubmission;
use hybridinteractive\contactformextensions\elements\db\ContactFormSubmissionQuery;

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

        if ($submission) {
            $messageObject = ContactFormExtensions::$plugin->contactFormExtensionsService->utf8AllTheThings((array) json_decode($submission->message));

            $variables = [
                'submission'    => $submission,
                'siteHandle'    => $siteHandle,
                'messageObject' => $messageObject,
            ];

            return $this->renderTemplate('contact-form-extensions/submissions/_show', $variables);
        } else {
            $variables = [
                'submission'    => null,
                'siteHandle'    => '',
                'messageObject' => '',
            ];

            return $this->renderTemplate('contact-form-extensions/submissions/_show', $variables);
        }
    }
}
