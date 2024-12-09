<?php

namespace App\BusinessCentral\Connector;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Connector\GadgetIberiaConnector;
use App\BusinessCentral\Connector\IniaConnector;
use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\BusinessCentral\Connector\KpFranceConnector;
use App\BusinessCentral\Connector\KpUkConnector;
use App\BusinessCentral\Connector\TurisportConnector;
use Exception;

class BusinessCentralAggregator
{
    public function __construct(
        private KpFranceConnector $kpFranceConnector,
        private GadgetIberiaConnector $gadgetIberiaConnector,
        private IniaConnector $iniaConnector,
        private KitPersonalizacionSportConnector $kitPersonalizacionSportConnector,
        private KpUkConnector $kpUkConnector,
        private TurisportConnector $turisportConnector
    ) {
    }


    public function getAllCompanies(): array
    {
        return  [
            BusinessCentralConnector::KP_FRANCE,
            BusinessCentralConnector::GADGET_IBERIA,
            BusinessCentralConnector::KIT_PERSONALIZACION_SPORT,
            BusinessCentralConnector::INIA,
            BusinessCentralConnector::KP_UK,
            BusinessCentralConnector::TURISPORT
        ];
    }


    public function getBusinessCentralConnector(string $companyName): BusinessCentralConnector
    {
        if ($companyName == BusinessCentralConnector::KP_FRANCE) {
            return $this->kpFranceConnector;
        } elseif ($companyName == BusinessCentralConnector::GADGET_IBERIA) {
            return $this->gadgetIberiaConnector;
        } elseif ($companyName == BusinessCentralConnector::KIT_PERSONALIZACION_SPORT) {
            return $this->kitPersonalizacionSportConnector;
        } elseif ($companyName == BusinessCentralConnector::INIA) {
            return $this->iniaConnector;
        } elseif ($companyName == BusinessCentralConnector::KP_UK) {
            return $this->kpUkConnector;
        } elseif ($companyName == BusinessCentralConnector::TURISPORT) {
            return $this->turisportConnector;
        }


        throw new Exception("Company $companyName is not related to any connector");
    }




    public function getInitiales($companyName)
    {
        if ($companyName == BusinessCentralConnector::KP_FRANCE) {
            return 'kpf';
        } elseif ($companyName == BusinessCentralConnector::GADGET_IBERIA) {
            return 'gi';
        } elseif ($companyName == BusinessCentralConnector::KIT_PERSONALIZACION_SPORT) {
            return 'kps';
        } elseif ($companyName == BusinessCentralConnector::INIA) {
            return 'inia';
        } elseif ($companyName == BusinessCentralConnector::KP_UK) {
            return 'kpuk';
        } elseif ($companyName == BusinessCentralConnector::TURISPORT) {
            return 'turi';
        }
        throw new Exception("Company $companyName is not related to any connector");
    }
}
