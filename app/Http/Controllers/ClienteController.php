<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Promocion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ClienteController extends Controller
{
    /* ============================
     * LISTADO
     * ============================ */
    public function index()
    {
        $clientes = Cliente::all();
        return view('admin.clientes.index', compact('clientes'));
    }

    /* ============================
     * FORM CREAR
     * ============================ */
public function create()
{
    $promoVigente = Promocion::where('activa', true)
        ->where(function ($q) {
            $q->whereNull('fecha_inicio')
              ->orWhere('fecha_inicio', '<=', now());
        })
        ->where(function ($q) {
            $q->whereNull('fecha_fin')
              ->orWhere('fecha_fin', '>=', now());
        })
        ->orderBy('precio_promo', 'asc') // 👈 la más barata
        ->first();

    return view('admin.clientes.create', compact('promoVigente'));
}

    /* ============================
     * GUARDAR CLIENTE
     * ============================ */
    public function store(Request $request)
    {
        $request->validate([
            'nombre'              => 'required|string|max:100',
            'celular'             => 'required|string|max:15',
            'referencia_celular'  => 'nullable|string|max:15',
            'direccion'           => 'required|string|max:200',
            'ubicacion_gps'       => 'nullable|string|max:500',
            'latitud'             => 'nullable|numeric',
            'longitud'            => 'nullable|numeric',
        ]);

        /* ============================
         * UBICACIÓN
         * ============================ */
        $lat = $request->latitud;
        $lng = $request->longitud;

        if ($request->ubicacion_gps && (!$lat || !$lng)) {
            [$lat, $lng] = $this->extraerLatLng($request->ubicacion_gps);
        }

        /* ============================
         * PROMO VIGENTE (AUTO)
         * ============================ */
        $promo = Promocion::where('activa', true)
            ->where(function ($q) {
                $q->whereNull('fecha_inicio')
                  ->orWhere('fecha_inicio', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('fecha_fin')
                  ->orWhere('fecha_fin', '>=', now());
            })
            ->orderBy('precio_promo', 'asc') // 👈 la más barata
            ->first();

        /* ============================
         * CREAR CLIENTE
         * ============================ */
        $cliente = new Cliente();
        $cliente->nombre             = $request->nombre;
       
        $cliente->celular            = $request->celular;
        $cliente->referencia_celular = $request->referencia_celular;
        $cliente->direccion          = $request->direccion;
        $cliente->ubicacion_gps      = $request->ubicacion_gps;
        $cliente->latitud            = $lat;
        $cliente->longitud           = $lng;

        // 🔥 aplicar promo si existe
        if ($promo) {
            $cliente->promo_activa = true;
            $cliente->promo_desde  = $promo->fecha_inicio;
            $cliente->promo_hasta  = $promo->fecha_fin;
        } else {
            $cliente->promo_activa = false;
        }

        $cliente->save();

        return redirect()->route('admin.clientes.index')
            ->with('mensaje', 'Cliente creado correctamente')
            ->with('icono', 'success');
    }

    /* ============================
     * VER CLIENTE
     * ============================ */
    public function show($id)
    {
        $cliente = Cliente::findOrFail($id);
        return view('admin.clientes.show', compact('cliente'));
    }

    /* ============================
     * FORM EDITAR
     * ============================ */
    public function edit($id)
    {
        $cliente = Cliente::findOrFail($id);
        return view('admin.clientes.edit', compact('cliente'));
    }

    /* ============================
     * ACTUALIZAR
     * ============================ */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre'              => 'required|string|max:100',
            'celular'             => 'required|string|max:15',
            'referencia_celular'  => 'nullable|string|max:15',
            'direccion'           => 'required|string|max:200',
            'ubicacion_gps'       => 'nullable|string|max:500',
            'latitud'             => 'nullable|numeric',
            'longitud'            => 'nullable|numeric',
        ]);

        $cliente = Cliente::findOrFail($id);
        
        $cliente->promo_activa = $request->has('promo_activa') ? 1 : 0;

        $lat = $request->latitud;
        $lng = $request->longitud;

        if ($request->ubicacion_gps && (!$lat || !$lng)) {
            [$lat, $lng] = $this->extraerLatLng($request->ubicacion_gps);
        }

        $cliente->nombre             = $request->nombre;
        $cliente->celular            = $request->celular;
        $cliente->referencia_celular = $request->referencia_celular;
        $cliente->direccion          = $request->direccion;
        $cliente->ubicacion_gps      = $request->ubicacion_gps;
        $cliente->latitud            = $lat;
        $cliente->longitud           = $lng;
        $cliente->save();

        return redirect()->route('admin.clientes.index')
            ->with('mensaje', 'Cliente actualizado correctamente')
            ->with('icono', 'success');
    }

    /* ============================
     * ELIMINAR
     * ============================ */
    public function destroy($id)
    {
        Cliente::findOrFail($id)->delete();

        return redirect()->route('admin.clientes.index')
            ->with('mensaje', 'Cliente eliminado correctamente')
            ->with('icono', 'success');
    }

    /* ============================
     * EXTRAER COORDS (AJAX)
     * ============================ */
    public function obtenerCoords(Request $request)
    {
        $url = $request->input('url');

        try {
            $response = Http::withOptions(['allow_redirects' => true])->get($url);
            $finalUrl = (string) $response->effectiveUri();

            if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $finalUrl, $m)) {
                return response()->json(['lat' => $m[1], 'lng' => $m[2]]);
            }

            if (preg_match('/q=(-?\d+\.\d+),(-?\d+\.\d+)/', $finalUrl, $m)) {
                return response()->json(['lat' => $m[1], 'lng' => $m[2]]);
            }

            return response()->json(['error' => 'No se encontraron coordenadas'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error procesando el enlace'], 500);
        }
    }

    /* ============================
     * HELPER PRIVADO
     * ============================ */
    private function extraerLatLng($url)
    {
        $lat = $lng = null;

        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $m)) {
            $lat = $m[1];
            $lng = $m[2];
        } elseif (preg_match('/[?&]ll=(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $m)) {
            $lat = $m[1];
            $lng = $m[2];
        }

        return [$lat, $lng];
    }
}