<?php

/**
 * Contact Form Extensions plugin for Craft CMS 5.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions\controllers;

use Craft;
use craft\web\Controller;
use hybridinteractive\contactformextensions\elements\Submission;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Tools controller for clearing submissions.
 *
 * @author Hybrid Interactive
 *
 * @since 5.1.0
 */
class ToolsController extends Controller
{
    // Const Properties
    // =========================================================================

    public const PERMISSION_DELETE_SUBMISSIONS = 'contact-form-extensions:delete-submissions';

    // Protected Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = false;

    // Public Methods
    // =========================================================================

    /**
     * Shows the tools page.
     *
     * @throws ForbiddenHttpException
     *
     * @return Response
     *
     * @author Hybrid Interactive
     *
     * @since 5.1.0
     */
    public function actionIndex(): Response
    {
        $this->requirePermission(self::PERMISSION_DELETE_SUBMISSIONS);

        $formNames = $this->_getFormNames();
        $formOptions = array_map(
            static fn (string $form) => ['label' => ucfirst($form), 'value' => $form],
            $formNames
        );
        array_unshift($formOptions, [
            'label' => Craft::t('contact-form-extensions', 'All forms'),
            'value' => 'all',
        ]);

        return $this->renderTemplate('contact-form-extensions/tools/_index', [
            'formOptions' => $formOptions,
        ]);
    }

    /**
     * Clears submissions by form name (or all if formName is `all`).
     *
     * @throws ForbiddenHttpException
     * @throws \yii\web\BadRequestHttpException
     * @throws \Throwable
     *
     * @return Response
     *
     * @author Hybrid Interactive
     *
     * @since 5.1.0
     */
    public function actionClearSubmissions(): Response
    {
        $this->requirePermission(self::PERMISSION_DELETE_SUBMISSIONS);
        $this->requirePostRequest();

        $formName = Craft::$app->getRequest()->getBodyParam('formName');

        if (empty($formName)) {
            Craft::$app->getSession()->setError(Craft::t('contact-form-extensions', 'Please select a form'));

            return $this->redirectToPostedUrl();
        }

        $query = Submission::find()->status(null);
        if ($formName !== 'all') {
            /** @var \hybridinteractive\contactformextensions\elements\db\SubmissionQuery $query */
            $query->form($formName);
        }

        $count = 0;
        $elementsService = Craft::$app->getElements();
        foreach ($query->each() as $submission) {
            $elementsService->deleteElement($submission);
            $count++;
        }

        Craft::$app->getSession()->setNotice(Craft::t('contact-form-extensions', '{count} submission(s) deleted.', [
            'count' => $count,
        ]));

        return $this->redirectToPostedUrl();
    }

    // Private Methods
    // =========================================================================

    /**
     * @return array<string>
     */
    private function _getFormNames(): array
    {
        $forms = Craft::$app->getDb()->createCommand(
            'SELECT DISTINCT s.[[form]] FROM {{%contactform_submissions}} s
             INNER JOIN {{%elements}} e ON e.[[id]] = s.[[id]]
             WHERE e.[[type]] = :type AND e.[[dateDeleted]] IS NULL',
            ['type' => Submission::class]
        )->queryColumn();

        return array_values(array_filter($forms));
    }
}
