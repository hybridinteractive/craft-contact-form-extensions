<?php

/**
 * Contact Form Extensions plugin for Craft CMS 5.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions\controllers;

use craft\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Submissions controller.
 *
 * Gates the submissions index. Edit screens use Craft's unified element editor.
 *
 * @author Hybrid Interactive
 *
 * @since 5.0.0
 */
class SubmissionsController extends Controller
{
    // Const Properties
    // =========================================================================

    public const PERMISSION_VIEW_SUBMISSIONS = 'contact-form-extensions:view-submissions';

    // Protected Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = false;

    // Public Methods
    // =========================================================================

    /**
     * Renders the submissions element index.
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
        $this->requirePermission(self::PERMISSION_VIEW_SUBMISSIONS);

        return $this->renderTemplate('contact-form-extensions/index');
    }
}
