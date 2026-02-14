<?php

namespace App\Http\Controllers;

use App\Models\Llamada;
use Illuminate\Http\Request;

class LlamadaController extends Controller
{
    // Motoquero envía solicitud
    public function solicitar(Request $request)
    {
        Llamada::create([
            'cliente_id'      => $request->cliente_id,
            'nombre_cliente'  => $request->nombre_cliente,
            'celular_cliente' => $request->celular_cliente,
            'motoquero_id'    => auth()->user()->motoquero->id,
            'nombre_motoquero'=> $request->nombre_motoquero,
        ]);

        return response()->json(['ok' => true]);
    }

    // Admin consulta solicitudes pendientes
    public function poll()
    {
        return Llamada::where('estado', 'pendiente')->orderBy('id', 'desc')->get();
    }

    // Admin acepta la solicitud
    public function atender(Llamada $llamada)
    {
        $llamada->estado = 'atendida';
        $llamada->save();

        return response()->json(['ok' => true]);
    }

    public function cerrar(Llamada $llamada)
    {

        $llamada->estado = 'cerrada';
        $llamada->save();

        return response()->json(['ok' => true]);
    }

    public function checkMotoquero(Request $request)
    {
    $motoqueroId = auth()->user()->motoquero->id;

    // Buscar una llamada del motoquero cuyo estado sea atendida,
    // y que aún no haya sido notificada al motoquero.
    $llamada = Llamada::where('motoquero_id', $motoqueroId)
        ->where('estado', 'atendida')
        ->whereNull('notificado_motoquero')  // NUEVA COLUMNA
        ->first();

    if (!$llamada) {
        return response()->json(['notificacion' => false]);
    }

    // Marcar como enviada la notificación
    $llamada->notificado_motoquero = now();
    $llamada->save();

    return response()->json([
        'notificacion' => true,
        'cliente' => $llamada->nombre_cliente,
        'celular' => $llamada->celular_cliente,

    ]);
    }

}