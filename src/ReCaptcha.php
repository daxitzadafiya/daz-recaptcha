<?php

namespace Daxit\ReCaptcha;

use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;

class ReCaptcha
{
    const CLIENT_API = 'https://www.google.com/recaptcha/api.js';
    const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * The recaptcha secret key.
     *
     * @var string
     */
    protected $secret;

    /**
     * The recaptcha sitekey key.
     *
     * @var string
     */
    protected $sitekey;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $http;

    /**
     * The cached verified responses.
     *
     * @var array
     */
    protected $verifiedResponses = [];

    /**
     * ReCaptcha.
     *
     * @param string $secret
     * @param string $sitekey
     * @param array $options
     */
    public function __construct($secret, $sitekey, $options = [])
    {
        $this->secret = $secret;
        $this->sitekey = $sitekey;
        $this->http = new Client($options);
    }

    /**
     * Render HTML captcha.
     *
     * @param array $attributes
     *
     * @return string
     */
    public function display($attributes = [])
    {
        $attributes = $this->prepareAttributes($attributes);
        return '<div' . $this->buildAttributes($attributes) . '></div>';
    }

    /**
     * @see display()
     */
    public function displayWidget($attributes = [])
    {
        return $this->display($attributes);
    }

    /**
     * Display a Invisible reCAPTCHA by embedding a callback into a form submit button.
     *
     * @param string $formIdentifier the html ID of the form that should be submitted.
     * @param string $text the text inside the form button
     * @param array $attributes array of additional html elements
     *
     * @return string
     */
    public function displaySubmit($formIdentifier, $text = 'submit', $attributes = [])
    {
        $javascript = '';
        if (!isset($attributes['data-callback'])) {
            $functionName = 'onSubmit' . str_replace(['-', '=', '\'', '"', '<', '>', '`'], '', $formIdentifier);
            $attributes['data-callback'] = $functionName;
            $javascript = sprintf(
                '<script>function %s(){document.getElementById("%s").submit();}</script>',
                $functionName,
                $formIdentifier
            );
        }

        $attributes = $this->prepareAttributes($attributes);

        $button = sprintf('<button%s><span>%s</span></button>', $this->buildAttributes($attributes), $text);

        return $button . $javascript;
    }

    /**
     * Render js source
     *
     * @param null $lang
     * @param bool $callback
     * @param string $onLoadClass
     * @return string
     */
    public function renderJs($lang = null, $callback = false, $onLoadClass = 'onloadCallBack')
    {
        $jsScript = '<script src="'.$this->getJsLink($lang, $callback, $onLoadClass).'" async defer></script>'."\n";

        $jsScript .= '<script>
            function onloadCallBack() {
                jQuery(".g-recaptcha").each(function () {
                    const reCaptcha = jQuery(this);
                    if (reCaptcha.data("recaptcha-client-id") === undefined) {
                        const recaptchaClientId = grecaptcha.render(reCaptcha.attr("id"), {
                            "callback": function (response) {
                                if (reCaptcha.data("formId") !== "") {
                                    jQuery("#" + reCaptcha.data("inputId"), "#" + reCaptcha.data("formId")).val(response)
                                        .trigger("change");
                                } else {
                                    jQuery("#" + reCaptcha.data("inputId")).val(response).trigger("change");
                                }
                                if (reCaptcha.attr("data-callback")) {
                                    eval("(" + reCaptcha.attr("data-callback") + ")(response)");
                                }
                            },
                            "expired-callback": function (error) {
                                if (reCaptcha.data("formId") !== "") {
                                    jQuery("#" + reCaptcha.data("inputId"), "#" + reCaptcha.data("formId")).val("");
                                } else {
                                    jQuery("#" + reCaptcha.data("inputId")).val("");
                                }
                                if (reCaptcha.attr("data-expired-callback")) {
                                    eval("(" + reCaptcha.attr("data-expired-callback") + ")()");
                                }
                            },
                        });
                        reCaptcha.data("recaptcha-client-id", recaptchaClientId);
                    }
                });
            }
        </script>';

        return $jsScript;
    }

    /**
     * Verify re-captcha response.
     *
     * @param string $response
     * @param string $clientIp
     *
     * @return bool
     */
    public function verifyResponse($response, $clientIp = null, $version = 'v2')
    {
        if (empty($response)) {
            return false;
        }

        // Return true if response already verfied before.
        if (in_array($response, $this->verifiedResponses)) {
            return true;
        }

        $verifyResponse = $this->sendRequestVerify([
            'secret' => $this->secret,
            'response' => $response,
            'remoteip' => $clientIp,
        ]);

        if (isset($verifyResponse['success']) && $verifyResponse['success'] === true) {
            // A response can only be verified once from google, so we need to
            // cache it to make it work in case we want to verify it multiple times.
            $this->verifiedResponses[] = $response;
            return $version === 'v3' ? $verifyResponse : true;
        } else {
            return $version === 'v3' ? $verifyResponse : false;
        }
    }

    /**
     * Verify re-captcha response by Symfony Request.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function verifyRequest(Request $request)
    {
        return $this->verifyResponse(
            $request->get('g-recaptcha-response'),
            $request->getClientIp()
        );
    }

    /**
     * Get recaptcha js link.
     *
     * @param string $lang
     * @param boolean $callback
     * @param string $onLoadClass
     * @return string
     */
    public function getJsLink($lang = null, $callback = false, $onLoadClass = 'onloadCallBack')
    {
        $client_api = static::CLIENT_API;
        $params = [];

        $callback ? $this->setCallBackParams($params, $onLoadClass)  : false;
        $lang ? $params['hl'] = $lang : null;

        return $client_api . '?'. http_build_query($params);
    }

    /**
     * @param $params
     * @param $onLoadClass
     */
    protected function setCallBackParams(&$params, $onLoadClass)
    {
        $params['render'] = 'explicit';
        $params['onload'] = $onLoadClass;
    }

    /**
     * Send verify request.
     *
     * @param array $query
     *
     * @return array
     */
    protected function sendRequestVerify(array $query = [])
    {
        $response = $this->http->request('POST', static::VERIFY_URL, [
            'form_params' => $query,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Prepare HTML attributes and assure that the correct classes and attributes for captcha are inserted.
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function prepareAttributes(array $attributes)
    {
        $attributes['data-sitekey'] = $this->sitekey;
        if (!isset($attributes['class'])) {
            $attributes['class'] = '';
        }
        $attributes['class'] = trim('g-recaptcha ' . $attributes['class']);

        return $attributes;
    }

    /**
     * Build HTML attributes.
     *
     * @param array $attributes
     *
     * @return string
     */
    protected function buildAttributes(array $attributes)
    {
        $html = [];

        foreach ($attributes as $key => $value) {
            $html[] = $key.'="'.$value.'"';
        }

        return count($html) ? ' '.implode(' ', $html) : '';
    }
}
