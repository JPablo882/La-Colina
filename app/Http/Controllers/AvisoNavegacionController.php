<?php

namespace App\Http\Controllers;

use App\Models\AvisoNavegacion;
use Illuminate\Http\Request;

class AvisoNavegacionController extends Controller
{


    public function crear(Request $request)
    {
        // Validación
        $request->validate([
            'pedido_id'    => 'required|integer',
            'cliente'      => 'required|string',
            'celular'      => 'nullable|string', // permitimos que venga vacío
        ]);

        // Crear el aviso
        $aviso = AvisoNavegacion::create([
            'pedido_id'    => $request->pedido_id,
            'cliente'      => $request->cliente,
            'celular'      => $request->celular ?: '0000000000', // valor por defecto si es null
            'motoquero_id' => $request->motoquero_id,
            'estado'       => 'pendiente',
        ]);

        return response()->json(['ok' => true, 'aviso' => $aviso]);
    }



    // 1. POLL para el administrador
    public function poll()
    {
        $aviso = AvisoNavegacion::with('pedido.cliente')
            ->where('estado', 'pendiente')
            ->latest()
            ->first();

        if (!$aviso) {
            return response()->json([]);
        }

        return response()->json($aviso);
    }


    // 2. ADMIN acepta y envía mensaje WhatsApp
    public function atender($id)
    {
        $aviso = AvisoNavegacion::findOrFail($id);
        $aviso->estado = 'atendido';
        $aviso->save();

        return response()->json(['ok' => true]);
    }


    // 3. ADMIN cierra sin atender
    public function cerrar($id)
    {
        $aviso = AvisoNavegacion::findOrFail($id);
        $aviso->estado = 'cerrado';
        $aviso->save();

        return response()->json(['ok' => true]);
    }
}