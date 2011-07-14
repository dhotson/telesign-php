<?php

/**
 * For verifying phone numbers with the telesign.com service
 */
class Telesign
{
	private $_customerId;
	private $_authenticationId;
	private $_soapClient;

	/**
	 * @param string $customerId Your telesign customer id
	 * @param string $authenticationId Your telesign authentication id
	 * @param string (optional) URL to the telesign SOAP wsdl file
	 */
	public function __construct($customerId, $authenticationId, $wsdlUrl=null)
	{
		$this->_customerId = $customerId;
		$this->_authenticationId = $authenticationId;

		$wsdlUrl = isset($wsdlUrl) ? $wsdlUrl : 'https://api.telesign.com/1.x/soap.asmx?WSDL';
		$this->_soapClient = new SoapClient($wsdlUrl);
	}

	/**
	 * Sends a verification code in a phone call
	 * @param string $countryCode eg. '1' for USA, '61' for australia
	 * @param string $phoneNumber
	 * @param string $language eg null for US / australian / englishuk / @see telesign docs for more
	 * @return object eg. {code: verification code, referenceid: telesign reference id}
	 */
	public function call($countryCode, $phoneNumber, $language=null)
	{
		$code = $this->_randomNumber();

		$result = $this->_soapClient->RequestCALL(array(
			'CustomerID' => $this->_customerId,
			'AuthenticationID' => $this->_authenticationId,
			'CountryCode' => $countryCode,
			'PhoneNumber' => $phoneNumber,
			'VerificationCode' => $code,
			'Message' => $language,
		));

		$error = $result->RequestCALLResult->APIError;
		if ($error->Code !== 0)
			throw new Exception("$error->Code - $error->Message");

		return (object)array(
			'code' => $code,
			'referenceid' => $result->RequestCALLResult->ReferenceID,
		);
	}

	/**
	 * Sends a verification code in an SMS
	 * @param string $countryCode eg. '1' for USA, '61' for australia
	 * @param string $phoneNumber
	 * @param string $language eg null for US / australian / englishuk / @see telesign docs for more
	 * @return object eg. {code: The verification code sent, referenceid: The telesign reference id}
	 */
	public function sms($countryCode, $phoneNumber, $language=null)
	{
		$code = $this->_randomNumber();

		$result = $this->_soapClient->RequestSMS(array(
			'CustomerID' => $this->_customerId,
			'AuthenticationID' => $this->_authenticationId,
			'CountryCode' => $countryCode,
			'PhoneNumber' => $phoneNumber,
			'VerificationCode' => $code,
			'Message' => $language,
		));

		$error = $result->RequestSMSResult->APIError;
		if ($error->Code !== 0)
			throw new Exception("$error->Code - $error->Message");

		return (object)array(
			'code' => $code,
			'referenceid' => $result->RequestSMSResult->ReferenceID,
		);
	}

	public function _randomNumber($digits=4)
	{
		return rand(pow(10, $digits - 1), pow(10, $digits) - 1);
	}
}
