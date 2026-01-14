<?php
/**
 *
 */

namespace CyberSource\SecureAcceptance\Controller\Adminhtml\Microform;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\Data\OrderInterface as DataOrderInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\Translate\InlineInterface;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order;
use Magento\Framework\UrlInterface;



class FlexPlaceOrder extends \Magento\Sales\Controller\Adminhtml\Order
{

    const KEY_FLEX_TOKEN = 'token';
    const KEY_CARD_TYPE = 'ccType';
    const KEY_EXP_DATE = 'expDate';
    const KEY_FLEX_MASKED_PAN = 'maskedPan';

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var \Magento\Payment\Gateway\Command\CommandManagerInterface
     */
    protected $commandManager;

    /**
     * @var \Magento\Framework\Session\SessionManager
     */
    protected $sessionManager;

    /**
     * @var \CyberSource\Core\Model\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $session;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface
     */
    protected $jwtProcessor;

    /**
     * @var QuoteManagement 
     */
    protected $quoteManagement;
    
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var /Magento
     */
    protected $order;

    /**
     * TokenRequest constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Payment\Gateway\Command\CommandManagerInterface $commandManager
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \CyberSource\Core\Model\LoggerInterface $logger
     */
    public function __construct(
        Action\Context $context,
        Registry $coreRegistry,
        FileFactory $fileFactory,
        InlineInterface $translateInline,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        LayoutFactory $resultLayoutFactory,
        RawFactory $resultRawFactory,
        OrderManagementInterface $orderManagement,
        \Magento\Payment\Gateway\Command\CommandManagerInterface $commandManager,
        \Magento\Framework\Session\SessionManager $sessionManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \CyberSource\Core\Model\LoggerInterface $logger,
		\Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface $jwtProcessor,
        \Magento\Backend\Model\Session\Quote $session,
        QuoteManagement $quoteManagement,
        UrlInterface $urlBuilder
    ) {
        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $translateInline,
            $resultPageFactory,
            $resultJsonFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $orderManagement,
            $orderRepository,
            $logger
        );
        $this->resultJsonFactory = $resultJsonFactory;
        $this->commandManager = $commandManager;
        $this->sessionManager = $sessionManager;
        $this->logger = $logger;
        $this->formKeyValidator = $formKeyValidator;
        $this->quoteRepository = $quoteRepository;
		$this->orderRepository = $orderRepository;
        $this->jwtProcessor = $jwtProcessor;
        $this->session = $session;
        $this->quoteManagement = $quoteManagement;
        $this->urlBuilder = $urlBuilder;
    }


    /**
     * Creates SA request JSON
     *
     * @return \Magento\Framework\Controller\ResultInterface|\Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        try {
            
            if (!$this->getRequest()->isPost()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Wrong method.'));
            }
            
            /** @var array $data */
            $data = $this->getRequest()->getParams();
            
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->session->getQuote();
            if (!$quote || !$quote->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Unable to load cart data.'));
            }
            $payment = $quote->getPayment();
            
            $quote->reserveOrderId();
            
            if (!$this->formKeyValidator->validate($this->getRequest())) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid formkey.'));
            }
            
            $this->quoteRepository->save($quote);

            $orderData = $data['order_data'];
            
            $this->session->setOrderDataPost($orderData);
            $cardType = $data['ccType'];
            $token = $data['token'];
            $ccExpDate =  $data['expDate'];
            $payment->setAdditionalInformation('flexJwt', $token);
            if ($flexPaymentToken = $this->jwtProcessor->getFlexPaymentToken($token)) {
                $payment->setAdditionalInformation('transientToken', $flexPaymentToken);
            }
            if ($cardData = $this->jwtProcessor->getCardData($token)) {
                $maskedPan = $payment->getAdditionalInformation('maskedPan') ?? '';
                $cardNumber=substr($maskedPan, 0, 6) . str_repeat('X', strlen($maskedPan) - 10) . substr($maskedPan, -4);
                $payment->setAdditionalInformation(static::KEY_FLEX_MASKED_PAN, $cardNumber ?? null);
                $payment->setAdditionalInformation('cardType', $cardData['type'] ?? null);
            }

            $payment->setAdditionalInformation('cardType', $cardType);
            $payment->setAdditionalInformation(static::KEY_EXP_DATE, $ccExpDate);
            
            $this->order = $this->quoteManagement->submit($quote);
            if($this->order === null)
            {
                throw new \Magento\Framework\Exception\LocalizedException(__('Order Creation Failed.'));
            }
            $this->orderRepository->save($this->order);
            
            $redirectUrl = $this->urlBuilder->getUrl('sales/order/view',array('order_id' => $this->order->getId()));
            $result->setData(
                [
                    'redirect_url' => $redirectUrl,
                    'success' => true,
                    'error' => false
                ]
            );
            $this->messageManager->addSuccessMessage(__('Your Order has been successfully created.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->getErrorResponse($e->getMessage());
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Unable to make Payment! Try Again'));
            $this->logger->error($e->getMessage());
            return $this->getErrorResponse($e->getMessage());
        }

        return $result;
    }

    /**
     *
     * Returns error JSON.
     *
     * @param null|string $message
     * @return \Magento\Framework\Controller\Result\Json
     */
    private function getErrorResponse($message = null)
    {
        $message = $message ? $message : __('Your payment has been declined. Please try again.');
        $redirectUrl = $this->urlBuilder->getUrl('sales/order_create/index',['_secure' => true]);
        return $this->resultJsonFactory->create()->setData(
            [
                'redirect_url' => $redirectUrl,
                'success' => false,
                'error' => true,
                'error_messages' => $message
            ]
        );
    }
}
