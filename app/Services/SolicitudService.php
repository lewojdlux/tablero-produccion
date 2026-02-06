<?php

namespace App\Services;

use App\Repository\SolicitudRepository;

class  SolicitudService {


    protected $solicitudRepository;


    public function __construct(SolicitudRepository $solicitudRepositorys) {

        $this->solicitudRepository = $solicitudRepositorys;
    }

    public function getSolicitudService() {

        return $this->solicitudRepository->getSolicitudesRepository();
    }


    public function getSolicitudIdService( $id ) {

        return $this->solicitudRepository->getSolicitudIdRepository( $id );
    }


    public function updateProveedorService( $id, $proveedor ) {

        return $this->solicitudRepository->updateProveedorRepository( $id, $proveedor );
    }

    public function storeMaterialService( $validated ) {

        return $this->solicitudRepository->storeMaterialRepository( $validated );
    }


}