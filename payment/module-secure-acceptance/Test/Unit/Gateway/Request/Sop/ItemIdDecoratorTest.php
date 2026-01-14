<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;

use PHPUnit\Framework\TestCase;

class ItemIdDecoratorTest extends TestCase
{
    /** @var ItemIdDecorator */
    private $itemIdDecorator;

    /** @var \Magento\Framework\ObjectManager\TMapFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $tmapFactory;


    /** @var string */
    private $prefix;

    protected function setUp()
    {
        $this->tmapFactory = $this->createMock(\Magento\Framework\ObjectManager\TMapFactory::class);
        $this->prefix = 'item_';
    }

    /**
     * @dataProvider dataProviderTestBuild
     *
     * @param $items
     * @param $expected
     *
     */
    public function testBuild($items, $expected)
    {

        $subject = ['payment' => ' test'];

        $this->tmapFactory->method('create')->willReturnCallback(
            function ($arg) {
                return $arg['array'];
            }
        );

        $builders = [];

        foreach ($items as $item) {
            $builderMock = $this->createMock(\Magento\Payment\Gateway\Request\BuilderInterface::class);
            $builderMock->method('build')->with($subject)->willReturn($item);
            $builders[] = $builderMock;
        }

        $this->itemIdDecorator = new ItemIdDecorator(
            $this->tmapFactory,
            $builders,
            $this->prefix
        );


        $this->assertEquals($expected, $this->itemIdDecorator->build($subject));

    }

    public function dataProviderTestBuild()
    {
        return [
            [
                'items' => [
                    [
                        [
                            'param1' => 'paramValue1',
                            'param2' => 'paramValue2',
                        ],
                    ],
                ],
                'expected' => [
                    'item_0_param1' => 'paramValue1',
                    'item_0_param2' => 'paramValue2',
                    'line_item_count' => 1
                ],
            ],
            [
                'items' => [
                    [
                        [
                            'param1' => 'paramValue1',
                            'param2' => 'paramValue2',
                        ],
                        [
                            'param1' => 'paramValue1',
                            'param2' => 'paramValue2',
                        ],
                    ],
                ],
                'expected' => [
                    'item_0_param1' => 'paramValue1',
                    'item_0_param2' => 'paramValue2',
                    'item_1_param1' => 'paramValue1',
                    'item_1_param2' => 'paramValue2',
                    'line_item_count' => 2
                ],
            ],
        ];
    }

}
