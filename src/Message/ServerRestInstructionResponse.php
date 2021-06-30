<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay REST Server Refund Response
 */
class ServerRestInstructionResponse extends RestResponse
{
    /**
     *
     * @return bool false
     */
    public function isSuccessful()
    {
        return $this->getInstructionType() ?? false;
    }

    /**
     * @return string|null instructionType if present
     */
    public function getInstructionType()
    {
        return $this->getDataItem('instructionType');
    }

    /**
     * @return string|null date if present
     */
    public function getInstructionDate()
    {
        return $this->getDataItem('date');
    }
}
