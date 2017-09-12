<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Server Authorize Request
 */
class ServerAuthorizeRequest extends DirectAuthorizeRequest
{
    protected $service = 'vspserver-register';

    /**
     * Add the optional token details to the base data.
     * The returnUrl is supported for legacy applications not using the notifyUrl.
     *
     * @return array
     */
    public function getData()
    {
        if (! $this->getReturnUrl()) {
            $this->validate('notifyUrl');
        }

        $data = $this->getBaseAuthorizeData();

        // If a token is being used, then include the token data.
        // With a valid token or card reference, the user is just asked
        // for the CVV and not any remaining card details.
        $data = $this->getTokenData($data);

        // ReturnUrl is for legacy usage.
        $data['NotificationURL'] = $this->getNotifyUrl() ?: $this->getReturnUrl();

        $profile = strtoupper($this->getProfile());

        if ($profile === static::PROFILE_NORMAL || $profile === static::PROFILE_LOW) {
            $data['Profile'] = $this->getProfile();
        }

        return $data;
    }

    protected function createResponse($data)
    {
        return $this->response = new ServerAuthorizeResponse($this, $data);
    }
}
