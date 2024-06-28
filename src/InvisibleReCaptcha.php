<?php

namespace hybridinteractive\contactformextensions;

use AlbertCht\InvisibleReCaptcha\InvisibleReCaptcha as BaseInvisibleReCaptcha;

class InvisibleReCaptcha extends BaseInvisibleReCaptcha
{
    const POLYFILL_URI = 'https://cdnjs.cloudflare.com/polyfill/v2/polyfill.min.js';
}
