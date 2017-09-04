<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Server Authorize Request
 */
class ServerAuthorizeRequest extends DirectAuthorizeRequest
{
    /**
     * The returnUrl is supported for legacy applications.
     */
    public function getData()
    {
        if (! $this->getReturnUrl()) {
            $this->validate('notifyUrl');
        }

        $data = $this->getBaseAuthorizeData();

        // ReturnUrl is for legacy usage.
        $data['NotificationURL'] = $this->getNotifyUrl() ?: $this->getReturnUrl();

        $profile = strtoupper($this->getProfile());

        if ($profile === static::PROFILE_NORMAL || $profile === static::PROFILE_LOW) {
            $data['Profile'] = $this->getProfile();
        }

        return $data;
    }

    public function getService()
    {
        return 'vspserver-register';
    }

    protected function createResponse($data)
    {
        return $this->response = new ServerAuthorizeResponse($this, $data);
    }
}
