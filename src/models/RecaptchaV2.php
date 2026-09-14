<?php
/**
 * Contact Form Extensions plugin for Craft CMS 5.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions\models;

use Craft;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Invisible reCAPTCHA v2 implementation (no third-party Laravel package).
 *
 * @author Hybrid Interactive
 * @since 5.1.0
 */
class RecaptchaV2
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $siteKey;

    /**
     * @var string
     */
    public string $secretKey;

    /**
     * @var string
     */
    public string $recaptchaUrl;

    /**
     * @var string
     */
    public string $recaptchaVerificationUrl;

    /**
     * @var bool
     */
    public bool $hideBadge;

    /**
     * @var string
     */
    public string $dataBadge;

    /**
     * @var int
     */
    public int $timeout;

    /**
     * @var bool
     */
    public bool $debug;

    // Public Methods
    // =========================================================================

    /**
     * @param string $siteKey
     * @param string $secretKey
     * @param string $recaptchaUrl
     * @param string $recaptchaVerificationUrl
     * @param bool $hideBadge
     * @param string $dataBadge
     * @param int $timeout
     * @param bool $debug
     */
    public function __construct(
        string $siteKey,
        string $secretKey,
        string $recaptchaUrl,
        string $recaptchaVerificationUrl,
        bool $hideBadge,
        string $dataBadge,
        int $timeout,
        bool $debug,
    ) {
        $this->siteKey = $siteKey;
        $this->secretKey = $secretKey;
        $this->recaptchaUrl = $recaptchaUrl;
        $this->recaptchaVerificationUrl = $recaptchaVerificationUrl;
        $this->hideBadge = $hideBadge;
        $this->dataBadge = $dataBadge;
        $this->timeout = $timeout;
        $this->debug = $debug;
    }

    /**
     * Render reCAPTCHA v2 (invisible).
     *
     * @param string|null $lang Language code (e.g. `en`)
     * @return string
     *
     * @author Hybrid Interactive
     * @since 5.1.0
     */
    public function render(?string $lang = null): string
    {
        $html = $this->_renderPolyfill();
        $html .= $this->_renderCaptchaHtml();
        $html .= $this->_renderFooterJs($lang);

        return $html;
    }

    /**
     * Verify invisible reCAPTCHA response.
     *
     * @param string|null $response
     * @param string|null $clientIp
     * @return bool
     *
     * @author Hybrid Interactive
     * @since 5.1.0
     */
    public function verifyResponse(?string $response, ?string $clientIp = null): bool
    {
        if (empty($response)) {
            return false;
        }

        try {
            $client = new Client(['timeout' => $this->timeout]);
            $result = $client->post($this->recaptchaVerificationUrl, [
                'form_params' => [
                    'secret' => $this->secretKey,
                    'remoteip' => $clientIp,
                    'response' => $response,
                ],
            ]);

            $body = json_decode($result->getBody()->getContents(), true);

            if (!isset($body['success']) || $body['success'] !== true) {
                $errorCodes = $body['error-codes'] ?? [];
                Craft::warning('reCAPTCHA verification failed: ' . implode(', ', $errorCodes), __METHOD__);

                return false;
            }

            return true;
        } catch (RequestException $e) {
            Craft::error('reCAPTCHA verification request failed: ' . $e->getMessage(), __METHOD__);

            return false;
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * @return string
     */
    private function _renderPolyfill(): string
    {
        return '<script src="https://cdnjs.cloudflare.com/polyfill/v2/polyfill.min.js"></script>' . PHP_EOL;
    }

    /**
     * @return string
     */
    private function _renderCaptchaHtml(): string
    {
        $html = '<div id="_g-recaptcha"></div>' . PHP_EOL;
        if ($this->hideBadge) {
            $html .= '<style>.grecaptcha-badge{display:none !important;}</style>' . PHP_EOL;
        }

        $html .= '<div class="g-recaptcha" data-sitekey="' . htmlspecialchars($this->siteKey) . '" ';
        $html .= 'data-size="invisible" data-callback="_submitForm" data-badge="' . htmlspecialchars($this->dataBadge) . '"></div>';

        return $html;
    }

    /**
     * @param string|null $lang
     * @return string
     */
    private function _renderFooterJs(?string $lang = null): string
    {
        $apiUrl = $this->recaptchaUrl;
        if ($lang) {
            $apiUrl .= '?hl=' . htmlspecialchars($lang);
        }

        $html = '<script src="' . htmlspecialchars($apiUrl) . '" async defer></script>' . PHP_EOL;
        $html .= '<script>var _submitForm,_captchaForm,_captchaSubmit,_execute=true,_captchaBadge;</script>';
        $html .= "<script>window.addEventListener('load', _loadCaptcha);" . PHP_EOL;
        $html .= 'function _loadCaptcha(){';
        if ($this->hideBadge) {
            $html .= "_captchaBadge=document.querySelector('.grecaptcha-badge');";
            $html .= "if(_captchaBadge){_captchaBadge.style = 'display:none !important;';}" . PHP_EOL;
        }
        $html .= '_captchaForm=document.querySelector("#_g-recaptcha").closest("form");';
        $html .= "_captchaSubmit=_captchaForm.querySelector('[type=submit]');";
        $html .= '_submitForm=function(){if(typeof _submitEvent==="function"){_submitEvent();';
        $html .= 'grecaptcha.reset();}else{_captchaForm.submit();}};';
        $html .= "_captchaForm.addEventListener('submit',";
        $html .= "function(e){e.preventDefault();if(typeof _beforeSubmit==='function'){";
        $html .= '_execute=_beforeSubmit(e);}if(_execute){grecaptcha.execute();}});';
        if ($this->debug) {
            $html .= $this->_renderDebug();
        }
        $html .= '}</script>' . PHP_EOL;

        return $html;
    }

    /**
     * @return string
     */
    private function _renderDebug(): string
    {
        $debugElements = ['_submitForm', '_captchaForm', '_captchaSubmit'];
        $html = '';
        foreach ($debugElements as $element) {
            $html .= $this->_consoleLog('"Checking element binding of ' . $element . '..."');
            $html .= $this->_consoleLog($element . '!==undefined');
        }

        return $html;
    }

    /**
     * @param string $string
     * @return string
     */
    private function _consoleLog(string $string): string
    {
        return "console.log({$string});";
    }
}
