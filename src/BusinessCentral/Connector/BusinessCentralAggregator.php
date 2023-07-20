<?php

namespace App\BusinessCentral\Connector;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Connector\IniaConnector;
use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\BusinessCentral\Connector\KpFranceConnector;
use App\BusinessCentral\Connector\KpUkConnector;
use Exception;

class BusinessCentralAggregator
{

    private $connectors;

    public function __construct(
        private KpFranceConnector $kpFranceConnector,
        private IniaConnector $iniaConnector,
        private KpUkConnector $kpUkConnector,
        private KitPersonalizacionSportConnector $kitPersonalizacionSportConnector
    ) {
        $this->connectors = [
            $kpFranceConnector,
            $iniaConnector,
            $kpUkConnector,
            $kitPersonalizacionSportConnector,
        ];
    }

    public function getAllConnectors()
    {
        return $this->connectors;
    }


    public function getBusinessCentralConnector(string $companyName): BusinessCentralConnector
    {
        foreach($this->connectors as $connector) {
            if($connector->getCompanyIntegration() == $companyName) {
                return $connector;
            }
        }
        throw new Exception("Company $companyName is not related to any connector");
    }
}
