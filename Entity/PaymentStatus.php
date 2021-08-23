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
use Eccube\Entity\Master\AbstractMasterEntity;

/**
 * PaymentStatus
 *
 * @ORM\Table(name="plg_smartpay_payment_status")
 * @ORM\Entity(repositoryClass="Plugin\Smartpay\Repository\PaymentStatusRepository")
 */
class PaymentStatus extends AbstractMasterEntity
{
    /**
     * 定数名は適宜変更してください.
     */

    /**
     * 未決済
     */
    const OUTSTANDING = 1;
    /**
     * 有効性チェック済
     */
    const ENABLED = 2;
    /**
     * 仮売上
     */
    const PROVISIONAL_SALES = 3;
    /**
     * 実売上
     */
    const ACTUAL_SALES = 4;
    /**
     * キャンセル
     */
    const CANCEL = 5;
}
