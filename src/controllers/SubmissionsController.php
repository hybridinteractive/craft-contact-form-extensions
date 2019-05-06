<?php

namespace rias\contactformextensions\controllers;

use Craft;
use craft\web\Controller;
use rias\contactformextensions\ContactFormExtensions;
use rias\contactformextensions\elements\ContactFormSubmission;
use rias\contactformextensions\elements\db\ContactFormSubmissionQuery;
use rias\contactformextensions\models\Settings;

class SubmissionsController extends Controller
{
    /**
     * @param string|null $submissionId
     * @param string|null $siteHandle
     * @property  Settings $settings
     *
     * @return \yii\web\Response
     */
    public function actionShowSubmission(string $submissionId = null, string $siteHandle = null)
    {
        $query = new ContactFormSubmissionQuery(ContactFormSubmission::class);
        $query->id = $submissionId;

        /* @var ContactFormSubmission $submission */
        $submission = $query->one();

        $volume = Craft::$app->getVolumes()->getVolumeByHandle(ContactFormExtensions::$plugin->settings->attachmentVolumeHandle);
        $messageObject = ContactFormExtensions::$plugin->contactFormExtensionsService->utf8AllTheThings((array) json_decode($submission->message));

        $variables = [
            'submission'    => $submission,
            'siteHandle'    => $siteHandle,
            'messageObject' => $messageObject,
            'volumeRoot'    => $this->sanitizeUrl($volume->getRootPath())
        ];

        return $this->renderTemplate('contact-form-extensions/submissions/_show', $variables);
    }


    public function sanitizeUrl($url){
        $url = str_replace(Craft::getAlias('@webroot'), '',$url);
        $url = str_replace(Craft::getAlias('@root'), '',$url);
        $url = str_replace(Craft::getAlias('@web'), '',$url);
        return $url;
    }
}
