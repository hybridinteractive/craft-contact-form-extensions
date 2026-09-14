<?php

/**
 * Contact Form Extensions plugin for Craft CMS 5.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions\controllers;

use craft\web\Controller;

/**
 * Submissions controller.
 *
 * Kept for permission constants used by the Submission element and permission registration.
 * Edit screens use Craft's unified element editor.
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
}
