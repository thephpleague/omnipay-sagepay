<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;

/**
 * Sage Pay Response
 */
class Response extends AbstractResponse implements RedirectResponseInterface
{
    /**
     * FIXME: The response should never be directly passed the raw HTTP
     * body like this. The body should be parsed to data before instantiation.
     * However, the tests do not do that. I believe it is the tests that are broken,
     * but the tests are how the interface has been implemented so we cannot break
     * that for people who may rely on it.
     */
    public function __construct(RequestInterface $request, $data)
    {
        $this->request = $request;

        if (!is_array($data)) {
            // Split the data (string or guzzle body object) into lines.
            $lines = preg_split('/[\n\r]+/', (string)$data);

            $data = array();

            foreach ($lines as $line) {
                $line = explode('=', $line, 2);
                if (!empty($line[0])) {
                    $data[trim($line[0])] = isset($line[1]) ? trim($line[1]) : '';
                }
            }
        }

        $this->data = $data;
    }

    public function isSuccessful()
    {
        return isset($this->data['Status']) && 'OK' === $this->data['Status'];
    }

    /**
     * The only reason supported for a redirect from a Server transaction
     * will be 3D Secure. PayPal may come into this at some point.
     */
    public function isRedirect()
    {
        return isset($this->data['Status']) && '3DAUTH' === $this->data['Status'];
    }

    /**
     * Gateway Reference
     *
     * Sage Pay requires the original VendorTxCode as well as 3 separate
     * fields from the response object to capture or refund transactions at a later date.
     *
     * Active Merchant solves this dilemma by returning the gateway reference in the following
     * custom format: VendorTxCode;VPSTxId;TxAuthNo;SecurityKey
     *
     * We have opted to return this reference as JSON, as the keys are much more explicit.
     */
    public function getTransactionReference()
    {
        $reference = array();
        $reference['VendorTxCode'] = $this->getRequest()->getTransactionId();

        foreach (array('SecurityKey', 'TxAuthNo', 'VPSTxId') as $key) {
            if (isset($this->data[$key])) {
                $reference[$key] = $this->data[$key];
            }
        }

        ksort($reference);

        return json_encode($reference);
    }

    public function getStatus()
    {
        return isset($this->data['Status']) ? $this->data['Status'] : null;
    }

    public function getMessage()
    {
        return isset($this->data['StatusDetail']) ? $this->data['StatusDetail'] : null;
    }

    public function getRedirectUrl()
    {
        if ($this->isRedirect()) {
            return $this->data['ACSURL'];
        }
    }

    public function getRedirectMethod()
    {
        return 'POST';
    }

    public function getRedirectData()
    {
        if ($this->isRedirect()) {
            return array(
                'PaReq' => $this->data['PAReq'],
                'TermUrl' => $this->getRequest()->getReturnUrl(),
                'MD' => $this->data['MD'],
            );
        }
    }

    public function getToken()
    {
        return isset($this->data['Token']) ? $this->data['Token'] : null;
    }
}
