<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Gateway\Response\Sop;

use PHPUnit\Framework\TestCase;

class TokenHandlerTest extends TestCase
{
    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderAdapterMock;

    /**
     * @var \Magento\Payment\Model\InfoInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /** @var TokenHandler */
    private $tokenHandler;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    /** @var \CyberSource\SecureAcceptance\Model\PaymentTokenManagement | \PHPUnit_Framework_MockObject_MockObject */
    private $paymentTokenManagementMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->paymentTokenManagementMock = $this->createMock(\CyberSource\SecureAcceptance\Model\PaymentTokenManagement::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Payment\Model\InfoInterface::class);
        $this->orderAdapterMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderAdapterMock);

        $this->tokenHandler = new TokenHandler(
            $this->subjectReaderMock,
            $this->paymentTokenManagementMock
        );
    }

    /**
     * @param $response
     * @param $tokenData
     * @param bool $vaultActive
     * @dataProvider  dataProviderTestHandle
     */
    public function testHandle($response, $tokenData, $vaultActive, $vaultTokenSaveInvocation)
    {
        $subject = [
            'payment' => $this->paymentDOMock
        ];

        $this->subjectReaderMock->method('readPayment')->willReturn($subject['payment']);

        $this->paymentTokenManagementMock->expects(static::once())->method('storeTokenIntoPayment')->with($this->paymentMock, $response['payment_token']);

        $this->paymentMock->method('getAdditionalInformation')->with('is_active_payment_token_enabler')->willReturn($vaultActive);
        $this->paymentMock->expects($vaultTokenSaveInvocation)->method('setAdditionalInformation')->with(
            'token_data',
            $tokenData
        );

        $this->tokenHandler->handle($subject, $response);
    }

    public function dataProviderTestHandle()
    {
        return [
            [
                'response' => [
                    'payment_token' => '123123',
                    'req_card_type' => '001',
                    'req_card_number' => '411111xxxxxx1111',
                    'req_card_expiry_date'=> '11/22',
                    'payment_token_instrument_identifier_id' => '23123123123',
                ],
                'tokenData' => [
                    'payment_token' => '123123',
                    'card_type' => '001',
                    'cc_last4' => '1111',
                    'card_expiry_date' => '11/22',
                    'card_bin' => '411111',
                    'instrument_id' => '23123123123',
                ],
                'vaultActive' => true,
                'vaultTokenSaveInvocation' => static::once(),
            ],

        ];
    }
}
