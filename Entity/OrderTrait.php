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

namespace Plugin\Smartpay\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $smartpay_payment_checkout_id;

    /**
     * @var PaymentStatus
     * @ORM\ManyToOne(targetEntity="Plugin\Smartpay\Entity\PaymentStatus")
     * @ORM\JoinColumn(name="smartpay_payment_status_id", referencedColumnName="id")
     */
    private $SmartpayPaymentStatus;

    /**
     * @return string|null
     */
    public function getSmartpayPaymentCheckoutID(): ?string
    {
        return $this->smartpay_payment_checkout_id;
    }

    /**
     * @param string|null $smartpay_payment_checkout_id
     * @return $this
     */
    public function setSmartpayPaymentCheckoutID(?string $smartpay_payment_checkout_id): self
    {
        $this->smartpay_payment_checkout_id = $smartpay_payment_checkout_id;

        return $this;
    }

    /**
     * @return PaymentStatus|null
     */
    public function getSmartpayPaymentStatus(): ?PaymentStatus
    {
        return $this->SmartpayPaymentStatus;
    }

    /**
     * @param PaymentStatus|null $paymentStatus
     * @return $this
     */
    public function setSmartpayPaymentStatus(?PaymentStatus $paymentStatus): self
    {
        $this->SmartpayPaymentStatus = $paymentStatus;

        return $this;
    }
}
