<?php

namespace App\Repository;

use App\Models\SolicitudMaterialModel;
use App\Models\DetalleMaterialProveedor;

class SolicitudRepository
{

    protected $modelSolicitud = 'App\Models\SolicitudMaterialModel';
    protected $modelDetalleMaterialProveedor = 'App\Models\DetalleMaterialProveedor';


    /// Constructor
    public function __construct(SolicitudMaterialModel $modelSolicitud, DetalleMaterialProveedor $modelDetalleMaterialProveedor)
    {
        $this->modelSolicitud = $modelSolicitud;
        $this->modelDetalleMaterialProveedor = $modelDetalleMaterialProveedor;
    }


    // Traer todas las solicitudes
    public function getSolicitudesRepository()
    {
        return  $this->modelSolicitud::with(['pedidoMaterial', 'instalador', 'proveedor'])
            ->orderBy('id_solicitud_material', 'desc')
            ->paginate('10');
    }

    public function getSolicitudIdRepository($id){

        return  $this->modelSolicitud::with(['pedidoMaterial', 'instalador', 'proveedor'])
            ->where('id_solicitud_material', $id)
            ->first();
    }


    public function updateProveedorRepository( $id, $proveedor ) {

        return  $this->modelSolicitud::where('id_solicitud_material', $id)
            ->update(['proveedor_id' => $proveedor]);
    }


    public function storeMaterialRepository( $validated ) {

        return  $this->modelDetalleMaterialProveedor::create($validated);
    }

}
