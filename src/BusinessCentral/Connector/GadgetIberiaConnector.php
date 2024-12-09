<?php

namespace App\BusinessCentral\Connector;

use App\BusinessCentral\Connector\BusinessCentralConnector;

class GadgetIberiaConnector extends BusinessCentralConnector
{
    public function getCompanyIntegration()
    {
        return BusinessCentralConnector::GADGET_IBERIA;
    }


    public function getAccountNumberForExpedition()
    {
        return '7591001';
    }

    public function getDefaultWarehouse()
    {
        return BusinessCentralConnector::STOCK_LAROCA;
    }
}
