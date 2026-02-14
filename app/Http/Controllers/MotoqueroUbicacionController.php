<?php

namespace App\Http\Controllers;

use App\Models\MotoqueroUbicacion;
use Illuminate\Http\Request;

class MotoqueroUbicacionController extends Controller
{
    // 📍 Guardar ubicación desde el celular del motoquero
    public function store(Request $request)
    {
        $request->validate([
            'motoquero_id' => 'required|exists:motoqueros,id',
            'latitud'      => 'required|numeric',
            'longitud'     => 'required|numeric',
        ]);

        MotoqueroUbicacion::create([
            'motoquero_id' => $request->motoquero_id,
            'latitud'      => $request->latitud,
            'longitud'     => $request->longitud,
            'estado'       => 'activo',
            'registrado_en'=> now(),
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    // 🗺️ Obtener última ubicación de TODOS (admin)
    public function ultimas()
    {
        $ubicaciones = MotoqueroUbicacion::select('motoquero_id', 'latitud', 'longitud', 'registrado_en')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                  ->from('motoquero_ubicaciones')
                  ->groupBy('motoquero_id');
            })
            ->get();

        return response()->json($ubicaciones);
    }

    // 🧭 Trazo de un motoquero
    public function recorrido($motoqueroId)
    {
        return MotoqueroUbicacion::where('motoquero_id', $motoqueroId)
            ->orderBy('registrado_en')
            ->get(['latitud', 'longitud', 'registrado_en']);
    }
}
