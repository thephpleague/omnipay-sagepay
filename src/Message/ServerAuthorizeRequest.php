<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Server Authorize Request
 */
class ServerAuthorizeRequest extends DirectAuthorizeRequest
{
    /**
     * Flag whether to allow the gift aid acceptance box to appear for this
     * transaction on the payment page. This only appears if your vendor
     * account is Gift Aid enabled.
     */
    const ALLOW_GIFT_AID_YES = 1;
    const ALLOW_GIFT_AID_NO  = 0;

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

    /**
     * @return int static::ALLOW_GIFT_AID_YES or static::ALLOW_GIFT_AID_NO
     */
    public function getAllowGiftAid()
    {
        return $this->getParameter('allowGiftAid');
    }

    /**
     * This flag allows the gift aid acceptance box to appear for this transaction
     * on the payment page. This only appears if your vendor account is Gift Aid enabled.
     *
     * Values defined in static::ALLOW_GIFT_AID_* constant.
     *
     * @param bool|int $allowGiftAid 0 = No Gift Aid box displayed (default).
     *                               1 = Display Gift Aid box on payment page.
     *
     * @return $this
     */
    public function setAllowGiftAid($allowGiftAid)
    {
        $allowGiftAid = (bool) $allowGiftAid;

        $this->setParameter(
            'allowGiftAid',
            ($allowGiftAid ? static::ALLOW_GIFT_AID_YES : static::ALLOW_GIFT_AID_NO)
        );
    }

    protected function getBaseAuthorizeData()
    {
        $data = parent::getBaseAuthorizeData();

        if (null === $this->getAllowGiftAid()) {
            $data['AllowGiftAid'] = static::ALLOW_GIFT_AID_NO;
        } else {
            $data['AllowGiftAid'] = $this->getAllowGiftAid();
        }

        return $data;
    }
}
