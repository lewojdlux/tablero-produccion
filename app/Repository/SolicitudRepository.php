<?php

namespace App\Repository;

use App\Models\SolicitudMaterialModel;
use App\Models\DetalleMaterialProveedor;
use App\Models\DetalleSolicitudMaterialModel;

class SolicitudRepository
{

    protected $modelSolicitud = 'App\Models\SolicitudMaterialModel';
    protected $modelDetalleMaterialProveedor = 'App\Models\DetalleMaterialProveedor';
    protected $modelDetalleSolicitudMaterial = 'App\Models\DetalleSolicitudMaterialModel';


    /// Constructor
    public function __construct(SolicitudMaterialModel $modelSolicitud, DetalleMaterialProveedor $modelDetalleMaterialProveedor,
                                DetalleSolicitudMaterialModel $modelDetalleSolicitudMaterial)
    {
        $this->modelSolicitud = $modelSolicitud;
        $this->modelDetalleMaterialProveedor = $modelDetalleMaterialProveedor;
        $this->modelDetalleSolicitudMaterial = $modelDetalleSolicitudMaterial;
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

        return  $this->modelDetalleSolicitudMaterial::create($validated);
    }

}