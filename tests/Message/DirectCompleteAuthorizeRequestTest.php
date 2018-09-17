<?php

namespace Omnipay\SagePay\Message;

use Omnipay\Tests\TestCase;
use Mockery as m;

class DirectCompleteAuthorizeRequestTest extends TestCase
{
    public function testDirectCompleteAuthorizeRequestSuccess()
    {
        parent::setUp();

        $this->request = new DirectCompleteAuthorizeRequest(
            $this->getHttpClient(),
            $this->getHttpRequest()
        );

        $this->request->initialize(
            array(
                'md' => '12345678ABCD',
                'paRes' => '12345678ABCD12345678ABCD12345678ABCD12345678ABCD',
            )
        );

        $this->assertSame(
            [
                'MD' => '12345678ABCD',
                'PARes' => '12345678ABCD12345678ABCD12345678ABCD12345678ABCD',
            ],
            $this->request->getData()
        );
    }
}
