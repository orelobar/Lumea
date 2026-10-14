<?php

require_once __DIR__ . '/VBase.class.php';

class VCouponCliente extends VBase
{
    public function mostraCoupon(array $dati): void
    {
        $this->setDataIntoTemplate('utente', $dati['utente']);
        $this->setDataIntoTemplate('fedelta', $dati['fedelta']);
        $this->setDataIntoTemplate('coupon', $dati['coupon']);
        $this->setDataIntoTemplate(
            'couponDisponibili',
            $dati['coupon_disponibili']
        );
        $this->setDataIntoTemplate('creditoCoupon', $dati['credito_coupon']);
        $this->setTemplate('coupon_cliente.tpl');
    }
}
