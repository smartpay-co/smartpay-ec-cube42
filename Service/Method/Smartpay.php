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

namespace Plugin\Smartpay\Service\Method;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\Smartpay\Entity\PaymentStatus;
use Plugin\Smartpay\Repository\PaymentStatusRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * クレジットカード(トークン決済)の決済処理を行う.
 */
class Smartpay implements PaymentMethodInterface
{
    /**
     * @var Order
     */
    protected $Order;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * @var PaymentStatusRepository
     */
    private $paymentStatusRepository;

    /**
     * @var PurchaseFlow
     */
    private $purchaseFlow;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var ParameterBag
     */
    private $parameterBag;

    private function isValidPublicApiKey($key)
    {
        return preg_match('/^pk_(test|live)_[0-9a-zA-Z]+$/', $key) === 1;
    }

    private function isValidSecretApiKey($key)
    {
        return preg_match('/^sk_(test|live)_[0-9a-zA-Z]+$/', $key) === 1;
    }

    /**
     * CreditCard constructor.
     *
     * @param OrderStatusRepository $orderStatusRepository
     * @param PaymentStatusRepository $paymentStatusRepository
     * @param PurchaseFlow $shoppingPurchaseFlow
     */
    public function __construct(
        OrderStatusRepository $orderStatusRepository,
        PaymentStatusRepository $paymentStatusRepository,
        PurchaseFlow $shoppingPurchaseFlow,
        EccubeConfig $eccubeConfig,
        ParameterBag $parameterBag
    ) {
        $this->orderStatusRepository = $orderStatusRepository;
        $this->paymentStatusRepository = $paymentStatusRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->eccubeConfig = $eccubeConfig;
        $this->parameterBag = $parameterBag;
    }

    /**
     * 注文確認画面遷移時に呼び出される.
     *
     * @return PaymentResult
     *
     * @throws \Eccube\Service\PurchaseFlow\PurchaseException
     */
    public function verify()
    {
        $result = new PaymentResult();
        $publicKey = getenv('SMARTPAY_PUBLIC_KEY');
        $secretKey = getenv('SMARTPAY_SECRET_KEY');

        if (empty($publicKey) || $this->isValidPublicApiKey($publicKey) === false) {
            $result->setSuccess(false);
            $result->setErrors([trans('smartpay.shopping.checkout.error.public_key')]);
            return $result;
        }

        if (empty($secretKey) || $this->isValidSecretApiKey($secretKey) === false) {
            $result->setSuccess(false);
            $result->setErrors([trans('smartpay.shopping.checkout.error.secret_key')]);
            return $result;
        }

        // 決済ステータスを有効性チェック済みへ変更
        $PaymentStatus = $this->paymentStatusRepository->find(PaymentStatus::ENABLED);
        $this->Order->setSmartpayPaymentStatus($PaymentStatus);

        $result->setSuccess(true);
        return $result;
    }

    /**
     * 注文時に呼び出される.
     *
     * 受注ステータス, 決済ステータスを更新する.
     * ここでは決済サーバとの通信は行わない.
     *
     * @return PaymentDispatcher|null
     */
    public function apply()
    {
        // 受注ステータスを決済処理中へ変更
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);
        $this->Order->setOrderStatus($OrderStatus);

        // purchaseFlow::prepareを呼び出し, 購入処理を進める.
        $this->purchaseFlow->prepare($this->Order, new PurchaseContext());

        $this->parameterBag->set('smartpay.Order', $this->Order);

        // 支払い処理へリダイレクト
        $dispatcher = new PaymentDispatcher();
        $dispatcher->setRoute('shopping_smartpay_payment');
        $dispatcher->setForward(true);

        return $dispatcher;
    }

    /**
     * 注文時に呼び出される.
     *
     * @return PaymentResult
     */
    public function checkout()
    {
        $result = new PaymentResult();
        $result->setSuccess(true);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setFormType(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrder(Order $Order)
    {
        $this->Order = $Order;
    }
}
