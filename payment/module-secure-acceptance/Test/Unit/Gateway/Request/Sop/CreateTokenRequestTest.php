<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;

use PHPUnit\Framework\TestCase;

class CreateTokenRequestTest extends TestCase
{
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $encryptorMock;

    /**
     * @var \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sessionMock;

    /**
     * @var \Magento\Payment\Gateway\Data\AddressAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $addressMock;

    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    /**
     * @var \Magento\Framework\Math\Random|\PHPUnit_Framework_MockObject_MockObject
     */
    private $randomMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTimeMock;

    /**
     * @var \Magento\Sales\Model\Order\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /** @var CreateTokenRequest */
    private $createTokenRequest;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Config\Config | \PHPUnit_Framework_MockObject_MockObject */
    private $gatewayConfigMock;

    /** @var \CyberSource\SecureAcceptance\Helper\RequestDataBuilder | \PHPUnit_Framework_MockObject_MockObject */
    private $requestDataBuilderMock;

    /** @var \Magento\Framework\Locale\Resolver | \PHPUnit_Framework_MockObject_MockObject */
    private $localeResolverMock;

    /** @var \Magento\Framework\UrlInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $urlBuilderMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->gatewayConfigMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Config\Config::class);
        $this->requestDataBuilderMock = $this->createMock(\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::class);
        $this->localeResolverMock = $this->createMock(\Magento\Framework\Locale\Resolver::class);
        $this->urlBuilderMock = $this->createMock(\Magento\Framework\UrlInterface::class);
        $this->dateTimeMock = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class);
        $this->randomMock = $this->createMock(\Magento\Framework\Math\Random::class);
        $this->encryptorMock = $this->createMock(\Magento\Framework\Encryption\EncryptorInterface::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $this->orderMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);
        $this->addressMock = $this->createMock(\Magento\Payment\Gateway\Data\AddressAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderMock);
        $this->orderMock->expects(static::any())->method('getBillingAddress')->willReturn($this->addressMock);

        $this->sessionMock = $this->createMock(\Magento\Checkout\Model\Session::class);

        $this->createTokenRequest = new CreateTokenRequest(
            $this->subjectReaderMock,
            $this->gatewayConfigMock,
            $this->requestDataBuilderMock,
            $this->localeResolverMock,
            $this->urlBuilderMock,
            $this->dateTimeMock,
            $this->randomMock,
            $this->encryptorMock,
            $this->sessionMock
        );
    }

    /**
     * @param $expected
     *
     * @dataProvider dataProviderTestBuild
     */
    public function testBuild($expected)
    {
        $subject = ['payment' => $this->paymentDOMock];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);

        $this->gatewayConfigMock->method('isSilent')->willReturn(true);
        $this->gatewayConfigMock->method('getSopAccessKey')->willReturn($expected['access_key']);
        $this->gatewayConfigMock->method('getSopProfileId')->willReturn($expected['profile_id']);

        $this->randomMock->method('getUniqueHash')->willReturn($expected['transaction_uuid']);

        $this->requestDataBuilderMock->method('getLocale')->willReturn($expected['locale']);

        $this->orderMock->method('getId')->willReturn('1');
        $this->orderMock->method('getCurrencyCode')->willReturn('USD');

        $this->urlBuilderMock
            ->method('getUrl')
            ->with('cybersource/index/placeorder', ['_secure' => true])
            ->willReturn($expected['override_custom_receipt_page']);

        $this->dateTimeMock->method('gmtDate')->willReturn($expected['signed_date_time']);
        $this->requestDataBuilderMock->method('getSignedFields')->willReturn($expected['signed_field_names']);
        $this->requestDataBuilderMock->method('sign')->willReturn($expected['signature']);
        $this->requestDataBuilderMock->method('getCardType')->willReturn('001');

        $this->addressMock->method('getFirstname')->willReturn($expected['bill_to_forename']);
        $this->addressMock->method('getLastname')->willReturn($expected['bill_to_surname']);
        $this->addressMock->method('getEmail')->willReturn($expected['bill_to_email']);
        $this->addressMock->method('getStreetLine1')->willReturn($expected['bill_to_address_line1']);
        $this->addressMock->method('getStreetLine2')->willReturn($expected['bill_to_address_line2']);
        $this->addressMock->method('getCity')->willReturn($expected['bill_to_address_city']);
        $this->addressMock->method('getRegionCode')->willReturn($expected['bill_to_address_state']);
        $this->addressMock->method('getCountryId')->willReturn($expected['bill_to_address_country']);
        $this->addressMock->method('getPostcode')->willReturn($expected['bill_to_address_postal_code']);

        $this->gatewayConfigMock->method('getValue')->willReturnMap([
            ['token_skip_auto_auth', null, true],
            ['token_skip_decision_manager', null, false],
        ]);

        $sessionId = 'somesessionid';
        $this->sessionMock->method('getSessionId')->willReturn($sessionId);
        $this->encryptorMock->method('encrypt')->with($sessionId)->willReturn($sessionId);

        $this->assertEquals($expected, $this->createTokenRequest->build($subject));
    }

    public function dataProviderTestBuild()
    {
        return [
            [
                'expected' => [
                    'access_key' => '3232',
                    'profile_id' => '23232',
                    'transaction_uuid' => '35355353535',
                    'locale' => 'en_us',
                    'transaction_type' => 'create_payment_token',
                    'reference_number' => 'token_request_1',
                    'merchant_secure_data1' => '1',
                    'amount' => '0.00',
                    'currency' => 'USD',
                    'payment_method' => 'card',
                    'bill_to_forename' => 'test',
                    'bill_to_surname' => 'example',
                    'bill_to_email' => 'test@example.org',
                    'bill_to_address_country' => 'TE',
                    'bill_to_address_city' => 'TestVille',
                    'bill_to_address_state' => 'Testorado',
                    'bill_to_address_line1' => 'test line 1',
                    'bill_to_address_line2' => 'line 2',
                    'bill_to_address_postal_code' => '12312312',
                    'skip_decision_manager' => 'false',
                    'override_custom_receipt_page' => 'http://example.org/placeorder',
                    'signed_date_time' => 'somedatetime',
                    'signed_field_names' => 'fields_list',
                    'signature' => '32323232',
                    'card_type' => '001',
                    'unsigned_field_names' => 'card_type,card_number,card_expiry_date,card_cvn',
                    'skip_auto_auth' => 'true',
                    'merchant_secure_data2' => 'somesessionid',
                ],
            ],
        ];
    }
}
