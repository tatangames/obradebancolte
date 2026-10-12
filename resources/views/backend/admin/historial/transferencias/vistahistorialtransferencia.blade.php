@extends('adminlte::page')

@section('title', 'Historial / Transferencias')

@section('content_header')
    <h1>Historial / Transferencias</h1>
@stop

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)
@section('plugins.Sweetalert2', true)

@include('backend.urlglobal')

@section('content_top_nav_right')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet"/>
    <link href="{{ asset('css/select2.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}" type="text/css" rel="stylesheet">

    <li class="nav-item dropdown">
        <a href="#" class="nav-link" data-toggle="dropdown">
            <i class="fas fa-cogs"></i>
            <span class="d-none d-md-inline">{{ Auth::guard('admin')->user()->nombre }}</span>
        </a>
        <div class="dropdown-menu dropdown-menu-right">
            <a href="{{ route('admin.perfil') }}" class="dropdown-item">
                <i class="fas fa-user mr-2"></i> Editar Perfil
            </a>
        </div>
    </li>

    <li class="nav-item">
        <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="nav-link btn btn-link border-0 bg-transparent">
                <i class="fas fa-sign-out-alt"></i>
                <span class="d-none d-md-inline">Cerrar Sesión</span>
            </button>
        </form>
    </li>
@endsection

@section('content')
    <div id="divcontenedor">

        {{-- ══ FILTROS ══ --}}
        <section class="content" style="margin-bottom:0">
            <div class="container-fluid">
                <div class="card card-blue">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filtros</h3>
                    </div>
                    <div class="card-body">

                        {{-- Fila 1: Proyecto + Toggle + Fecha desde + Botones --}}
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label class="font-weight-bold">Proyecto</label>
                                <select class="form-control" id="filtro-proyecto">
                                    <option value="">— Todos —</option>
                                    <option value="general">Salida General</option>
                                    @foreach($arrayProyectos as $p)
                                        <option value="{{ $p->id }}"
                                                data-cerrado="{{ $p->transferido ? '1' : '0' }}">
                                            {{ $p->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="font-weight-bold d-block">Buscar como</label>
                                <div class="btn-group btn-block" role="group" id="toggle-tipo-busqueda">
                                    <button type="button"
                                            class="btn btn-primary active"
                                            data-tipo="origen">
                                        <i class="fas fa-sign-out-alt mr-1"></i> Origen
                                    </button>
                                    <button type="button"
                                            class="btn btn-outline-primary"
                                            data-tipo="destino">
                                        <i class="fas fa-sign-in-alt mr-1"></i> Destino
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="font-weight-bold">Fecha desde</label>
                                <input type="date" class="form-control" id="filtro-fecha-desde">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary btn-block mb-1" onclick="recargar()">
                                    <i class="fas fa-search mr-1"></i> Filtrar
                                </button>
                                <button class="btn btn-secondary btn-block" onclick="limpiarFiltros()">
                                    <i class="fas fa-times mr-1"></i> Limpiar
                                </button>
                            </div>
                        </div>

                        {{-- Fila 2: Fecha hasta + Material + Documento --}}
                        <div class="row align-items-end mt-3">
                            <div class="col-md-3">
                                <label class="font-weight-bold">Fecha hasta</label>
                                <input type="date" class="form-control" id="filtro-fecha-hasta">
                            </div>
                            <div class="col-md-4">
                                <label class="font-weight-bold">
                                    <i class="fas fa-box mr-1 text-muted"></i> Buscar por material
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="filtro-material"
                                       placeholder="Ej: cemento, varilla...">
                            </div>
                            <div class="col-md-4">
                                <label class="font-weight-bold">
                                    <i class="fas fa-file-alt mr-1 text-muted"></i> Buscar por documento
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="filtro-documento"
                                       placeholder="Ej: TRF-001...">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <small class="text-muted">Filtra por material o documento.</small>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>

        {{-- ══ TABLA ══ --}}
        <section class="content">
            <div class="container-fluid">
                <div class="card card-blue">
                    <div class="card-header">
                        <h3 class="card-title">Listado de Transferencias</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div id="tablaDatatable"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- ══ Modal Detalle ══ --}}
    <div class="modal fade" id="modalDetalle" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-exchange-alt mr-2"></i>
                        Detalle de Transferencia —
                        <span id="detalle-proyecto"></span>
                        <small class="ml-2" id="detalle-fecha"></small>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="detalle-desc-row" class="mb-3" style="display:none;">
                        <strong>Descripción:</strong> <span id="detalle-descripcion"></span>
                    </div>

                    <div id="detalle-loading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                    </div>
                    <div id="detalle-contenido" style="display:none;">
                        <table class="table table-bordered table-striped table-sm">
                            <thead class="thead-dark">
                            <tr>
                                <th style="width:4%">#</th>
                                <th style="width:28%">Material</th>
                                <th style="width:28%">Objeto Específico</th>
                                <th class="text-center" style="width:12%">Cantidad sobrante</th>
                                <th class="text-right" style="width:14%">Precio unitario</th>
                                <th class="text-right" style="width:14%">Subtotal</th>
                            </tr>
                            </thead>
                            <tbody id="detalle-tbody"></tbody>
                            <tfoot>
                            <tr class="table-dark">
                                <td colspan="5" class="text-right font-weight-bold">Total estimado:</td>
                                <td class="text-right font-weight-bold" id="detalle-total"></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div id="detalle-vacio" class="text-center text-muted py-4" style="display:none;">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p>Esta transferencia no tiene materiales registrados.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ Modal Uso del Material ══ --}}
    <div class="modal fade" id="modalUso" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-search-dollar mr-2"></i>
                        ¿A dónde fue el material? — <span id="uso-proyecto"></span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="uso-loading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                    </div>
                    <div id="uso-contenido" style="display:none;">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-dark">
                            <tr>
                                <th>Material</th>
                                <th class="text-center">Cantidad</th>
                                <th>Ficha</th>
                                <th>Talonario</th>
                                <th>Fecha</th>
                            </tr>
                            </thead>
                            <tbody id="uso-tbody"></tbody>
                        </table>
                        <div id="uso-reservas-alerta" class="alert alert-info" style="display:none;">
                            <i class="fas fa-bookmark mr-1"></i>
                            Además tiene <b id="uso-reservas-count"></b> reserva(s) activa(s) sobre este material.
                        </div>

                        <hr>
                        <h6 class="font-weight-bold">
                            <i class="fas fa-undo mr-1"></i>
                            Disponible para devolver a <span id="uso-proyecto-origen" class="text-primary"></span>
                        </h6>
                        <table class="table table-bordered table-sm" id="uso-tabla-disponible">
                            <thead class="thead-light">
                            <tr>
                                <th>Material</th>
                                <th class="text-center">Transferido</th>
                                <th class="text-center">Usado</th>
                                <th class="text-center">Reservado</th>
                                <th class="text-center">Disponible</th>
                                <th class="text-center" style="width:140px;">Cantidad a devolver</th>
                            </tr>
                            </thead>
                            <tbody id="uso-disponible-tbody"></tbody>
                        </table>
                        <div id="uso-disponible-vacio" class="text-muted mb-2" style="display:none;">
                            <i class="fas fa-info-circle mr-1"></i>
                            No queda material disponible para devolver — todo fue usado o reservado.
                        </div>
                        <small class="text-muted d-block mb-2">
                            Escribe la cantidad que deseas devolver en cada fila (puede ser parcial, hasta 2 decimales).
                            No puede superar el valor de "Disponible".
                        </small>
                        <button type="button" class="btn btn-success btn-sm" id="btn-devolver" style="display:none;">
                            <i class="fas fa-undo mr-1"></i> Devolver al proyecto de origen
                        </button>
                    </div>
                    <div id="uso-vacio" class="text-center text-muted py-4" style="display:none;">
                        <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                        <p>Este material no ha sido usado ni reservado todavía. Se puede eliminar sin problema.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

@stop

@section('js')
    <script src="{{ asset('js/toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/axios.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>
    <script src="{{ asset('js/select2.min.js') }}" type="text/javascript"></script>

    <script>
        $(function () {
            const ruta = "{{ url('/admin/historial/transferencias/tabla') }}";

            // ── Estado del toggle Origen / Destino ────────────────
            let tipoBusqueda = 'origen';   // valor por defecto

            // ── Select2 ───────────────────────────────────────────
            $('#filtro-proyecto').select2({
                theme: 'bootstrap-5',
                placeholder: '— Todos —',
                allowClear: true,
                language: { noResults: function () { return 'No encontrado'; } },
                templateResult: function (data) {
                    if (!data.id || data.id === 'general') return data.text;
                    var cerrado = $(data.element).data('cerrado') == '1';
                    return $('<span class="d-flex align-items-center justify-content-between">')
                        .append($('<span>').text(data.text))
                        .append($('<span>')
                            .addClass(cerrado ? 'badge badge-danger ml-2' : 'badge badge-success ml-2')
                            .text(cerrado ? 'Cerrado' : 'Activo'));
                },
                templateSelection: function (data) {
                    if (!data.id || data.id === 'general') return data.text;
                    var cerrado = $(data.element).data('cerrado') == '1';
                    return $('<span>')
                        .append($('<span>').text(data.text))
                        .append($('<span>')
                            .addClass(cerrado ? 'badge badge-danger ml-2' : 'badge badge-success ml-2')
                            .text(cerrado ? 'Cerrado' : 'Activo'));
                }
            });

            // ── Toggle Origen / Destino ───────────────────────────
            $('#toggle-tipo-busqueda button').on('click', function () {
                tipoBusqueda = $(this).data('tipo');

                $('#toggle-tipo-busqueda button')
                    .removeClass('btn-primary active')
                    .addClass('btn-outline-primary');

                $(this)
                    .removeClass('btn-outline-primary')
                    .addClass('btn-primary active');
            });

            // ── DataTable ─────────────────────────────────────────
            function initDataTable() {
                if ($.fn.DataTable.isDataTable('#tabla')) {
                    $('#tabla').DataTable().destroy();
                }
                $('#tabla').DataTable({
                    paging: true,
                    lengthChange: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    autoWidth: false,
                    responsive: true,
                    pagingType: "full_numbers",
                    lengthMenu: [[50, 100, -1], [50, 100, "Todo"]],
                    language: {
                        sProcessing:   "Procesando...",
                        sLengthMenu:   "Mostrar _MENU_ registros",
                        sZeroRecords:  "No se encontraron resultados",
                        sEmptyTable:   "Ningún dato disponible en esta tabla",
                        sInfo:         "Mostrando _START_ a _END_ de _TOTAL_ registros",
                        sInfoEmpty:    "Mostrando 0 a 0 de 0 registros",
                        sInfoFiltered: "(filtrado de _MAX_ registros)",
                        sSearch:       "Buscar:",
                        oPaginate: {
                            sFirst: "Primero", sLast: "Último",
                            sNext: "Siguiente", sPrevious: "Anterior"
                        }
                    },
                    dom:
                        "<'row align-items-center'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 text-md-right'f>>" +
                        "tr" +
                        "<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
                });
                $('#tabla_length select').addClass('form-control form-control-sm');
                $('#tabla_filter input').addClass('form-control form-control-sm').css('display', 'inline-block');
            }

            // ── Cargar tabla ──────────────────────────────────────
            function cargarTabla() {
                const proyecto   = $('#filtro-proyecto').val();
                const fechaDesde = $('#filtro-fecha-desde').val();
                const fechaHasta = $('#filtro-fecha-hasta').val();
                const material   = $('#filtro-material').val().trim();
                const documento  = $('#filtro-documento').val().trim();

                const params = new URLSearchParams();
                if (proyecto) {
                    params.append('proyecto',      proyecto);
                    params.append('tipo_busqueda', tipoBusqueda);
                }
                if (fechaDesde) params.append('fecha_desde', fechaDesde);
                if (fechaHasta) params.append('fecha_hasta', fechaHasta);
                if (material)   params.append('material',    material);
                if (documento)  params.append('documento',   documento);

                const url = params.toString() ? ruta + '?' + params.toString() : ruta;

                $('#tablaDatatable').load(url, function () {
                    initDataTable();
                    $('[data-toggle="tooltip"]').tooltip(); // reinicializar tooltips
                });
            }

            window.recargar = function () { cargarTabla(); };

            window.limpiarFiltros = function () {
                $('#filtro-proyecto').val('').trigger('change');
                $('#filtro-fecha-desde').val('');
                $('#filtro-fecha-hasta').val('');
                $('#filtro-material').val('');
                $('#filtro-documento').val('');

                // reset toggle a Origen
                tipoBusqueda = 'origen';
                $('#toggle-tipo-busqueda button')
                    .removeClass('btn-primary active')
                    .addClass('btn-outline-primary');
                $('#toggle-tipo-busqueda button[data-tipo="origen"]')
                    .removeClass('btn-outline-primary')
                    .addClass('btn-primary active');

                cargarTabla();
            };

            cargarTabla();
        });
    </script>

    <script>
        // ── Constantes y utilidades de cantidad ───────────────────────────
        var CANTIDAD_MAXIMA = 1000000;               // máximo permitido por devolución
        var REGLA_DECIMAL   = /^\d+(\.\d{1,2})?$/;   // entero o hasta 2 decimales

        // Redondea a 2 decimales evitando errores de punto flotante
        function redondear2(n) {
            return Math.round((Number(n) + Number.EPSILON) * 100) / 100;
        }

        // Redondea a 4 decimales (precios y subtotales de este historial usan 4)
        function redondear4(n) {
            return Math.round((Number(n) + Number.EPSILON) * 10000) / 10000;
        }

        // Cantidades siempre con 2 decimales y separador de miles (1,250.50)
        function formatoCantidad(n) {
            return redondear2(n || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // Deja solo dígitos y un punto, con máximo 2 decimales
        function sanitizarDecimal(valor) {
            valor = String(valor).replace(/[^0-9.]/g, '');

            var partes = valor.split('.');
            if (partes.length > 2) {
                valor = partes[0] + '.' + partes.slice(1).join('');
                partes = valor.split('.');
            }
            if (partes.length === 2) {
                valor = partes[0] + '.' + partes[1].substring(0, 2);
            }
            return valor;
        }

        // Normaliza lo escrito: ".5" → "0.5"  |  "5." → "5"  |  "." → ""
        function normalizarCantidad(valor) {
            valor = String(valor ?? '').trim();
            if (valor === '' || valor === '.') return '';
            if (valor.charAt(0) === '.') valor = '0' + valor;
            if (valor.charAt(valor.length - 1) === '.') valor = valor.slice(0, -1);
            return valor;
        }

        // Escapa texto para meterlo de forma segura dentro de HTML
        function escapeHtml(texto) {
            return String(texto ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        // Validar teclas numéricas (permite un solo punto decimal)
        function validateInput(event) {
            if (event.ctrlKey || event.metaKey) return true; // copiar / pegar / seleccionar todo

            const key = event.key;
            if (["Backspace","ArrowLeft","ArrowRight","Delete","Tab","Home","End","Enter"].includes(key)) return true;

            if (key === ".") {
                return event.target.value.indexOf('.') === -1;
            }

            if (key === "e" || key === "E" || key === "-" || key === " " || isNaN(Number(key))) return false;
            return true;
        }

        // ── Detalle ───────────────────────────────────────────────
        function verDetalle(id, proyecto, fecha, documento, descripcion) {
            $('#detalle-proyecto').text(proyecto);
            $('#detalle-fecha').text(fecha);
            $('#detalle-tbody').html('');
            $('#detalle-total').text('');
            $('#detalle-contenido').hide();
            $('#detalle-vacio').hide();
            $('#detalle-loading').show();

            // Documento
            if (documento) {
                $('#detalle-doc-row').show();
            } else {
                $('#detalle-doc-row').hide();
            }

            // Descripción
            if (descripcion) {
                $('#detalle-descripcion').text(descripcion);
                $('#detalle-desc-row').show();
            } else {
                $('#detalle-desc-row').hide();
            }

            $('#modalDetalle').modal('show');

            axios.post(urlAdmin + '/admin/historial/transferencias/detalle', {
                id: id
            })
                .then((response) => {
                    $('#detalle-loading').hide();

                    if (response.data.success === 1 &&
                        response.data.detalle.length > 0) {

                        let html = '';
                        let total = 0;

                        response.data.detalle.forEach((fila, index) => {
                            const precio   = parseFloat(fila.precio) || 0;
                            // parseFloat (antes parseInt): parseInt truncaba 5.75 a 5
                            // y además deformaba el subtotal y el total estimado.
                            const cantidad = redondear2(fila.cantidad_sobrante);
                            const subtotal = redondear4(precio * cantidad);

                            total = redondear4(total + subtotal);

                            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${escapeHtml(fila.nombre_material)}</td>
                    <td>
                        <small class="text-muted">
                            ${escapeHtml(fila.objeto_especifico)}
                        </small>
                    </td>
                    <td class="text-center">${formatoCantidad(cantidad)}</td>
                    <td class="text-right">$${escapeHtml(fila.precio)}</td>
                    <td class="text-right">$${subtotal.toFixed(4)}</td>
                </tr>`;
                        });

                        $('#detalle-tbody').html(html);
                        $('#detalle-total').text('$' + total.toFixed(4));
                        $('#detalle-contenido').show();

                    } else {
                        $('#detalle-vacio').show();
                    }
                })
                .catch((error) => {
                    $('#detalle-loading').hide();
                    $('#detalle-vacio').show();

                    console.error(error);
                    toastr.error('Error al cargar el detalle');
                });
        }

        // ── Eliminar ──────────────────────────────────────────────
        function eliminar(id) {
            Swal.fire({
                title: '¿Eliminar transferencia?',
                text: 'Se eliminarán también todos los materiales del detalle. Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    openLoading();
                    axios.post(urlAdmin + '/admin/historial/transferencias/eliminar', { id: id })
                        .then((response) => {
                            closeLoading();

                            switch (response.data.success) {

                                case 1:
                                    toastr.success('Transferencia eliminada correctamente');
                                    recargar();
                                    break;

                                case 2:
                                    // El material que entró al destino ya fue usado o reservado
                                    Swal.fire({
                                        title: 'No se puede eliminar',
                                        html: 'El material <b>' +
                                            escapeHtml(response.data.nombre_material || '—') +
                                            '</b> ya fue usado o reservado en el proyecto destino.<br><br>' +
                                            'Debe revertir esos movimientos antes de eliminar la transferencia.',
                                        icon: 'warning',
                                        confirmButtonColor: '#d33',
                                        confirmButtonText: 'Entendido'
                                    });
                                    break;

                                case 0:
                                    toastr.error('La transferencia no existe o ya fue eliminada');
                                    recargar();
                                    break;

                                case 99:
                                    toastr.error('Ocurrió un error al eliminar. Intente nuevamente.');
                                    break;

                                default:
                                    toastr.error('Error al eliminar');
                            }
                        })
                        .catch(() => { closeLoading(); toastr.error('Error al eliminar'); });
                }
            });
        }

        // ── Ver Uso del Material ────────────────────────────────────
        let usoTransferenciaId = null; // id de la transferencia abierta actualmente en el modal

        function verUso(id, proyecto) {
            usoTransferenciaId = id;

            $('#uso-proyecto').text(proyecto);
            $('#uso-tbody').html('');
            $('#uso-contenido').hide();
            $('#uso-vacio').hide();
            $('#uso-reservas-alerta').hide();
            $('#uso-disponible-tbody').html('');
            $('#uso-tabla-disponible').show();
            $('#uso-disponible-vacio').hide();
            $('#btn-devolver').hide();
            $('#uso-loading').show();

            $('#modalUso').modal('show');

            cargarUso(id, proyecto);
        }

        function cargarUso(id, proyecto) {
            axios.post(urlAdmin + '/admin/historial/transferencias/uso', { id: id })
                .then((response) => {
                    $('#uso-loading').hide();

                    const usos = response.data.usos || [];
                    const reservas = response.data.reservas_activas || 0;
                    const materiales = response.data.materiales || [];

                    if (usos.length === 0 && reservas === 0 && materiales.length === 0) {
                        $('#uso-vacio').show();
                        $('#uso-contenido').hide();
                        return;
                    }

                    // ── Tabla de despachos ya hechos ──────────────
                    let html = '';
                    usos.forEach((fila) => {
                        html += `
                <tr>
                    <td>${escapeHtml(fila.material)}</td>
                    <td class="text-center">${formatoCantidad(fila.cantidad)}</td>
                    <td>${escapeHtml(fila.ficha)}</td>
                    <td>${escapeHtml(fila.talonario)}</td>
                    <td>${escapeHtml(fila.fecha)}</td>
                </tr>`;
                    });

                    $('#uso-tbody').html(html);

                    if (reservas > 0) {
                        $('#uso-reservas-count').text(reservas);
                        $('#uso-reservas-alerta').show();
                    } else {
                        $('#uso-reservas-alerta').hide();
                    }

                    // ── Resumen de disponible para devolver ───────
                    $('#uso-proyecto-origen').text(response.data.nombre_proyecto_origen || '—');

                    if (materiales.length === 0) {
                        $('#uso-tabla-disponible').hide();
                        $('#uso-disponible-vacio').show();
                        $('#btn-devolver').hide();
                    } else {
                        let htmlMat = '';
                        materiales.forEach((m) => {
                            // Verificación: disponible = transferido - usado - reservado (calculado en backend)
                            const disponible = redondear2(m.disponible);
                            // Requerido para el envío al backend. Se aceptan varios nombres
                            // por si el endpoint /uso lo devuelve con otra llave.
                            const idMaterial = m.id_material ?? m.material_id ?? m.id ?? '';

                            htmlMat += `
                    <tr data-id-material="${escapeHtml(idMaterial)}" data-disponible="${disponible.toFixed(2)}">
                        <td>${escapeHtml(m.material)}</td>
                        <td class="text-center">${formatoCantidad(m.cantidad_original)}</td>
                        <td class="text-center">${formatoCantidad(m.cantidad_usada)}</td>
                        <td class="text-center">${formatoCantidad(m.cantidad_reservada)}</td>
                        <td class="text-center font-weight-bold text-success">${formatoCantidad(disponible)}</td>
                        <td class="text-center">
                            <input type="text"
                                   class="form-control form-control-sm input-cantidad-devolver"
                                   inputmode="decimal"
                                   maxlength="10"
                                   autocomplete="off"
                                   placeholder="0.00"
                                   ${disponible <= 0 ? 'disabled' : ''}>
                        </td>
                    </tr>`;
                        });
                        $('#uso-disponible-tbody').html(htmlMat);
                        $('#uso-tabla-disponible').show();
                        $('#uso-disponible-vacio').hide();
                        $('#btn-devolver').show();
                    }

                    $('#uso-contenido').show();
                })
                .catch((error) => {
                    $('#uso-loading').hide();
                    $('#uso-vacio').show();
                    console.error(error);
                    toastr.error('Error al consultar el uso del material');
                });
        }

        // Teclas permitidas en "Cantidad a devolver" (números y un solo punto)
        $(document).on('keydown', '.input-cantidad-devolver', function (e) {
            if (!validateInput(e.originalEvent)) {
                e.preventDefault();
            }
        });

        // Valida en vivo: solo decimales (máx. 2) y sin superar el disponible de la fila
        $(document).on('input', '.input-cantidad-devolver', function () {
            const $input     = $(this);
            const $row       = $input.closest('tr');
            const disponible = redondear2($row.attr('data-disponible'));

            $input.val(sanitizarDecimal($input.val()));

            const limite = Math.min(disponible, CANTIDAD_MAXIMA);
            if (Number($input.val()) > limite) {
                $input.val(String(redondear2(limite)));
                toastr.warning('No puede devolver más de lo disponible (' + formatoCantidad(limite) + ')');
            }
        });

        // ── Devolver material al proyecto de origen ─────────────────
        $(document).on('click', '#btn-devolver', function () {
            if (!usoTransferenciaId) {
                toastr.error('No se identificó la transferencia.');
                return;
            }

            const items = [];
            let mensajeError = '';
            let faltaIdMaterial = false;

            $('#uso-disponible-tbody tr').each(function () {
                const $row = $(this);
                const idMaterial = parseInt($row.attr('data-id-material'), 10);
                const disponible = redondear2($row.attr('data-disponible'));
                const cantidadTxt = normalizarCantidad($row.find('.input-cantidad-devolver').val());

                if (cantidadTxt === '') {
                    return; // fila sin cantidad a devolver, se omite
                }

                if (!REGLA_DECIMAL.test(cantidadTxt)) {
                    mensajeError = 'Hay una cantidad inválida (máximo 2 decimales). Corrígela antes de continuar.';
                    return;
                }

                const cantidad = redondear2(cantidadTxt);

                if (cantidad <= 0) {
                    return; // cero equivale a no devolver
                }

                if (isNaN(idMaterial)) {
                    faltaIdMaterial = true;
                    return;
                }

                if (cantidad > CANTIDAD_MAXIMA) {
                    mensajeError = 'La cantidad máxima por devolución es 1,000,000.00.';
                    return;
                }

                if (cantidad > disponible) {
                    mensajeError = 'Hay una cantidad que supera lo disponible. Corrígela antes de continuar.';
                    return;
                }

                items.push({
                    id_material: idMaterial,
                    cantidad: cantidad.toFixed(2)   // siempre con 2 decimales
                });
            });

            if (faltaIdMaterial) {
                toastr.error('No se pudo identificar el material (falta id_material desde el servidor). Revisa el endpoint /uso.');
                return;
            }

            if (mensajeError) {
                toastr.error(mensajeError);
                return;
            }

            if (items.length === 0) {
                toastr.warning('Ingresa al menos una cantidad a devolver.');
                return;
            }

            Swal.fire({
                title: '¿Confirmar devolución?',
                text: 'Se devolverá el material seleccionado al proyecto de origen.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, devolver',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (!result.isConfirmed) return;

                openLoading();
                axios.post(urlAdmin + '/admin/historial/transferencias/devolver', {
                    id: usoTransferenciaId,
                    items: items
                })
                    .then((response) => {
                        closeLoading();

                        switch (response.data.success) {
                            case 1:
                                toastr.success('Material devuelto correctamente al proyecto de origen');
                                // Refrescar el detalle de uso/disponible dentro del modal
                                $('#uso-loading').show();
                                $('#uso-contenido').hide();
                                cargarUso(usoTransferenciaId, $('#uso-proyecto').text());
                                // Refrescar el listado principal
                                if (typeof recargar === 'function') recargar();
                                break;

                            case 2:
                                toastr.error(response.data.msg || 'No se encontró el material solicitado en el destino.');
                                break;

                            case 3:
                                Swal.fire({
                                    title: 'Cantidad no disponible',
                                    html: 'La cantidad a devolver de <b>' +
                                        escapeHtml(response.data.nombre_material || '—') +
                                        '</b> supera lo disponible (' +
                                        escapeHtml(response.data.disponible ?? '—') + ').',
                                    icon: 'warning',
                                    confirmButtonText: 'Entendido'
                                });
                                // Refrescar por si el disponible cambió entre tanto
                                cargarUso(usoTransferenciaId, $('#uso-proyecto').text());
                                break;

                            case 0:
                                toastr.error(response.data.msg || 'Datos inválidos.');
                                break;

                            case 99:
                                toastr.error('Ocurrió un error al procesar la devolución. Intente nuevamente.');
                                break;

                            default:
                                toastr.error('Error al devolver el material.');
                        }
                    })
                    .catch((error) => {
                        closeLoading();
                        console.error(error);
                        toastr.error('Error al devolver el material.');
                    });
            });
        });
    </script>
@endsection
