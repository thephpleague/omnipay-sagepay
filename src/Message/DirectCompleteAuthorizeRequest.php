<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Exception\InvalidResponseException;

/**
 * Sage Pay Direct Complete Authorize Request.
 * TODO: support passing in MD and PaRes as parameters.
 * TODO: support MDX as well as MD.
 */
class DirectCompleteAuthorizeRequest extends AbstractRequest
{
    public function getData()
    {
        $data = array(
            'MD' => $this->httpRequest->request->get('MD'),
            // Inconsistent caps are intentional
            'PARes' => $this->httpRequest->request->get('PaRes'),
        );

        if (empty($data['MD']) || empty($data['PARes'])) {
            throw new InvalidResponseException;
        }

        return $data;
    }

    public function getService()
    {
        return 'direct3dcallback';
    }
}
