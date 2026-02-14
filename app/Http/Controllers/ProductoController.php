<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index()
    {
       $productos = Producto::all();
   //     return view('admin', compact('productos'));
       return view('admin.productos.index', compact('productos'));

    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
        ]);

        Producto::create($request->only('nombre', 'precio'));

        return redirect()->back()->with('success', 'Producto creado correctamente.');
    }


        public function create()
    {
        return view('admin.productos.create');
         
    }


    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
        ]);

        $producto->update($request->only('nombre', 'precio'));

        return redirect()->back()->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto)
    {
        $producto->delete();
        return redirect()->back()->with('success', 'Producto eliminado correctamente.');
    }
}