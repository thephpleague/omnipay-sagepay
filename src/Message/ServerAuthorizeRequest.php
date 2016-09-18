<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Server Authorize Request
 */
class ServerAuthorizeRequest extends DirectAuthorizeRequest
{
    public function getProfile()
    {
        return $this->getParameter('profile');
    }

    public function setProfile($value)
    {
        return $this->setParameter('profile', $value);
    }

    /**
     * The returnUrl is supported for legacy applications.
     */
    public function getData()
    {
        if (!$this->getReturnUrl()) {
            $this->validate('notifyUrl');
        }

        $data = $this->getBaseAuthorizeData();
        $data['NotificationURL'] = $this->getNotifyUrl() ?: $this->getReturnUrl();
        $data['Profile'] = $this->getProfile();

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
