<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Exception\InvalidResponseException;

/**
 * Sage Pay Direct Complete Authorize Request
 */
class DirectCompleteAuthorizeRequest extends AbstractRequest
{
    public function getData()
    {
        if($this->httpRequest->request->has('cres')){
            // V4 protocol
            $data = array(
                'CRes' => $this->httpRequest->request->get('cres'), // inconsistent caps are intentional
                'VPSTxId' => $this->httpRequest->request->get('threeDSSessionData'),
            );

            if (empty($data['CRes']) || empty($data['VPSTxId'])) {
                throw new InvalidResponseException;
            }
        }else{
            // V3 protocol
            $data = array(
                'MD' => $this->httpRequest->request->get('MD'),
                'PARes' => $this->httpRequest->request->get('PaRes'), // inconsistent caps are intentional
            );

            if (empty($data['MD']) || empty($data['PARes'])) {
                throw new InvalidResponseException;
            }
        }


        return $data;
    }

    public function getService()
    {
        return 'direct3dcallback';
    }
}
