<?php
/**
 * First Data Payeezy Response
 */

namespace Omnipay\FirstData\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\FirstData\Message\AchResponseHelper;
use Omnipay\FirstData\Message\ResponseHelper;

/**
 * First Data Payeezy Response
 *
 * ### Quirks
 *
 * This gateway requires both a transaction reference (aka an authorization number)
 * and a transaction tag to implement either voids or refunds.  These are referred
 * to in the documentation as "tagged refund" and "tagged voids".
 *
 * The transaction reference returned by this class' getTransactionReference is a
 * concatenated value of the authorization number and the transaction tag.
 */
class PayeezyResponse extends AbstractResponse
{
    use ResponseHelper, AchResponseHelper;

    public function __construct(RequestInterface $request, $data)
    {
        $this->request = $request;
        $this->data = json_decode($data, true);

        if($this->data === "Unauthorized Request. Bad or missing credentials."){
            throw new InvalidAuthResponseException("Unauthorized Request. Bad or missing credentials.");
        }
        if($this->data == null){
            throw new InvalidResponseException($data ?? "No Response");
        }
    }

    public function isSuccessful()
    {
        return ($this->data['transaction_approved'] == '1') ? true : false;
    }

    /**
     * Get an item from the internal data array
     *
     * This is a short cut function to ensure that we test that the item
     * exists in the data array before we try to retrieve it.
     *
     * @param $itemname
     * @return mixed|null
     */
    public function getDataItem($itemname)
    {
        if (isset($this->data[$itemname])) {
            return $this->data[$itemname];
        }

        return null;
    }

    /**
     * Get the authorization number
     *
     * This is the authorization number returned by the cardholder’s financial
     * institution when a transaction has been approved. This value overrides any
     * value sent for the Request Property of the same name.
     *
     * @return integer
     */
    public function getAuthorizationNumber()
    {
        return $this->getDataItem('authorization_num');
    }

    /**
     * Get the transaction tag.
     *
     * A unique identifier to associate with a tagged transaction. This value overrides
     * any value sent for the Request Property of the same name.
     *
     * @return string
     */
    public function getTransactionTag()
    {
        return $this->getDataItem('transaction_tag');
    }

    /**
     * Get the transaction reference
     *
     * Because refunding or voiding a transaction requires both the authorization number
     * and the transaction tag, we concatenate them together to make the transaction
     * reference.
     *
     * @return string
     */
    public function getTransactionReference()
    {
        return $this->getAuthorizationNumber() . '::' . $this->getTransactionTag();
    }

    /**
     * Get the transaction ID as generated by the merchant website.
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->getDataItem('reference_no');
    }

    /**
     * Get the transaction sequence number.
     *
     * A digit sequentially incremented number generated by Global Gateway e4 and passed
     * through to the financial institution. It is also passed back to the client in the
     * transaction response. This number can be used for tracking and audit purposes.
     *
     * @return string
     */
    public function getSequenceNo()
    {
        return $this->getDataItem('sequence_no');
    }

    /**
     * Get the credit card reference for a completed transaction.
     *
     * This is only provided if TransArmor processing is turned on for the gateway.
     *
     * @return string
     */
    public function getCardReference()
    {
        return $this->getDataItem('transarmor_token');
    }

    /**
     * Get Message
     *
     * A human readable message response and if not then the bank message.
     *
     * @return string
     */
    public function getMessage()
    {
        $message = $this->getDataItem('bank_message');
        if (empty($message)) {
            $message = $this->getDataItem('exact_message');
        }
        return $message;
    }

    /**
     * Get Bank Response Message
     *
     * A message provided by the financial institution describing the Response code above.
     *
     * @return string
     */
    public function getBankMessage(){
        return $this->getDataItem('bank_message');
    }

    /**
     * Get the Exact Message.
     *
     * Message that accompanies the Exact_Resp_code.
     *
     * @return string
     */
    public function getExactMessage(){
        return $this->getDataItem('exact_message');
    }

    /**
     * Get the error code.
     *
     * This property indicates the processing status of the transaction. Please refer
     * to the section on Exception Handling for further information. The Transaction_Error
     * property will return True if this property is not “00”.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->getDataItem('exact_resp_code');
    }

    /**
     * Get the bank response code.
     *
     * This is a 2 or 3 digit code, provided by the financial institution, indicating the
     * approval status of a transaction. The meaning of these codes is defined by the
     * various financial institutions and is not under the control of the Payeezy Gateway.
     *
     * @return string
     */
    public function getBankCode()
    {
        return $this->getDataItem('bank_resp_code');
    }

}
