<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Gateway\Response\Soap;

use PHPUnit\Framework\TestCase;

class AdditionalInfoHandlerTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /** @var AdditionalInfoHandler */
    private $additionalInfoHandler;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);

        $this->additionalInfoHandler = new AdditionalInfoHandler(
            $this->subjectReaderMock
        );
    }

    /**
     * @dataProvider dataProviderTestHandle
     */
    public function testHandle($request, $expectedFields)
    {

        $subject = ['payment' => $this->paymentDOMock];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);

        $this->paymentMock->expects(static::any())->method('setAdditionalInformation')->willReturnCallback(
            function ($key, $value) use ($expectedFields) {

                if (isset($expectedFields[$key])) {
                    $this->assertEquals($expectedFields[$key], $value);
                    return;
                }

                $this->fail(
                    'Unexpected key: '
                    . $key
                    . ' Expected one of '
                    . implode(' ', array_keys($expectedFields))
                );
            }
        );

        $this->additionalInfoHandler->handle($subject, $request);
    }

    public function dataProviderTestHandle()
    {
        return [
            [
                'request' => [
                    'some' => ['reply' => 'value']
                ],
                'expectedFields' => ['some_reply' => 'value']
            ],
            [
                'request' => [
                    'some' => (object)['reply' => (object)['lala' => 'haha']]
                ],
                'expectedFields' => ['some_reply_lala' => 'haha']
            ],
            [
                'request' => [
                    'ccAuthReply' => [
                        'amount' => '100.00',
                    ]
                ],
                'expectedFields' => [
                    'ccAuthReply_amount' => '100.00',
                    'auth_amount' => '100.00',
                ]
            ],
        ];
    }
}
