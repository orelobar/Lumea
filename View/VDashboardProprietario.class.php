<?php

require_once __DIR__ . '/VBase.class.php';

class VDashboardProprietario extends VBase
{
    public function mostraDashboard(
        array $dati
    ) {
        $this->setDataIntoTemplate(
            'dashboard',
            $dati
        );

        $this->setTemplate(
            'dashboard_proprietario.tpl'
        );
    }
}
