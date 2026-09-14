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
 * reCAPTCHA v3 helper with multi-form unique response field IDs.
 *
 * @author Hybrid Interactive
 *
 * @since 5.0.0
 */
class RecaptchaV3
{
    // Protected Properties
    // =========================================================================

    /**
     * @var Client
     */
    protected Client $client;

    /**
     * @var string
     */
    private string $siteKey;

    /**
     * @var string
     */
    private string $secretKey;

    /**
     * @var string
     */
    private string $recaptchaUrl;

    /**
     * @var string
     */
    private string $recaptchaVerificationUrl;

    /**
     * @var float
     */
    private float $threshold;

    /**
     * @var bool
     */
    private bool $hideBadge;

    // Public Methods
    // =========================================================================

    /**
     * @param string $siteKey
     * @param string $secretKey
     * @param string $recaptchaUrl
     * @param string $recaptchaVerificationUrl
     * @param float  $threshold
     * @param int    $timeout
     * @param bool   $hideBadge
     */
    public function __construct(
        string $siteKey,
        string $secretKey,
        string $recaptchaUrl,
        string $recaptchaVerificationUrl,
        float $threshold,
        int $timeout = 5,
        bool $hideBadge = false,
    ) {
        $this->siteKey = $siteKey;
        $this->secretKey = $secretKey;
        $this->recaptchaUrl = $recaptchaUrl;
        $this->recaptchaVerificationUrl = $recaptchaVerificationUrl;
        $this->client = new Client([
            'timeout' => $timeout,
        ]);
        $this->threshold = $threshold;
        $this->hideBadge = $hideBadge;
    }

    /**
     * Renders the reCAPTCHA v3 script and hidden response input.
     *
     * @param string|null $action
     *
     * @return string
     *
     * @author Hybrid Interactive
     *
     * @since 5.0.0
     */
    public function render(?string $action = 'homepage'): string
    {
        $action = $action !== null && $action !== '' ? $action : 'homepage';
        $siteKey = $this->siteKey;
        $api_uri = $this->recaptchaUrl;
        $uniqueId = uniqid();
        $safeAction = htmlspecialchars($action, ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
                <script src="{$api_uri}?onload=onloadRecaptcha{$uniqueId}&render={$siteKey}" async defer></script>
                <script>
                    var onloadRecaptcha{$uniqueId} = function() {
                        grecaptcha.ready(function() {
                            var input=document.getElementById('g-recaptcha-response{$uniqueId}');
                            var form=input.parentElement;
                            while(form && form.tagName.toLowerCase()!='form') {
                                form = form.parentElement;
                            }

                            if (form) {
                                form.addEventListener('submit',function(e) {
                                    e.preventDefault();
                                    e.stopImmediatePropagation();

                                    if (input.value == '') {
                                        grecaptcha.execute('{$siteKey}', {action: '{$safeAction}'}).then(function(token) {
                                            input.value = token;
                                            form.submit();
                                        });
                                    }

                                    return false;
                                },false);
                            }
                        });
                    };
                </script>

                <input type="hidden" id="g-recaptcha-response{$uniqueId}" name="g-recaptcha-response" value="">
            HTML;

        if ($this->hideBadge) {
            $html .= '<style>.grecaptcha-badge{display:none;!important}</style>' . PHP_EOL;
        }

        return $html;
    }

    /**
     * Verifies a reCAPTCHA v3 response against the configured threshold.
     *
     * @param string|null $response
     * @param string|null $clientIp
     *
     * @return bool
     *
     * @author Hybrid Interactive
     *
     * @since 5.0.0
     */
    public function verifyResponse(?string $response, ?string $clientIp): bool
    {
        if (empty($response)) {
            return false;
        }

        $body = $this->_sendVerifyRequest([
            'secret' => $this->secretKey,
            'remoteip' => $clientIp,
            'response' => $response,
        ]);

        if (!isset($body['success']) || $body['success'] !== true) {
            return false;
        }

        if (isset($body['score']) && $body['score'] >= $this->threshold) {
            return true;
        }

        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * @param array $query
     *
     * @return array
     */
    private function _sendVerifyRequest(array $query = []): array
    {
        try {
            $response = $this->client->post($this->recaptchaVerificationUrl, [
                'form_params' => $query,
            ]);

            return json_decode((string) $response->getBody(), true) ?? [];
        } catch (TransferException $e) {
            Craft::error('reCAPTCHA verification request failed: ' . $e->getMessage(), __METHOD__);

            return [];
        }
    }
}
