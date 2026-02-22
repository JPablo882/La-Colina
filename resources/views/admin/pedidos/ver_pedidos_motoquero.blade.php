@extends('adminlte::page')

@section('content_header')
    <h1><b>Listado de pedidos</b></h1>
    <hr>
@stop

@section('content')


{{-- ===== PEDIDOS NUEVOS ===== --}}
<div id="contenedor-pedidos-nuevos">

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Pedidos nuevos</h3>
        </div>

        <div class="card-body">
            @php
                $pedidosNuevos = $pedidos->where('estado', 'Asignado')
                                          ->where('motoquero_id', $motoquero->id)
                                          ->sortBy('orden')
                                          ->values();
            @endphp

            @forelse($pedidosNuevos as $pedido)
                @php $numeroPedido = $pedido->orden; @endphp

                <div class="pedido-card motoquero-card"
                     data-id="{{ $pedido->id }}"
                     data-cliente="{{ $pedido->cliente->id }}">

                    <div class="pedido-header">
                        <h5><b>{{ $pedido->cliente->nombre }}</b></h5>
                        <div style="font-weight:bold; font-size:1.2em; color:#007bff;">
                            #{{ $numeroPedido }}
                        </div>
                    </div>

                    <p><b>Ubicación GPS:</b>
                        @if($pedido->cliente->ubicacion_gps ?? $pedido->gps)
                            <a href="{{ $pedido->cliente->ubicacion_gps ?? $pedido->gps }}" target="_blank">
                                Ver enlace
                            </a>
                        @else
                            <span class="text-muted">No registrado</span>
                        @endif
                    </p>

                    <p><b>Descripción:</b>
                        {{ $pedido->cliente->direccion ?? $pedido->direccion_entrega }}
                    </p>

                    <p><b>Observaciones:</b> {{ $pedido->observaciones }}</p>

                    {{-- 🔵 ÚLTIMA COMPRA --}}
                    @php
                        $ultimaCompra = $ultimasCompras[$pedido->cliente->id] ?? null;
                    @endphp

                    @if($ultimaCompra && $ultimaCompra->detalles->count() > 0)
                        <div class="mt-2 p-2"
                             style="background:#eef7ff; border-radius:6px;">
                            <b>Última compra:</b>
                            <ul style="margin-left:12px; margin-top:5px; margin-bottom:0;">
                                @foreach($ultimaCompra->detalles as $detalle)
                                    <li style="font-size:0.95rem;">
                                        {{ $detalle->producto }}:
                                        <strong>{{ $detalle->cantidad }}</strong>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="mb-0">
                            <b>Última compra:</b>
                            <span class="text-muted">Sin historial</span>
                        </p>
                    @endif

                    <div class="pedido-acciones">
                        <form action="{{ url('/admin/pedidos/motoquero/'.$motoquero->id.'/tomar_pedido') }}"
                              method="post"
                              class="form-iniciar-navegacion"
                              data-pedido-id="{{ $pedido->id }}">
                            @csrf
                            <input type="hidden" name="pedido_id" value="{{ $pedido->id }}">
                            <input type="hidden" name="cliente" value="{{ $pedido->cliente->nombre }}">
                            <input type="hidden" name="celular" value="{{ $pedido->cliente->celular }}">
                            <input type="hidden" name="motoquero_id" value="{{ $pedido->motoquero_id }}">


                            <button type="submit"
                                    class="btn btn-info btn-sm btn-navegar"
                                    data-lat="{{ $pedido->cliente->latitud ?? '-'}}"
                                    data-lng="{{ $pedido->cliente->longitud ?? '-'}}">
                                <i class="fas fa-motorcycle"></i> Iniciar Navegación
                            </button>

                        </form>

                        <form action="{{ url('/admin/pedidos/motoquero/'.$motoquero->id.'/rechazar_pedido') }}"
                              method="post">
                            @csrf
                            <input type="hidden" name="pedido_id" value="{{ $pedido->id }}">
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-times"></i> Rechazar pedido
                            </button>
                        </form>
                    </div>

                </div>
            @empty
                <p class="text-center">No hay pedidos nuevos.</p>
            @endforelse
        </div>
    </div>

</div>

{{-- ===== PEDIDOS EN CAMINO ===== --}}
<div class="card card-warning mt-4">
    <div class="card-header">
        <h3 class="card-title">Pedidos en camino</h3>
    </div>
    <div class="card-body">
        @php
            $pedidosCaminoOrdenados = $pedidos_en_camino->sortBy('orden');
        @endphp

        @forelse($pedidosCaminoOrdenados as $pedido)
            <div class="pedido-card" data-id="{{ $pedido->id }}" data-cliente="{{ $pedido->cliente->id }}">
                <div class="pedido-header">
                    <h5><b>{{ $pedido->cliente->nombre }}</b></h5>
                    <span class="badge badge-warning">{{ $pedido->estado }}</span>
                </div>

                <p><b>Ubicación GPS:</b>
                    @if($pedido->cliente->ubicacion_gps ?? $pedido->gps)
                        <a href="{{ $pedido->cliente->ubicacion_gps ?? $pedido->gps }}" target="_blank">Ver enlace</a>
                    @else
                        <span class="text-muted">No registrado</span>
                    @endif
                </p>

                <p><b>Descripción:</b> {{ $pedido->cliente->direccion ?? $pedido->direccion_entrega }}</p>
                <p><b>Observaciones:</b> {{ $pedido->observaciones }}</p>


                {{-- 🟢 PRECIO REFERENCIA (dinámico por cliente) --}}
                <div class="mt-2 p-2" style="background:#fff8e1; border-radius:6px;"
                    data-precio-box
                    data-cliente="{{ $pedido->cliente->id }}">

                    <b>Precio referencia:</b>
                    <div style="font-size:0.95rem; margin-top:4px;">
                        <div>
                            Agua normal (ID 1):
                            <span class="precio-id-1 text-muted">Cargando...</span>
                        </div>
                        <div>
                            Agua alcalina (ID 2):
                            <span class="precio-id-2 text-muted">Cargando...</span>
                        </div>
                    </div>
                </div>
                {{-- 🟢 FIN PRECIO REFERENCIA --}}


                {{-- 🔵 ÚLTIMA COMPRA (solo producto + cantidad) usando $ultimasCompras precargado --}}
                @php
                    $ultimaCompra = $ultimasCompras[$pedido->cliente->id] ?? null;
                @endphp

                @if($ultimaCompra && $ultimaCompra->detalles->count() > 0)
                    <div class="mt-2 p-2" style="background: #eef7ff; border-radius: 6px;">
                        <b>Última compra:</b>
                        <ul style="margin-left: 12px; margin-top: 5px; margin-bottom:0;">
                            @foreach($ultimaCompra->detalles as $detalle)
                                <li style="font-size:0.95rem; margin-bottom:2px;">
                                    {{ $detalle->producto }}:
                                    <strong>{{ $detalle->cantidad }}</strong>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <p class="mb-0"><b>Última compra:</b> <span class="text-muted">Sin historial</span></p>
                @endif
                {{-- 🔵 FIN BLOQUE ÚLTIMA COMPRA --}}


                @php
                $msgLlegué = "Llegué a la ubicación: " . $pedido->cliente->nombre . ",   ". "+"  . $pedido->cliente->celular . ",  ". "https://wa.me/" . $pedido->cliente->celular . "?text=Hola,%20el%20distribuidor%20LLEGÓ%20a%20su%20ubicación.%20Por%20favor%20acérquese%20para%20recibir%20el%20pedido." ;                           
                $msgLlegué = urlencode($msgLlegué);
                @endphp

                
                    <a href="https://wa.me/59163524474?text={{ $msgLlegué }}" target="_blank" class="btn btn-success btn-sm">
                        <i class="fab fa-whatsapp"></i> Chat Central
                    </a>
                
                    <button class="btn btn-warning btn-sm"
                        onclick="solicitarLlamada({{ $pedido->cliente->id }}, '{{ $pedido->cliente->nombre }}', '{{ $pedido->cliente->celular }}', '{{ auth()->user()->name }}', '{{ $pedido->motoquero_id }}')">
                        <i class="fas fa-phone"></i> Hacer Llamar
                    </button>

                <div class="pedido-acciones">
                    <form action="{{ url('/admin/pedidos/motoquero/'.$motoquero->id.'/rechazar_pedido') }}" method="post">
                        @csrf
                        <input type="hidden" name="pedido_id" value="{{ $pedido->id }}">
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fas fa-times"></i> Rechazar
                        </button>
                    </form>

                    <button class="btn btn-success btn-sm" data-bs-toggle="modal"
                        data-bs-target="#modalVenta" data-pedido="{{ $pedido->id }}" data-cliente="{{ $pedido->cliente->id }}">
                        <i class="fas fa-check"></i> Finalizar entrega
                    </button>
                </div>
            </div>
        @empty
            <p class="text-center">No hay pedidos en camino.</p>
        @endforelse
    </div>
</div>

{{-- ===== PEDIDOS ENTREGADOS ===== --}}
<div class="card card-success mt-4">
    <div class="card-header">
        <h3 class="card-title">Pedidos entregados</h3>
    </div>
    <div class="card-body">

        <div class="mb-3">
            <form method="GET" class="d-flex align-items-center gap-2">
                <label for="fecha">Filtrar por fecha:</label>
                <input type="date" name="fecha" id="fecha" class="form-control" value="{{ $userFiltered ? $fecha : '' }}">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
            </form>
            @if(!$userFiltered)
                <small class="text-muted">
                    Mostrando pedidos entregados de hoy ({{ \Carbon\Carbon::today()->format('d/m/Y') }})
                </small>
            @endif
        </div>

        <div class="scroll-entregados">
            @forelse($pedidos_entregados as $pedido)
                <div class="pedido-card" data-cliente="{{ $pedido->cliente->id }}">
                    <div class="pedido-header">
                        <h5><b>{{ $pedido->cliente->nombre }}</b></h5>
                        <span class="badge badge-success">{{ $pedido->estado }}</span>
                    </div>

                    <p><b>Ubicación GPS:</b>
                        @if($pedido->cliente->ubicacion_gps ?? $pedido->gps)
                            <a href="{{ $pedido->cliente->ubicacion_gps ?? $pedido->gps }}" target="_blank">Ver enlace</a>
                        @else
                            <span class="text-muted">No registrado</span>
                        @endif
                    </p>

                    <p><b>Descripción:</b> {{ $pedido->cliente->direccion ?? $pedido->direccion_entrega }}</p>
                    <p><b>Observaciones:</b> {{ $pedido->observaciones }}</p>

                    </p>

                    @if($pedido->detalles->count() > 0)
                        <table class="table table-sm table-bordered mt-2 tabla-entregados">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unitario (Bs)</th>
                                    <th>Total (Bs)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pedido->detalles as $detalle)
                                    
                                    <tr>
                                        <td>{{ $detalle->producto }}</td>
                                        <td>{{ $detalle->cantidad }}</td>
                                        <td>{{ number_format($detalle->precio_unitario, 2) }}</td>
                                        <td>{{ number_format($detalle->precio_unitario * $detalle->cantidad, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p class="text-end"><b>Total pedido:</b> Bs {{ number_format($pedido->total_precio, 2) }}</p>
                        <p><b>Método de pago:</b> {{ $pedido->metodo_pago ?? 'No definido' }}</p>
                    @endif
                </div>
            @empty
                <p class="text-center">No hay pedidos entregados para esta fecha.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- MODAL FINALIZAR ENTREGA --}}
<div class="modal fade" id="modalVenta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ url('/admin/pedidos/motoquero/'.$motoquero->id.'/finalizar_pedido') }}">
                @csrf
                <input type="hidden" name="pedido_id" id="modal_pedido_id">
                <input type="hidden" id="modal_cliente_id" value="">

                <div class="modal-header">
                    <h5 class="modal-title">Finalizar entrega</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    {{-- Tabla de productos --}}
                    <table class="table table-sm text-center tabla-finalizar" id="tablaProductos">
                        <thead class="thead-light">
                            <tr>
                                <th>Producto</th>
                                <th>Precio ref.</th>
                                <th>Cantidad</th>
                                <th>Total (Bs)</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                       
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total general (Bs):</th>
                                <th><input type="number" id="totalGeneral" class="form-control text-center" value="0.00" readonly></th>
                                <th></th>
                            </tr>
                        </tfoot>

                    </table>

                    <div class="text-end mb-3">
                        <button type="button" class="btn btn-info btn-sm" id="agregarFila">
                            <i class="fas fa-plus"></i> Agregar producto
                        </button>
                    </div>

                    {{-- Método de pago --}}
                    <div class="mb-3">
                        <label><b>Método de pago:</b></label>
                        <select name="metodo_pago" id="metodo_pago" class="form-control" required>
                            <option value="">Seleccione...</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="QR">QR</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-success" type="submit">Finalizar</button>
                </div>
            </form>
        </div>
    </div>

</div>


{{-- MODAL PEDIDO DE EMERGENCIA, CONTROL --}}
<div id="modalEmergencia" class="modal">
  <div class="modal-content">
    <h3>🚨 PEDIDO DE EMERGENCIA</h3>
    <p>Cliente: <strong id="emgCliente"></strong></p>

    <a id="btnNavegarEmergencia"
       class="btn btn-danger btn-lg">
       INICIAR NAVEGACIÓN DE EMERGENCIA
    </a>
  </div>
</div>



<audio id="sound-ya-sale" src="{{ asset('sounds/ya_sale.mp3') }}"></audio>
<audio id="sound-no-contesta" src="{{ asset('sounds/no_contesta.mp3') }}"></audio>


@stop

@section('css')
<style>
.pedido-card { background: #fff; border-radius: 10px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.pedido-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.pedido-acciones { display: flex; justify-content: flex-start; gap: 10px; margin-top: 10px; }
.scroll-entregados { max-height: 500px; overflow-y: auto; padding-right: 10px; scrollbar-width: thin; scrollbar-color: #28a745 #e9ecef; }
.scroll-entregados::-webkit-scrollbar { width: 8px; }
.scroll-entregados::-webkit-scrollbar-thumb { background-color: #28a745; border-radius: 10px; }
.scroll-entregados::-webkit-scrollbar-track { background: #e9ecef; }
</style>

<style>
.tabla-finalizar { font-size: 13px; table-layout: fixed; width: 100%;}
.tabla-finalizar th,
.tabla-finalizar td {padding: 5px 6px; vertical-align: middle;}
/* Producto */
.tabla-finalizar th:nth-child(1),
.tabla-finalizar td:nth-child(1) {width: 28%;}
/* Precio ref */
.tabla-finalizar th:nth-child(2),
.tabla-finalizar td:nth-child(2) {width: 14%;}
/* Cantidad (más angosta) */
.tabla-finalizar th:nth-child(3),
.tabla-finalizar td:nth-child(3) {width: 12%;}
/* Total (más ancho) */
.tabla-finalizar th:nth-child(4),
.tabla-finalizar td:nth-child(4) {width: 18%;white-space: nowrap;}
/* Acción */
.tabla-finalizar th:nth-child(5),
.tabla-finalizar td:nth-child(5) {width: 12%;}
/* Inputs más pequeños */
.tabla-finalizar input,
.tabla-finalizar select { height: 30px; font-size: 13px; padding: 3px 6px;}
/* Evita que el modal se desborde en móvil */
#modalVenta .modal-body {overflow-x: auto;}
</style>
<style>
/* Contenedor scroll horizontal solo si es necesario */
.scroll-entregados { overflow-x: auto;}
/* Tabla más compacta */
.tabla-entregados { font-size: 13px; table-layout: fixed; width: 100%;}
.tabla-entregados th,
.tabla-entregados td { padding: 4px 6px; vertical-align: middle;}
/* Producto */
.tabla-entregados th:nth-child(1),
.tabla-entregados td:nth-child(1) { width: 35%;}
/* Cantidad (MUCHO más angosta) */
.tabla-entregados th:nth-child(2),
.tabla-entregados td:nth-child(2) { width: 10%; text-align: center;}
/* Precio Unitario */
.tabla-entregados th:nth-child(3),
.tabla-entregados td:nth-child(3) { width: 20%; text-align: right; white-space: nowrap;}
/* Total */
.tabla-entregados th:nth-child(4),
.tabla-entregados td:nth-child(4) {width: 20%;text-align: right;white-space: nowrap;}
/* En móvil reducir aún más */
@media (max-width: 576px) {
.tabla-entregados {font-size: 12px;}
.tabla-entregados th,
.tabla-entregados td {padding: 3px 4px;}}
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabla = document.querySelector('#tablaProductos tbody');
    const totalGeneralInput = document.getElementById('totalGeneral');


    // Función para actualizar total de una fila
    function actualizarFilaTotal(fila) {
        const precio = parseFloat(fila.querySelector('.precio').value) || 0;
        const cantidad = parseFloat(fila.querySelector('.cantidad').value) || 0;
        fila.querySelector('.total').value = (precio * cantidad).toFixed(2);
    }

    // Función para actualizar total general
    function actualizarTotalGeneral() {
        let total = 0;
        document.querySelectorAll('.total').forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        totalGeneralInput.value = total.toFixed(2);
    }


    // Actualizar total al cambiar cantidad
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('cantidad')) {
            const fila = e.target.closest('tr');
            actualizarFilaTotal(fila);
            actualizarTotalGeneral();
        }
    });


    document.addEventListener('click', function(e) {
        if (e.target.closest('.eliminar-fila')) {
            e.target.closest('tr').remove();
            actualizarTotalGeneral();
        }
    });


    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('producto-select')) {

            const fila = e.target.closest('tr');
            const productoId = e.target.value;
            const clienteId = document.getElementById('modal_cliente_id').value;

            if (!productoId) return;

            fetch(`/admin/pedidos/precio-cliente/${clienteId}/${productoId}`)
                .then(r => r.json())
                .then(data => {
                    // VISUAL
                    fila.querySelector('.precio-ref').innerText = data.precio + ' Bs';

                    // OCULTO
                    fila.querySelector('.precio').value = data.precio;

                    // recalcular
                    actualizarFilaTotal(fila);
                    actualizarTotalGeneral();
                });
        }
    });



    // Función para crear fila nueva
    function crearFila() {
        const fila = document.createElement('tr');

        let options = '<option value="">Seleccione...</option>';
        @foreach($productos as $prod)
            options += `<option value="{{ $prod->id }}">{{ $prod->nombre }}</option>`;
        @endforeach

        fila.innerHTML = `
            <td>
                <select name="producto_id[]" class="form-control producto-select" required>
                    ${options}
                </select>
            </td>

            <td class="text-center">
                <span class="precio-ref text-muted">—</span>
                <input type="hidden" class="precio" value="0">
            </td>

            <td>
                <input type="number" min="1" name="cantidad[]" 
                    class="form-control cantidad" value="1" required>
            </td>

            <td>
                <input type="number" step="0.01" 
                    class="form-control total" readonly value="0.00">
            </td>

            <td>
                <button type="button" class="btn btn-danger btn-sm eliminar-fila">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;

        tabla.appendChild(fila);
    }

    // Agregar fila
    document.getElementById('agregarFila').addEventListener('click', function() {
        const cliente_id = document.getElementById('modal_cliente_id').value;
        crearFila(cliente_id);
    });

    // Abrir modal
    const modal = document.getElementById('modalVenta');
    modal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const pedidoId = button.getAttribute('data-pedido');
        const clienteId = button.getAttribute('data-cliente'); // Pasar cliente_id al botón
        document.getElementById('modal_pedido_id').value = pedidoId;
        document.getElementById('modal_cliente_id').value = clienteId;

        // Reset tabla
        tabla.querySelectorAll('tr').forEach(tr => tr.remove());
        crearFila(clienteId);

        // Reset método de pago
        document.getElementById('metodo_pago').value = "";
        totalGeneralInput.value = "0.00";
    });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Indica si el usuario filtró manualmente (desde el servidor)
    const userFiltered = @json($userFiltered ?? false);

    // Elemento fecha
    const fechaInput = document.getElementById('fecha');
    if (!fechaInput) return;

    // Fecha hoy en formato YYYY-MM-DD
    const hoy = new Date().toISOString().slice(0,10);

    // --- Comportamiento 1: si la página fue abierta otro día (última visita diferente)
    // y el usuario NO filtró manualmente, recargamos con la fecha de hoy para que
    // se muestren los entregados de hoy.
    try {
        const storageKey = 'pedidos_entregados_last_visit_date';
        const lastVisit = localStorage.getItem(storageKey);

        if (!userFiltered) {
            // Si no hay lastVisit o es distinto a hoy y el input no es hoy -> recarga
            if ((!lastVisit || lastVisit !== hoy) && fechaInput.value !== hoy) {
                // Guardar hoy para evitar bucles y recargar con ?fecha=hoy
                localStorage.setItem(storageKey, hoy);

                // Construimos la URL actual manteniendo otros query params salvo 'fecha'
                const url = new URL(window.location.href);
                url.searchParams.set('fecha', hoy);
                window.location.href = url.toString();
                return;
            }

            // Guardamos la visita actual
            localStorage.setItem(storageKey, hoy);
        }
    } catch (e) {
        // si storage falla, no pasa nada
        console.warn('localStorage no disponible', e);
    }

    // --- Comportamiento 2: si la app queda abierta y llega la medianoche,
    // recargar automáticamente (solo si el usuario no filtró manualmente)
    if (!userFiltered) {
        const now = new Date();
        const manana = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);
        const msUntilMidnight = manana - now;

        // Programamos recarga a la medianoche para actualizar la fecha y la lista
        setTimeout(function() {
            const url = new URL(window.location.href);
            const nuevaHoy = new Date().toISOString().slice(0,10);
            url.searchParams.set('fecha', nuevaHoy);
            // actualiza localStorage antes de recargar para no crear bucle
            try { localStorage.setItem('pedidos_entregados_last_visit_date', nuevaHoy); } catch (_) {}
            window.location.href = url.toString();
        }, msUntilMidnight + 1000); // +1s para asegurarnos que ya es el nuevo día
    }

});
</script>


<script>
/**
 * ENVÍA AVISO AL ADMIN CUANDO SE INICIA NAVEGACIÓN
 */
function enviarAvisoDeNavegacion(pedidoId, cliente, celular, motoqueroId) {
    fetch("/admin/avisos-navegacion/crear", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            pedido_id: pedidoId,
            cliente: cliente,
            celular: celular,
            motoquero_id: motoqueroId
        })
    }).catch(err => console.error("Error enviando aviso navegación:", err));
}


/**
 * INTERCEPTA SUBMIT DE "INICIAR NAVEGACIÓN"
 * (funciona aunque el HTML se recargue)
 */
document.addEventListener('submit', function (e) {

    const form = e.target;
    const btn = form.querySelector('.btn-navegar');

    // Si no es el formulario de navegación, salir
    if (!btn) return;

    // ========================
    // DATOS
    // ========================
    const pedidoId   = form.querySelector('input[name="pedido_id"]')?.value;
    const cliente    = form.querySelector('input[name="cliente"]')?.value;
    const celular    = form.querySelector('input[name="celular"]')?.value;
    const motoqueroId= form.querySelector('input[name="motoquero_id"]')?.value;

    const lat = btn.dataset.lat || null;
    const lng = btn.dataset.lng || null;

    // ========================
    // AVISO AL ADMIN
    // ========================
    enviarAvisoDeNavegacion(pedidoId, cliente, celular, motoqueroId);

    // ========================
    // GUARDAR COORDENADAS
    // ========================
    if (lat && lng) {
        sessionStorage.setItem('nav_lat', lat);
        sessionStorage.setItem('nav_lng', lng);
    }
});


/**
 * DETECTAR PLATAFORMA
 */
function getPlatform() {
    const ua = navigator.userAgent || navigator.vendor || window.opera;

    if (/android/i.test(ua)) return "android";
    if (/iPad|iPhone|iPod/.test(ua) && !window.MSStream) return "ios";
    return "desktop";
}


/**
 * ABRIR GOOGLE MAPS DESPUÉS DEL SUBMIT
 */
document.addEventListener('DOMContentLoaded', function () {

    const lat = sessionStorage.getItem('nav_lat');
    const lng = sessionStorage.getItem('nav_lng');

    if (!lat || !lng) return;

    // Limpiar para no repetir
    sessionStorage.removeItem('nav_lat');
    sessionStorage.removeItem('nav_lng');

    let url = '';
    const platform = getPlatform();

    if (platform === 'android') {
        url = `intent://maps.google.com/maps?daddr=${lat},${lng}&travelmode=driving#Intent;scheme=https;package=com.google.android.apps.maps;end`;
    } else if (platform === 'ios') {
        url = `maps://?daddr=${lat},${lng}&dirflg=d`;
    } else {
        url = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`;
    }

    window.open(url, "_blank");
});
</script>

<script>
// REEMPLAZAR tu función solicitarLlamada por esta versión con SweetAlert2
function solicitarLlamada(clienteId, nombre, celular, motoquero, motoqueroID) {

    fetch("{{ route('solicitar.llamada') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({
            cliente_id: clienteId,
            nombre_cliente: nombre,
            celular_cliente: celular,
            nombre_motoquero: motoquero,
            motoquero_id: motoqueroID

        })
    })
    .then(r => r.json())
    .then(() => {
        // SweetAlert2 Toast
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Solicitud enviada al Administrador ✔',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            background: '#28a745', // color verde como éxito
            color: '#fff',
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
    })
    .catch(err => {
        console.error(err);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo enviar la solicitud'
        });
    });
}
</script>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Cargar el audio
const audio = new Audio("/sounds/alertallegada.mp3");
audio.preload = "auto";

// Vibración
function vibrar() {
    if (navigator.vibrate) {
        navigator.vibrate([300, 200, 300]);
    }
}

// Revisar cada 5 segundos si hay llamada atendida
setInterval(() => {
    fetch("{{ route('llamadas.checkMotoquero') }}", {
        headers: {
            'Accept': 'application/json'
        },
        redirect: 'manual'
    })
    .then(res => {
        // ❌ jamás seguir redirects
        if (res.status === 302) return null;
        if (res.status === 204) return null;
        if (res.status === 401) return null;

        return res.json();
    })
    .then(data => {
        if (!data || !data.notificacion) return;

        // 🔊 Sonido
        audio.currentTime = 0;
        audio.play().catch(() => {});

        // 📳 Vibración
        vibrar();

        // 🔔 SweetAlert
        Swal.fire({
            title: "📞 Cliente Avisado",
            html: `
                <p>El cliente <strong>${data.cliente}</strong> ya fue avisado.</p>
                <p>Celular: <strong>${data.celular}</strong></p>
            `,
            icon: "success",
            confirmButtonText: "Entendido",
            confirmButtonColor: "#198754",
            backdrop: true
        });
    })
    .catch(() => {});
}, 5000);
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    setInterval(() => {

        // Si la pestaña no está activa, no recargar
        if (document.hidden) return;

        fetch(window.location.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.text())
        .then(html => {

            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const nuevo = doc.querySelector('#contenedor-pedidos-nuevos');
            const actual = document.querySelector('#contenedor-pedidos-nuevos');

            if (nuevo && actual) {
                actual.innerHTML = nuevo.innerHTML;
            }

        })
        .catch(err => console.error('Error refrescando pedidos nuevos:', err));

    }, 5000); // ⏱ cada 5 segundos

});
</script>


<script>
let pedidoEmergenciaId = null;

/**
 * Mostrar modal de emergencia
 * (lo llama el poll / fetch)
 */
function mostrarModalEmergencia(data) {

    pedidoEmergenciaId = data.pedido_id;

    document.getElementById('emgCliente').innerText = data.cliente;

    const modal = new bootstrap.Modal(
        document.getElementById('modalEmergencia'),
        { backdrop: 'static', keyboard: false }
    );

    modal.show();
}


/**
 * Botón "INICIAR NAVEGACIÓN" del modal
 * 👉 SIMULA el submit real
 */
document.getElementById('btnNavegarEmergencia')
.addEventListener('click', function () {

    if (!pedidoEmergenciaId) {
        alert('Pedido de emergencia no válido');
        return;
    }

    const form = document.querySelector(
        `.form-iniciar-navegacion[data-pedido-id="${pedidoEmergenciaId}"]`
    );

    if (!form) {
        alert('El pedido no está disponible en la lista');
        return;
    }

    // 🔥 TOMAR LAT / LNG DEL BOTÓN REAL
    const btn = form.querySelector('.btn-navegar');
    const lat = btn?.dataset.lat;
    const lng = btn?.dataset.lng;

    if (lat && lng) {
        sessionStorage.setItem('nav_lat', lat);
        sessionStorage.setItem('nav_lng', lng);
    }


if (typeof enviarAvisoDeNavegacion === 'function') {

    const pedidoId   = form.querySelector('input[name="pedido_id"]')?.value;
    const cliente    = form.querySelector('input[name="cliente"]')?.value;
    const celular    = form.querySelector('input[name="celular"]')?.value;
    const motoqueroId= form.querySelector('input[name="motoquero_id"]')?.value;

    enviarAvisoDeNavegacion(
        pedidoId,
        cliente,
        celular,
        motoqueroId
    );
}

    // 🔥 SUBMIT REAL
    form.submit();

    // Cerrar modal
    const modalEl = document.getElementById('modalEmergencia');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal.hide();

    pedidoEmergenciaId = null;
});
</script>


<script>
setInterval(() => {
    fetch("{{ route('motoquero.check.emergencia') }}", {
        headers: {
            'Accept': 'application/json'
        },
        redirect: 'manual'
    })
    .then(res => {
        // ❌ Nunca seguir redirects
        if (res.status === 302) return null;
        if (res.status === 204) return null;
        if (res.status === 401) return null;

        return res.json();
    })
    .then(data => {
        if (!data || !data.emergencia) return;
        mostrarModalEmergencia(data);
    })
    .catch(() => {});
}, 5000);
</script>



<script>
let watchId = null;
let ultimaPosicion = null;
</script>

<script>
function iniciarTrackingMotoquero(motoqueroId) {

    if (!navigator.geolocation) {
        alert('GPS no soportado en este dispositivo');
        return;
    }

    watchId = navigator.geolocation.watchPosition(
        position => {

            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = position.coords.accuracy;

            ultimaPosicion = { lat, lng };

            enviarUbicacionServidor(motoqueroId, lat, lng, accuracy);

        },
        error => {
            console.error('Error GPS:', error);
        },
        {
            enableHighAccuracy: true,
            maximumAge: 5000,
            timeout: 10000
        }
    );
}
</script>

<script>
function enviarUbicacionServidor(motoqueroId, lat, lng, accuracy) {

    fetch('/motoquero/ubicacion', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            motoquero_id: motoqueroId,
            latitud: lat,
            longitud: lng,
            accuracy: accuracy
        })
    })
    .catch(err => console.error('Error enviando ubicación', err));
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    iniciarTrackingMotoquero({{ auth()->user()->motoquero->id }});
});
</script>


<script>

let sonidoActivo = null;
let intervaloVibracion = null;

function iniciarAlerta(tipo) {

    // 🔊 Elegir sonido según tipo
    if (tipo === 'ya_sale') {
        sonidoActivo = document.getElementById('sound-ya-sale');
    }

    if (tipo === 'no_contesta') {
        sonidoActivo = document.getElementById('sound-no-contesta');
    }

    // 🔊 SONIDO EN LOOP
    if (sonidoActivo) {
        sonidoActivo.currentTime = 0;
        sonidoActivo.loop = true;
        sonidoActivo.play().catch(() => {});
    }

    // 📳 VIBRACIÓN CONTINUA
    if (navigator.vibrate) {
        intervaloVibracion = setInterval(() => {
            navigator.vibrate([500, 300, 500]);
        }, 1200);
    }
}

function detenerAlerta() {

    // 🔇 Detener sonido
    if (sonidoActivo) {
        sonidoActivo.pause();
        sonidoActivo.currentTime = 0;
        sonidoActivo.loop = false;
    }

    // 📳 Detener vibración
    if (intervaloVibracion) {
        clearInterval(intervaloVibracion);
        intervaloVibracion = null;
    }

    if (navigator.vibrate) {
        navigator.vibrate(0);
    }
}

function checkAvisos() {

    fetch("{{ route('admin.motoquero.avisos') }}", {
        headers: {
            'Accept': 'application/json'
        },
        redirect: 'manual'
    })
    .then(res => {

        if (res.status === 302) return null;
        if (res.status === 204) return null;
        if (res.status === 401) return null;
        if (!res.ok) return null;

        return res.json();
    })
    .then(data => {

        if (!data || data.length === 0) return;

        data.forEach(aviso => {

            iniciarAlerta(aviso.tipo);

            let titulo = '';
            let color = '';
            let mensaje = '';

            if (aviso.tipo === 'ya_sale') {
                titulo = '🚀 Cliente en salida';
                color = '#28a745';
                mensaje = '<span style="color:green;font-weight:bold;">EL CLIENTE YA SALE</span>';
            }

            if (aviso.tipo === 'no_contesta') {
                titulo = '📞 Cliente no responde';
                color = '#dc3545';
                mensaje = '<span style="color:red;font-weight:bold;">NO CONTESTA – TOCAR PUERTA</span>';
            }

            Swal.fire({
                icon: 'info',
                title: titulo,
                html: `
                    <b>Pedido:</b> #${aviso.pedido_id}<br>
                    <b>Cliente:</b> ${aviso.cliente}<br><br>
                    ${mensaje}
                `,
                confirmButtonText: 'Entendido',
                confirmButtonColor: color,
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                detenerAlerta();
            });

        });

    })
    .catch(() => {});
}

// ⏱ Polling cada 5 segundos
setInterval(checkAvisos, 5000);

</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('[data-precio-box]').forEach(box => {

        const clienteId = box.dataset.cliente;

        function obtenerPrecio(productoId, claseSpan) {

            fetch(`/admin/pedidos/precio-cliente/${clienteId}/${productoId}`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.precio) {
                        box.querySelector(claseSpan).innerText = data.precio + ' Bs';
                    } else {
                        box.querySelector(claseSpan).innerText = '—';
                    }
                })
                .catch(() => {
                    box.querySelector(claseSpan).innerText = '—';
                });
        }

        // Producto ID 1 → Agua normal
        obtenerPrecio(1, '.precio-id-1');

        // Producto ID 2 → Agua alcalina
        obtenerPrecio(2, '.precio-id-2');

    });

});
</script>

@stop