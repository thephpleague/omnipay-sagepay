<?php

namespace Omnipay\SagePay;

/**
 * A convenient place to put all the gateway constants,
 * for use in all classes.
 */

interface ConstantsInterface
{
    //
    // First the request constants.
    //

    /**
     * Flag whether to allow the gift aid acceptance box to appear for this
     * transaction on the payment page. This only appears if your vendor
     * account is Gift Aid enabled.
     */
    const ALLOW_GIFT_AID_YES = 1;
    const ALLOW_GIFT_AID_NO  = 0;

    /**
     * Supported 3D Secure values for Apply3DSecure.
     * 0: APPLY - If 3D-Secure checks are possible and rules allow,
     *      perform the checks and apply the authorisation rules.
     *      (default)
     * 1: FORCE - Force 3D-Secure checks for this transaction if
     *      possible and apply rules for authorisation.
     * 2: NONE - Do not perform 3D-Secure checks for this
     *      transaction and always authorise.
     * 3: AUTH - Force 3D-Secure checks for this transaction if
     *      possible but ALWAYS obtain an auth code, irrespective
     *      of rule base.
     *
     * @var integer
     */
    const APPLY_3DSECURE_APPLY  = 0;
    const APPLY_3DSECURE_FORCE  = 1;
    const APPLY_3DSECURE_NONE   = 2;
    const APPLY_3DSECURE_AUTH   = 3;

    /**
     * Supported AVS/CV2 values.
     *
     * 0: DEFAULT will use the account settings for checks and applying of rules.
     * 1: FORCE_CHECKS will force checks to be made.
     * 2: NO_CHECKS will force no checks to be performed.
     * 3: NO_RULES will force no rules to be applied.
     *
     * @var integer
     */
    const APPLY_AVSCV2_DEFAULT      = 0;
    const APPLY_AVSCV2_FORCE_CHECKS = 1;
    const APPLY_AVSCV2_NO_CHECKS    = 2;
    const APPLY_AVSCV2_NO_RULES     = 3;

    /**
     * Flag whether to store a cardReference or token for multiple use.
     */
    const STORE_TOKEN_YES   = 1;
    const STORE_TOKEN_NO    = 0;

    /**
     * Flag whether to create a cardReference or token for the CC supplied.
     */
    const CREATE_TOKEN_YES   = 1;
    const CREATE_TOKEN_NO    = 0;

    /**
     * Profile for Sage Pay Server hosted forms.
     * - NORMAL for full page forms.
     * - LOW for use in iframes.
     */
    const PROFILE_NORMAL    = 'NORMAL';
    const PROFILE_LOW       = 'LOW';

    /**
     * The values for the AccountType field.
     * E – for ecommerce transactions (default)
     * M – for telephone (MOTO) transactions
     * C – for repeat transactions
     *
     * @var string
     */
    const ACCOUNT_TYPE_E = 'E';
    const ACCOUNT_TYPE_M = 'M';
    const ACCOUNT_TYPE_C = 'C';

    /**
     * The transaction type.
     * These will usually be returned in the response matching the
     * request.
     * @var string
     */
    const TXTYPE_PAYMENT        = 'PAYMENT';
    const TXTYPE_DEFERRED       = 'DEFERRED';
    const TXTYPE_AUTHENTICATE   = 'AUTHENTICATE';
    const TXTYPE_REMOVETOKEN    = 'REMOVETOKEN';
    const TXTYPE_TOKEN          = 'TOKEN';
    const TXTYPE_RELEASE        = 'RELEASE';
    const TXTYPE_AUTHORISE      = 'AUTHORISE';
    const TXTYPE_VOID           = 'VOID';
    const TXTYPE_ABORT          = 'ABORT';
    const TXTYPE_REFUND         = 'REFUND';
    const TXTYPE_REPEAT         = 'REPEAT';
    const TXTYPE_REPEATDEFERRED = 'REPEATDEFERRED';
    const TXTYPE_COMPLETE       = 'COMPLETE';

    /**
     *
     */
    const SERVICE_SERVER_REGISTER   = 'vspserver-register';
    const SERVICE_DIRECT_REGISTER   = 'vspdirect-register';
    const SERVICE_REPEAT            = 'repeat';
    const SERVICE_TOKEN             = 'directtoken';
    const SERVICE_DIRECT3D          = 'direct3dcallback';
    const SERVICE_PAYPAL            = 'complete';

    /**
     * 0 = Do not send either customer or vendor emails
     * 1 = Send customer and vendor emails if addresses are provided
     * 2 = Send vendor email but NOT the customer email
     * If you do not supply this field, 1 is assumed and emails
     * are sent if addresses are provided.
     */
    const SEND_EMAIL_NONE = '0';
    const SEND_EMAIL_BOTH = '1';
    const SEND_EMAIL_VENDOR = '2';

    //
    // Then the response constants.
    //

    /**
     * There are a wide range of status codes across the different gatweay types
     * and in response to different types of request.
     * @var string
     */
    const SAGEPAY_STATUS_OK             = 'OK';
    const SAGEPAY_STATUS_OK_REPEATED    = 'OK REPEATED';
    const SAGEPAY_STATUS_PENDING        = 'PENDING';
    const SAGEPAY_STATUS_NOTAUTHED      = 'NOTAUTHED';
    const SAGEPAY_STATUS_REJECTED       = 'REJECTED';
    const SAGEPAY_STATUS_AUTHENTICATED  = 'AUTHENTICATED';
    const SAGEPAY_STATUS_REGISTERED     = 'REGISTERED';
    const SAGEPAY_STATUS_3DAUTH         = '3DAUTH';
    const SAGEPAY_STATUS_PPREDIRECT     = 'PPREDIRECT';
    const SAGEPAY_STATUS_ABORT          = 'ABORT';
    const SAGEPAY_STATUS_MALFORMED      = 'MALFORMED';
    const SAGEPAY_STATUS_INVALID        = 'INVALID';
    const SAGEPAY_STATUS_ERROR          = 'ERROR';
    const SAGEPAY_STATUS_PAYPALOK       = 'PAYPALOK';

    /**
     * Raw values for AddressResult
     * @var string
     */
    const ADDRESS_RESULT_NOTPROVIDED    = 'NOTPROVIDED';
    const ADDRESS_RESULT_NOTCHECKED     = 'NOTCHECKED';
    const ADDRESS_RESULT_MATCHED        = 'MATCHED';
    const ADDRESS_RESULT_NOTMATCHED     = 'NOTMATCHED';

    /**
     * Raw values for PostCodeResult
     * @var string
     */
    const POSTCODE_RESULT_NOTPROVIDED   = 'NOTPROVIDED';
    const POSTCODE_RESULT_NOTCHECKED    = 'NOTCHECKED';
    const POSTCODE_RESULT_MATCHED       = 'MATCHED';
    const POSTCODE_RESULT_NOTMATCHED    = 'NOTMATCHED';

    /**
     * Raw values for CV2Result
     * @var string
     */
    const CV2_RESULT_NOTPROVIDED        = 'NOTPROVIDED';
    const CV2_RESULT_NOTCHECKED         = 'NOTCHECKED';
    const CV2_RESULT_MATCHED            = 'MATCHED';
    const CV2_RESULT_NOTMATCHED         = 'NOTMATCHED';

    /**
     * Raw values for AVSCV2
     * @var string
     */
    const AVSCV2_RESULT_ALLMATCH            = 'ALLMATCH';
    const AVSCV2_RESULT_SECURITY_CODE_ONLY  = 'SECURITY CODE MATCH ONLY';
    const AVSCV2_RESULT_ADDRESS_ONLY        = 'ADDRESS MATCH ONLY';
    const AVSCV2_RESULT_NO_DATA             = 'NO DATA MATCHES';
    const AVSCV2_RESULT_NOT_CHECKED         = 'DATA NOT CHECKED';

    /**
     * Raw values for GiftAidResult (Sage Pay Serverv only)
     * @var string
     */
    const GIFTAID_CHECKED_TRUE  = '1';
    const GIFTAID_CHECKED_FALSE = '0';

    /**
     * Raw results for 3DSecureStatus
     * @var string
     */
    const SECURE3D_STATUS_OK            = 'OK';
    const SECURE3D_STATUS_NOTCHECKED    = 'NOTCHECKED';
    const SECURE3D_STATUS_NOTAVAILABLE  = 'NOTAVAILABLE';
    const SECURE3D_STATUS_NOTAUTHED     = 'NOTAUTHED';
    const SECURE3D_STATUS_INCOMPLETE    = 'INCOMPLETE';
    const SECURE3D_STATUS_ATTEMPTONLY   = 'ATTEMPTONLY';
    const SECURE3D_STATUS_ERROR         = 'ERROR';
    const SECURE3D_STATUS_NOAUTH        = 'NOAUTH';
    const SECURE3D_STATUS_CANTAUTH      = 'CANTAUTH';
    const SECURE3D_STATUS_MALFORMED     = 'MALFORMED';
    const SECURE3D_STATUS_INVALID       = 'INVALID';

    /**
     * Raw results for AddressStatus (PayPal only)
     * @var string
     */
    const ADDRESS_STATUS_NONE           = 'NONE';
    const ADDRESS_STATUS_CONFIRMED      = 'CONFIRMED';
    const ADDRESS_STATUS_UNCONFIRMED    = 'UNCONFIRMED';

    /**
     * Raw results for PayerStatus (PayPal only)
     * @var string
     */
    const PAYER_STATUS_VERIFIED     = 'VERIFIED';
    const PAYER_STATUS_UNVERIFIED   = 'UNVERIFIED';

    /**
     * The raw recorded card type that was used (Sage Pay Server).
     * TODO: a translation to OmniPay card brands would be useful.
     * @var string
     */
    const CARDTYPE_VISA     = 'VISA';
    const CARDTYPE_MC       = 'MC';
    const CARDTYPE_MCDEBIT  = 'MCDEBIT';
    const CARDTYPE_DELTA    = 'DELTA';
    const CARDTYPE_MAESTRO  = 'MAESTRO';
    const CARDTYPE_UKE      = 'UKE';
    const CARDTYPE_AMEX     = 'AMEX';
    const CARDTYPE_DC       = 'DC';
    const CARDTYPE_JCB      = 'JCB';
    const CARDTYPE_PAYPAL   = 'PAYPAL';

    /**
     * The raw FraudResponse values.
     * @var string
     */
    const FRAUD_RESPONSE_ACCEPT     = 'ACCEPT';
    const FRAUD_RESPONSE_CHALLENGE  = 'CHALLENGE';
    const FRAUD_RESPONSE_DENY       = 'DENY';
    const FRAUD_RESPONSE_NOTCHECKED = 'NOTCHECKED';
}
