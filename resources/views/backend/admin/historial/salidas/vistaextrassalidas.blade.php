@extends('adminlte::page')

@section('title', 'Agregar Extras — Salida #{{ $salida->id }}')

@section('content_header')
    <h1>Agregar Extras a Salida</h1>
@stop

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
    <style>
        table { table-layout: fixed; }
        *:focus { outline: none; }
        #modalCantidad .modal-dialog { max-width: 95%; }
        .cursor-pointer:hover { cursor: pointer; color: #401fd2; font-weight: bold; }
    </style>

    <div id="divcontenedor">

        {{-- Info de la salida existente --}}
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-10">
                        <div class="card card-gray-dark">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Salida #{{ $salida->id }} —
                                    {{ $salida->tipoproyecto->nombre ?? '' }}
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="text-muted">Fecha</label>
                                        <p><strong>{{ date('d/m/Y', strtotime($salida->fecha)) }}</strong></p>
                                    </div>
                                    <div class="col-md-9">
                                        <label class="text-muted">Descripción</label>
                                        <p><strong>{{ $salida->descripcion ?? '' }}</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Botones --}}
        <section class="content-header">
            <div class="row" style="margin-left: 0">
                <button type="button" onclick="abrirModal()" class="btn btn-primary btn-sm">
                    <i class="fas fa-search mr-1"></i> Buscar Material
                </button>
                <a href="{{ route('admin.historial.salidas.index') }}"
                   class="btn btn-secondary btn-sm ml-2">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </section>

        {{-- Modal buscar material --}}
        <div class="modal fade" id="modalRepuesto">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header" style="background:#2156af">
                        <h4 class="modal-title" style="color:#fff">
                            <i class="fas fa-search mr-2"></i>Buscar Material
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" style="color:#fff">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formulario-repuesto">
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Material — Solo con inventario disponible</label>
                                    <table class="table" id="matriz-busqueda">
                                        <tbody>
                                        <tr>
                                            <td>
                                                <input id="inputBuscador" autocomplete="off"
                                                       class="form-control" style="width:100%"
                                                       onkeyup="buscarMaterial(this)"
                                                       maxlength="300" type="text"
                                                       placeholder="Escribir nombre del material…">
                                                <div class="droplista" style="position:absolute;z-index:9;width:95% !important;"></div>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div id="tablaRepuesto"></div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal cantidad --}}
        <div class="modal fade" id="modalCantidad">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header" style="background:#1a3a6b">
                        <h4 class="modal-title" style="color:#fff">
                            <i class="fas fa-boxes mr-2"></i>Salida de Material
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" style="color:#fff">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formulario-material">
                            <div class="card-body">
                                <input type="hidden" id="id-material-seleccionado">
                                <div class="form-row mb-3">
                                    <div class="col-md-9">
                                        <label>Material</label>
                                        <input type="text" disabled class="form-control" id="info-material">
                                    </div>
                                    <div class="col-md-3">
                                        <label>U/M</label>
                                        <input type="text" disabled class="form-control" id="info-medida">
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" id="matrizM">
                                        <thead>
                                        <tr>
                                            <th>Fecha Ingreso</th>
                                            <th>Valor</th>
                                            <th>Cant. Actual</th>
                                            <th>Cant. Salida</th>
                                        </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-success" onclick="agregarAlDetalle()">
                            <i class="fas fa-plus mr-1"></i> Agregar al Detalle
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabla detalle --}}
        <section class="content-header">
            <div class="row mb-2">
                <div class="col-sm-6" style="margin-left: 15px">
                    <h2>Materiales a Sacar</h2>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card card-gray-dark">
                    <div class="card-header">
                        <h3 class="card-title">Detalle</h3>
                    </div>
                    <table class="table" id="matriz" style="margin: 0 15px;">
                        <thead>
                        <tr>
                            <th style="width:5%">#</th>
                            <th style="width:50%">Material</th>
                            <th style="width:15%">Salida</th>
                            <th style="width:10%">Opciones</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </section>

        <div class="modal-footer justify-content-between" style="margin-top: 25px;">
            <button type="button" class="btn btn-success" onclick="preguntaGuardar()">
                <i class="fas fa-save mr-1"></i> Guardar Extras
            </button>
        </div>

    </div>
@stop

@section('js')
    <script src="{{ asset('js/toastr.min.js') }}"></script>
    <script src="{{ asset('js/axios.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>

    <script>
        const ID_SALIDA   = {{ $salida->id }};
        const ID_PROYECTO = {{ $salida->id_tipoproyecto ?? 'null' }};

        // ── Constantes de cantidad ────────────────────────────────────────
        var CANTIDAD_MAXIMA = 1000000;               // máximo permitido por salida
        var REGLA_DECIMAL   = /^\d+(\.\d{1,2})?$/;   // entero o hasta 2 decimales

        // Redondea a 2 decimales evitando errores de punto flotante
        function redondear2(n) {
            return Math.round((Number(n) + Number.EPSILON) * 100) / 100;
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

        // Escapa texto para meterlo de forma segura dentro de value='...'
        function escapeHtml(texto) {
            return String(texto ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        $(document).ready(function () {
            $(document).click(function () { $(".droplista").hide(); });
        });

        function abrirModal() {
            document.getElementById('tablaRepuesto').innerHTML = '';
            document.getElementById('formulario-repuesto').reset();
            $('#modalRepuesto').modal('show');
        }

        // Validar teclas numéricas (permite un solo punto decimal)
        function validateInput(event) {
            if (event.ctrlKey || event.metaKey) return true; // copiar / pegar / seleccionar todo

            const key = event.key;
            if (["Backspace","ArrowLeft","ArrowRight","Delete","Tab","Home","End"].includes(key)) return true;

            if (key === ".") {
                return event.target.value.indexOf('.') === -1;
            }

            if (key === "e" || key === "E" || key === "-" || key === " " || isNaN(Number(key))) return false;
            return true;
        }

        // Cantidad de salida: decimales (máx. 2), tope = min(cantidad actual, 1,000,000)
        function validateCantidadSalida(input, maxCantidad) {
            input.value = sanitizarDecimal(input.value);

            var limite = Math.min(Number(maxCantidad), CANTIDAD_MAXIMA);
            if (Number(input.value) > limite) {
                input.value = String(redondear2(limite));
            }
        }

        // Fila con error en la tabla del modal de cantidades
        function colorRojoModal(index) {
            $("#matrizM tbody tr:eq(" + index + ")").css('background', '#f8d7da');
        }

        function colorLimpiarModal() {
            $("#matrizM tbody tr").css('background', '');
        }

        // ── Buscador con debounce y cancelación de petición anterior ──────
        var debounceBuscador   = null;
        var controllerBuscador = null;

        function buscarMaterial(e) {
            var row   = $(e).closest('tr');
            var texto = e.value;

            clearTimeout(debounceBuscador);
            debounceBuscador = setTimeout(function () {
                ejecutarBusquedaMaterial(row, texto);
            }, 300);
        }

        function ejecutarBusquedaMaterial(row, texto) {
            if (controllerBuscador) controllerBuscador.abort();
            controllerBuscador = new AbortController();

            axios.post(urlAdmin + '/admin/buscar/material/disponible', {
                query:       texto,
                id_proyecto: ID_PROYECTO
            }, {
                timeout: 10000,
                signal: controllerBuscador.signal
            })
                .then((response) => {
                    $(row).find(".droplista").fadeIn().html(response.data);
                })
                .catch((error) => {
                    if (axios.isCancel(error) || error.code === 'ERR_CANCELED') return;
                    toastr.error('No se pudo completar la búsqueda. Intenta de nuevo.');
                });
        }

        function modificarValor(edrop) {
            openLoading();
            $("#matrizM tbody tr").remove();
            var formData = new FormData();
            formData.append('id', edrop.id);
            formData.append('id_proyecto', ID_PROYECTO);

            axios.post(urlAdmin + '/admin/buscar/material/disponibilidad', formData)
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        if (response.data.disponible === 1) {
                            toastr.info('NO HAY INVENTARIO DISPONIBLE');
                            return;
                        }

                        $('#id-material-seleccionado').val(edrop.id);
                        $('#info-material').val(response.data.nombreMaterial);
                        $('#info-medida').val(response.data.nombreMedida);

                        $.each(response.data.arrayIngreso, function (key, val) {
                            var markup = "<tr>" +
                                "<td><input disabled value='" + escapeHtml(val.fechaIngreso) + "' class='form-control form-control-sm' type='text'></td>" +
                                "<td><input disabled value='" + escapeHtml(val.precioFormat) + "' class='form-control form-control-sm' type='text'></td>" +
                                "<td><input name='arrayCantidadActual[]' disabled " +
                                "data-cantidadActualFila='" + val.cantidadActual + "' " +
                                "value='" + val.cantidadActual + "' class='form-control form-control-sm' type='text'></td>" +
                                "<td><input class='form-control form-control-sm' " +
                                "data-idfilaentradadetalle='" + val.id + "' " +
                                "name='arrayCantidadSalida[]' type='text' inputmode='decimal' autocomplete='off' maxlength='10' " +
                                "placeholder='0.00' " +
                                "onkeydown=\"return validateInput(event);\" " +
                                "oninput=\"validateCantidadSalida(this, " + val.cantidadActual + ");\"></td>" +
                                "</tr>";
                            $("#matrizM tbody").append(markup);
                        });

                        $('#modalCantidad').modal('show');
                    } else {
                        toastr.error('Error al cargar material');
                    }
                })
                .catch(() => { closeLoading(); toastr.error('Error'); });
        }

        function agregarAlDetalle() {
            var arrayIdEntradaDetalle = $("input[name='arrayCantidadSalida[]']").map(function () { return $(this).attr("data-idfilaentradadetalle"); }).get();
            var arrayCantidadSalida   = $("input[name='arrayCantidadSalida[]']").map(function () { return normalizarCantidad($(this).val()); }).get();
            var arrayCantidadActual   = $("input[name='arrayCantidadActual[]']").map(function () { return $(this).attr("data-cantidadActualFila"); }).get();

            colorLimpiarModal();
            var habraSalida = true;

            for (var a = 0; a < arrayCantidadSalida.length; a++) {
                var fc = arrayCantidadSalida[a];

                if (fc !== '') {

                    if (!REGLA_DECIMAL.test(fc)) {
                        colorRojoModal(a);
                        toastr.error('Fila #' + (a+1) + ': Número inválido (máximo 2 decimales)');
                        return;
                    }

                    var cantidad = redondear2(fc);

                    if (cantidad <= 0) {
                        colorRojoModal(a);
                        toastr.error('Fila #' + (a+1) + ': No se permite cero');
                        return;
                    }

                    if (cantidad > CANTIDAD_MAXIMA) {
                        colorRojoModal(a);
                        toastr.error('Fila #' + (a+1) + ': Máximo 1,000,000.00');
                        return;
                    }

                    if (cantidad > redondear2(arrayCantidadActual[a])) {
                        colorRojoModal(a);
                        toastr.error('Fila #' + (a+1) + ': Supera cantidad actual');
                        return;
                    }

                    habraSalida = false;
                }
            }
            if (habraSalida) { toastr.error('Registrar mínimo 1 salida'); return; }

            var nombreTexto = document.getElementById('info-material').value;
            var nFilas = $('#matriz > tbody > tr').length;

            for (var z = 0; z < arrayCantidadSalida.length; z++) {
                var fcz = arrayCantidadSalida[z];
                if (fcz !== '' && redondear2(fcz) > 0) {
                    nFilas++;

                    var fcFormato = redondear2(fcz).toFixed(2); // siempre 2 decimales

                    var markup = "<tr>" +
                        "<td><p id='fila" + nFilas + "' class='form-control' style='max-width:55px'>" + nFilas + "</p></td>" +
                        "<td>" +
                        "<input name='idmaterialArray[]' type='hidden' data-idmaterialArray='" + arrayIdEntradaDetalle[z] + "'>" +
                        "<input disabled value='" + escapeHtml(nombreTexto) + "' class='form-control form-control-sm' type='text'>" +
                        "</td>" +
                        "<td><input name='salidaArray[]' disabled data-cantidadSalida='" + fcFormato + "' value='" + fcFormato + "' class='form-control form-control-sm' type='text'></td>" +
                        "<td><button type='button' class='btn btn-danger btn-sm btn-block' onclick='borrarFila(this)'>Borrar</button></td>" +
                        "</tr>";
                    $("#matriz tbody").append(markup);
                }
            }

            $('#modalCantidad').modal('hide');
            document.getElementById('inputBuscador').value = '';
            toastr.success('Agregado al detalle');
        }

        function preguntaGuardar() {
            if ($('#matriz > tbody > tr').length === 0) {
                toastr.error('Agrega al menos un material');
                return;
            }
            Swal.fire({
                title: '¿Guardar materiales extras?',
                text: 'Se agregarán a la salida #' + ID_SALIDA,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Sí, guardar'
            }).then((result) => {
                if (result.isConfirmed) guardarExtras();
            });
        }

        function guardarExtras() {
            var idEntradaDetalle = $("input[name='idmaterialArray[]']").map(function () { return $(this).attr("data-idmaterialArray"); }).get();
            var salidaCantidad   = $("input[name='salidaArray[]']").map(function ()    { return $(this).attr("data-cantidadSalida"); }).get();

            // Validación final antes de enviar
            for (var a = 0; a < salidaCantidad.length; a++) {
                var ic = salidaCantidad[a];
                if (!ic || !REGLA_DECIMAL.test(ic) || redondear2(ic) <= 0 || redondear2(ic) > CANTIDAD_MAXIMA) {
                    toastr.error('Fila #' + (a + 1) + ' — Cantidad inválida (máximo 2 decimales, entre 0.01 y 1,000,000)');
                    return;
                }
            }

            const contenedorArray = [];
            for (var i = 0; i < salidaCantidad.length; i++) {
                contenedorArray.push({
                    infoIdEntradaDeta: idEntradaDetalle[i],
                    infoCantidad:      salidaCantidad[i],
                });
            }

            openLoading();
            var formData = new FormData();
            formData.append('id_salida',       ID_SALIDA);
            formData.append('contenedorArray', JSON.stringify(contenedorArray));

            axios.post(urlAdmin + '/admin/historial/salidas/extras/guardar', formData)
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 10) {
                        Swal.fire({
                            title: 'Extras guardados',
                            icon: 'success',
                            allowOutsideClick: false,
                            confirmButtonColor: '#28a745',
                            confirmButtonText: 'Aceptar'
                        }).then((r) => { if (r.isConfirmed) $("#matriz tbody tr").remove(); });
                    } else if (response.data.success === 2) {
                        toastr.error('Fila #' + response.data.fila + ': Supera unidades disponibles');
                    } else {
                        toastr.error('Error al guardar');
                    }
                })
                .catch(() => { closeLoading(); toastr.error('Error al guardar'); });
        }

        function borrarFila(el) {
            el.closest('tr').remove();
            setearFila();
        }

        function setearFila() {
            var table  = document.getElementById('matriz');
            var conteo = 0;
            for (var r = 1; r < table.rows.length; r++) {
                conteo++;
                table.rows[r].cells[0].children[0].innerHTML = conteo;
            }
        }
    </script>
@endsection
