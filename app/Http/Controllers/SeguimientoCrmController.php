<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

// Services
use App\Services\CrmService;
use Illuminate\Support\Facades\Auth;

class SeguimientoCrmController
{
    protected CrmService $crmService;

    public function __construct(CrmService $crmService)
    {
        $this->crmService = $crmService;
    }

    public function index()
    {
        return view('crm.index');
    }

    /**
     * Display a listing of the resource.
     */
    public function data(Request $request)
    {
        try {
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);

            $filters = [
                'start' => $request->get('start') ?: null,
                'end' => $request->get('end') ?: null,
                'asesor' => $request->get('asesor') ?: null,
                'search' => $request->get('search') ?: null,
            ];

            $result = $this->crmService->listCrm($page, $perPage, $filters);

            return response()->json([
                'success' => true,
                'data' => collect($result['rows'])->map(
                    fn($r) => [
                        'fechaRegistro' => $r->FechaRegistro,
                        'oportunidad' => $r->Identificador,
                        'cliente' => $r->Cliente,
                        'asesor' => $r->Asesor,
                        'actividad' => $r->Actividad,
                        'fechaActividad' => $r->FechaActividad,
                        'observacion' => $r->Observacion,


                        // 🔽 DETALLE CLIENTE
                        'telefono'  => $r->Telefono,
                        'celular'   => $r->StrCelular,
                        'email'     => $r->Email,
                        'contacto'  => $r->Contacto,
                        'direccion' => $r->Direccion,
                    ],
                ),
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $result['total'],
                    'last_page' => (int) ceil($result['total'] / $perPage),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
