<?php

namespace rias\contactformextensions\models;

use GuzzleHttp\Client;

class RecaptchaV3
{
    const API_URI = 'https://www.google.com/recaptcha/api.js';
    const VERIFY_URI = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    private $siteKey;
    private $secretKey;
    private $threshold;
    private $hideBadge;

    public function __construct(string $siteKey, string $secretKey, float $threshold, int $timeout = 5, bool $hideBadge = false)
    {
        $this->siteKey = $siteKey;
        $this->secretKey = $secretKey;
        $this->client = new Client([
            'timeout' => $timeout,
        ]);
        $this->threshold = $threshold;
        $this->hideBadge = $hideBadge;
    }

    public function render($action = 'homepage')
    {
        $siteKey = $this->siteKey;
        $api_uri = static::API_URI;

        $html = <<<HTML
                <script src="${api_uri}?render=${siteKey}"></script>
                <script>
                  grecaptcha.ready(function() {
                      grecaptcha.execute('${siteKey}', {action: '${action}'}).then(function(token) {
                         document.getElementById('g-recaptcha-response').value = token;
                      });
                  });
                </script>
                <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" value="">
HTML;

        if ($this->hideBadge) {
            $html .= '<style>.grecaptcha-badge{display:none;!important}</style>'.PHP_EOL;
        }

        return $html;
    }

    public function verifyResponse($response, $clientIp)
    {
        if (empty($response)) {
            return false;
        }

        $response = $this->sendVerifyRequest([
            'secret'   => $this->secretKey,
            'remoteip' => $clientIp,
            'response' => $response,
        ]);

        if (!isset($response['success']) || $response['success'] !== true) {
            return false;
        }

        if (isset($response['score']) && $response['score'] >= $this->threshold) {
            return true;
        }

        return false;
    }

    protected function sendVerifyRequest(array $query = [])
    {
        $response = $this->client->post(static::VERIFY_URI, [
            'form_params' => $query,
        ]);

        return json_decode($response->getBody(), true);
    }
}
