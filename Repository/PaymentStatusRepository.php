<?php

/*
 * This file is part of Smartpay
 *
 * Copyright(c) Smartpay Solutions PTE. LTD. All Rights Reserved.
 *
 * https://smartpay.co
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Smartpay\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\Smartpay\Entity\PaymentStatus;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PaymentStatusRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PaymentStatus::class);
    }
}
