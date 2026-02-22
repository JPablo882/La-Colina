<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Promocion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

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

        $ultimoCliente = Cliente::orderBy('id', 'desc')->first();

    return view('admin.clientes.create', compact('promoVigente', 'ultimoCliente'));
}

    /* ============================
     * GUARDAR CLIENTE
     * ============================ */
    public function store(Request $request)
{
    /* ============================
     * LIMPIAR TELÉFONOS
     * ============================ */
    $celularLimpio = preg_replace('/\D/', '', $request->celular);
    $referenciaLimpio = $request->referencia_celular
        ? preg_replace('/\D/', '', $request->referencia_celular)
        : null;

    $request->merge([
        'celular' => $celularLimpio,
        'referencia_celular' => $referenciaLimpio
    ]);

    /* ============================
     * VALIDACIÓN SEGURA
     * ============================ */
    $request->validate([
        'nombre' => 'required|string|max:100|unique:clientes,nombre',

        'celular' => [
            'required',
            'digits_between:10,15',
            'unique:clientes,celular'
        ],

        'referencia_celular' => [
            'nullable',
            'digits_between:10,15'
        ],

        'direccion'     => 'required|string|max:200',
        'ubicacion_gps' => 'nullable|string|max:500',
        'latitud'       => 'nullable|numeric',
        'longitud'      => 'nullable|numeric',
    ], [
        'celular.digits_between' => 'El celular debe tener entre 10 y 15 dígitos e incluir código de país.',
        'celular.unique' => 'Este número ya está registrado.',
        'referencia_celular.digits_between' => 'La referencia debe incluir código de país (10–15 dígitos).'
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
     * PROMO VIGENTE
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
        ->orderBy('precio_promo', 'asc')
        ->first();

    /* ============================
     * CREAR CLIENTE
     * ============================ */
    $cliente = new Cliente();
    $cliente->nombre             = $request->nombre;
    $cliente->celular            = $celularLimpio;
    $cliente->referencia_celular = $referenciaLimpio;
    $cliente->direccion          = $request->direccion;
    $cliente->ubicacion_gps      = $request->ubicacion_gps;
    $cliente->latitud            = $lat;
    $cliente->longitud           = $lng;

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
    $cliente = Cliente::findOrFail($id);

    /* ============================
     * LIMPIAR TELÉFONOS
     * ============================ */
    $celularLimpio = preg_replace('/\D/', '', $request->celular);
    $referenciaLimpio = $request->referencia_celular
        ? preg_replace('/\D/', '', $request->referencia_celular)
        : null;

    $request->merge([
        'celular' => $celularLimpio,
        'referencia_celular' => $referenciaLimpio
    ]);

    /* ============================
     * VALIDACIÓN SEGURA
     * ============================ */
    $request->validate([
        'nombre' => [
            'required',
            'string',
            'max:100',
            Rule::unique('clientes')->ignore($cliente->id),
        ],

        'celular' => [
            'required',
            'digits_between:10,15',
            Rule::unique('clientes')->ignore($cliente->id),
        ],

        'referencia_celular' => [
            'nullable',
            'digits_between:10,15'
        ],

        'direccion'     => 'required|string|max:200',
        'ubicacion_gps' => 'nullable|string|max:500',
        'latitud'       => 'nullable|numeric',
        'longitud'      => 'nullable|numeric',
    ]);

    /* ============================
     * UBICACIÓN
     * ============================ */
    $lat = $request->latitud;
    $lng = $request->longitud;

    if ($request->ubicacion_gps && (!$lat || !$lng)) {
        [$lat, $lng] = $this->extraerLatLng($request->ubicacion_gps);
    }

    $cliente->promo_activa = $request->has('promo_activa') ? 1 : 0;

    $cliente->nombre             = $request->nombre;
    $cliente->celular            = $celularLimpio;
    $cliente->referencia_celular = $referenciaLimpio;
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

    public function validarCampo(Request $request)
    {
        $campo = $request->campo;
        $valor = $request->valor;

        $existe = \App\Models\Cliente::where($campo, $valor)->exists();

        return response()->json([
            'existe' => $existe
        ]);
    }

}