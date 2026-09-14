<?php

/**
 * Contact Form Extensions plugin for Craft CMS 5.x.
 *
 * Adds extensions to the Craft CMS contact form plugin.
 */

namespace hybridinteractive\contactformextensions\models;

use Craft;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;

/**
 * Invisible reCAPTCHA v2 implementation (no third-party Laravel package).
 *
 * @author Hybrid Interactive
 *
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
     * @param bool   $hideBadge
     * @param string $dataBadge
     * @param int    $timeout
     * @param bool   $debug
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
     *
     * @return string
     *
     * @author Hybrid Interactive
     *
     * @since 5.1.0
     */
    public function render(?string $lang = null): string
    {
        $uniqueId = uniqid();

        $html = $this->_renderPolyfill();
        $html .= $this->_renderCaptchaHtml($uniqueId);
        $html .= $this->_renderFooterJs($uniqueId, $lang);

        return $html;
    }

    /**
     * Verify invisible reCAPTCHA response.
     *
     * @param string|null $response
     * @param string|null $clientIp
     *
     * @return bool
     *
     * @author Hybrid Interactive
     *
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
        } catch (TransferException $e) {
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
     * @param string $uniqueId
     *
     * @return string
     */
    private function _renderCaptchaHtml(string $uniqueId): string
    {
        $html = '<div id="_g-recaptcha' . $uniqueId . '"></div>' . PHP_EOL;
        if ($this->hideBadge) {
            $html .= '<style>.grecaptcha-badge{display:none !important;}</style>' . PHP_EOL;
        }

        return $html;
    }

    /**
     * @param string      $uniqueId
     * @param string|null $lang
     *
     * @return string
     */
    private function _renderFooterJs(string $uniqueId, ?string $lang = null): string
    {
        $apiUrl = $this->recaptchaUrl;
        $query = ['onload' => 'onloadRecaptcha' . $uniqueId, 'render' => 'explicit'];
        if ($lang) {
            $query['hl'] = $lang;
        }
        $apiUrl .= '?' . http_build_query($query);

        $containerId = '_g-recaptcha' . $uniqueId;
        $onload = 'onloadRecaptcha' . $uniqueId;
        $siteKey = json_encode($this->siteKey, JSON_THROW_ON_ERROR);
        $dataBadge = json_encode($this->dataBadge, JSON_THROW_ON_ERROR);

        $html = '<script src="' . htmlspecialchars($apiUrl, ENT_QUOTES, 'UTF-8') . '" async defer></script>' . PHP_EOL;
        $html .= '<script>' . PHP_EOL;
        $html .= 'var ' . $onload . '=function(){';
        $html .= 'var container=document.getElementById("' . $containerId . '");';
        $html .= 'var form=container.closest("form");';
        $html .= 'var execute=true;';
        $html .= 'var widgetId=grecaptcha.render(container,{sitekey:' . $siteKey . ',size:"invisible",badge:' . $dataBadge . ',callback:function(){';
        $html .= 'if(typeof _submitEvent==="function"){_submitEvent();grecaptcha.reset(widgetId);}else{form.submit();}}});';
        $html .= 'form.addEventListener("submit",function(e){e.preventDefault();';
        $html .= 'if(typeof _beforeSubmit==="function"){execute=_beforeSubmit(e);}';
        $html .= 'if(execute){grecaptcha.execute(widgetId);}});';
        if ($this->debug) {
            $html .= $this->_consoleLog('"reCAPTCHA widget bound for ' . $containerId . '"');
        }
        $html .= '};</script>' . PHP_EOL;

        return $html;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function _consoleLog(string $string): string
    {
        return "console.log({$string});";
    }
}
