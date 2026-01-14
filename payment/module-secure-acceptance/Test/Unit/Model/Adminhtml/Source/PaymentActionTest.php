<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace CyberSource\SecureAcceptance\Test\Unit\Model\Adminhtml\Source;

use CyberSource\SecureAcceptance\Model\Adminhtml\Source\PaymentAction;

class PaymentActionTest extends \PHPUnit\Framework\TestCase
{
    public function testToOptionArray()
    {
        $sourceModel = new PaymentAction();

        static::assertEquals(
            [
                [
                    'value' => \CyberSource\SecureAcceptance\Model\Adminhtml\Source\PaymentAction::ACTION_AUTHORIZE,
                    'label' => __('Authorize Only')
                ],
                [
                    'value' => \CyberSource\SecureAcceptance\Model\Adminhtml\Source\PaymentAction::ACTION_AUTHORIZE_CAPTURE,
                    'label' => __('Authorize and Capture')
                ],
            ],
            $sourceModel->toOptionArray()
        );
    }
}
