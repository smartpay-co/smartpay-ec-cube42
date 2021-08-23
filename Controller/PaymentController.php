<?php

/*
 * This file is part of Smartpay
 *
 * Copyright(c) Smartpay Solutions PTE. LTD. All Rights Reserved.
 *
 * https://homepage.smartpay.ninja/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Smartpay\Controller;


use Eccube\Common\EccubeConfig;
use Eccube\Controller\AbstractShoppingController;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Service\OrderHelper;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\Smartpay\Entity\Config;
use Plugin\Smartpay\Entity\PaymentStatus;
use Plugin\Smartpay\Repository\ConfigRepository;
use Plugin\Smartpay\Repository\PaymentStatusRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class StripeController
 * @package Plugin\Stripe4\Controller
 *
 * @Route("/shopping/smartpay")
 */
class PaymentController extends AbstractShoppingController
{
    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var MailService
     */
    private $mailService;

    /**
     * @var PaymentStatusRepository
     */
    private $paymentStatusRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ParameterBag
     */
    private $parameterBag;

    public function __construct(
        CartService $cartService,
        OrderHelper $orderHelper,
        EccubeConfig $eccubeConfig,
        OrderStatusRepository $orderStatusRepository,
        OrderRepository $orderRepository,
        MailService $mailService,
        PaymentStatusRepository $paymentStatusRepository,
        ConfigRepository $configRepository,
        ParameterBag $parameterBag
    )
    {
        $this->cartService = $cartService;
        $this->orderHelper = $orderHelper;
        $this->eccubeConfig = $eccubeConfig;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->orderRepository = $orderRepository;
        $this->mailService = $mailService;
        $this->paymentStatusRepository = $paymentStatusRepository;
        $this->config = $configRepository->get();
        $this->parameterBag = $parameterBag;
    }

    /**
     * @return RedirectResponse
     * @throws \Smartpay\Exception\ApiErrorException
     *
     * @Route("/payment", name="shopping_smartpay_payment")
    */
    public function payment(): RedirectResponse
    {
        // 受注情報の取得
        /** @var Order $Order 受注情報の取得 */
        $Order = $this->parameterBag->get('smartpay.Order');

        if (!$Order) {
            return $this->redirectToRoute('shopping_error');
        }

        try {
            $publicKey = getenv('SMARTPAY_PUBLIC_KEY');
            // $secretKey = getenv('SMARTPAY_SECRET_KEY');
            // $url = "{$this->config->getAPIPrefix()}/sessions";
            // $data = array('lineItems' => array(
            //     array(
            //         'name' => 'レブロン 18 LOW',
            //         'price' => 19250,
            //         'currency' => 'JPY',
            //         'quantity' => 1,
            //     ),
            //     array(
            //         'name' => 'レブロン 20 LOW',
            //         'price' => 60523,
            //         'currency' => 'JPY',
            //         'quantity' => 2,
            //     )
            // ));
            // $options = array(
            //   'http' => array(
            //       'header'  => array(
            //         "Accept: application/json\r\n",
            //         "Authorization: Bearer {$secretKey}\r\n",
            //         "Content-type: application/json\r\n\r\n"
            //       ),
            //       'method'  => 'POST',
            //       'content' => http_build_query($data)
            //   )
            // );

            // $context  = stream_context_create($options);
            // $checkoutSession = file_get_contents($url, false, $context);
            // if ($checkoutSession === false) { 
            //   $this->addError($e->getMessage());
            //   return $this->redirectToRoute('shopping_error');
            // }

            // @TODO: temporarily since the sessions API is not ready
            $checkoutSession = array('id' => 'session_id_1');
            $sessionID = $checkoutSession['id'];

            // Memorize checkout id
            $Order->setSmartpayPaymentCheckoutID($sessionID);

            // Build redirect URL params
            $successURL = getenv('SMARTPAY_SUCCESS_URL');
            $cancelURL = getenv('SMARTPAY_CANCEL_URL');

            if (!$successURL || !$cancelURL) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $successURL = "{$protocol}{$_SERVER['HTTP_HOST']}/shopping/smartpay/payment/complete/{$Order->getId()}";
                $cancelURL = "{$protocol}{$_SERVER['HTTP_HOST']}/shopping/smartpay/payment/cancel/{$Order->getId()}";    
            }

            $params = "session={$sessionID}&key={$publicKey}&success_url={$successURL}&cancel_url={$cancelURL}";

            header("Location: {$this->config->getCheckoutURL()}/login?{$params}");
            exit;
        } catch (\Exception $e) {
            $this->rollbackOrder($Order);
            $this->addError($e->getMessage());
            return $this->redirectToRoute('shopping_error');
        }
    }

    /**
     * @return RedirectResponse
     * @throws \Smartpay\Exception\ApiErrorException
     *
     * @Route("/payment/complete/{id}", name="shopping_smartpay_payment_complete")
     */
    public function paymentComplete(string $id): RedirectResponse
    {
        try {
            $Order = $this->orderRepository->findOneBy([
                'id' => $id,
            ]);

            if (null === $Order) {
                $this->addError('受注情報が存在しません');
                return $this->redirectToRoute('shopping_error');
            }

            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::NEW);
            $Order->setOrderStatus($OrderStatus);

            $PaymentStatus = $this->paymentStatusRepository->find(PaymentStatus::PROVISIONAL_SALES);
            $Order->setSmartpayPaymentStatus($PaymentStatus);

            $this->purchaseFlow->commit($Order, new PurchaseContext());
            $this->completeShopping($Order);

            return $this->redirectToRoute('shopping_complete');
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
            return $this->redirectToRoute('shopping_error');
        }
    }

    /**
     * @return RedirectResponse
     * @throws \Smartpay\Exception\ApiErrorException
     *
     * @Route("/payment/cancel/{id}", name="shopping_smartpay_payment_cancel")
     */
    public function paymentCancel(string $id): RedirectResponse
    {
        try {
            $Order = $this->orderRepository->findOneBy([
                'id' => $id,
            ]);

            if (null === $Order) {
                $this->addError('受注情報が存在しません');
                return $this->redirectToRoute('shopping_error');
            }
            
            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PROCESSING);
            $Order->setOrderStatus($OrderStatus);

            $this->addError('Smartpay決済がキャンセルされました');
            $this->cancelShopping($Order);

            return $this->redirectToRoute('shopping_error');
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
            return $this->redirectToRoute('shopping_error');
        }
    }

    /**
     * @param Order $Order
     */
    protected function completeShopping(Order $Order)
    {
        $this->mailService->sendOrderMail($Order);
        $this->cartService->clear();
        $this->session->set(OrderHelper::SESSION_ORDER_ID, $Order->getId());
        $this->entityManager->flush();
    }

    /**
     * @param Order $Order
     */
    protected function cancelShopping(Order $Order)
    {
        $this->purchaseFlow->rollback($Order, new PurchaseContext());
        $this->entityManager->flush();
    }
}
