<?php declare(strict_types=1);

namespace CyberSource\ThreeDSecure\Gateway\Validator;

use PHPUnit\Framework\TestCase;

class PaEnrolledValidatorTest extends TestCase
{
    /** @var PaEnrolledValidator */
    private $paEnrolledValidator;

    /** @var \Magento\Payment\Gateway\Validator\ResultInterfaceFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $resultFactory;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReader;

    /** @var \Magento\Payment\Gateway\Request\BuilderInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $builder;

    protected function setUp()
    {
        $this->resultFactory = $this->createMock(\Magento\Payment\Gateway\Validator\ResultInterfaceFactory::class);
        $this->subjectReader = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->builder = $this->createMock(\Magento\Payment\Gateway\Request\BuilderInterface::class);
        $this->paEnrolledValidator = new PaEnrolledValidator(
            $this->resultFactory,
            $this->subjectReader,
            $this->builder
        );
    }

    public function testValidate()
    {

        $subject = [
            'response' => [
                'reasonCode' => 475,
                'payerAuthEnrollReply' => [
                    'acsURL' => 'htts://example.com/',
                    'paReq' => 'qpkwcqkpmcqwmcpkqmwpcpwqmcpqmwc',
                    'authenticationTransactionID' => 'qpwmdpqwmdpqwd'
                ],
            ]
        ];

        $this->builder->expects(static::any())->method('build')->with($subject)->willReturn(['some' => 'data']);

        $this->subjectReader->expects(static::any())->method('readResponse')->with($subject)->willReturn($subject['response']);


        try {
            $this->paEnrolledValidator->validate($subject);
        } catch (\Exception $e) {
        }

        /* @var $e \CyberSource\ThreeDSecure\Gateway\PaEnrolledException */
        $this->assertInstanceOf(\CyberSource\ThreeDSecure\Gateway\PaEnrolledException::class, $e);
        $this->assertEquals(475, $e->getCode());
        $this->assertEquals(
            [
                'cca' => [
                    'AcsUrl' => $subject['response']['payerAuthEnrollReply']['acsURL'],
                    'Payload' => $subject['response']['payerAuthEnrollReply']['paReq']
                ],
                'order' => [
                    'OrderDetails' => [
                        'TransactionId' => $subject['response']['payerAuthEnrollReply']['authenticationTransactionID'],

                    ],
                    'some' => 'data'
                ],

            ],
            $e->getDetails()
        );
    }

    public function testValidateNotEnrolled()
    {
        $subject = [
            'response' => [
                'reasonCode' => 100,
            ]
        ];

        $this->subjectReader->expects(static::any())->method('readResponse')->with($subject)->willReturn($subject['response']);

        $validationResult = $this->createMock(\Magento\Payment\Gateway\Validator\ResultInterface::class);

        $this->resultFactory
            ->expects(static::once())
            ->method('create')
            ->with(['isValid' => true, 'failsDescription' => []])
            ->willReturn($validationResult)
        ;

        $this->assertEquals($validationResult, $this->paEnrolledValidator->validate($subject));
    }
}
