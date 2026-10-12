@extends('adminlte::page')

@section('title', 'Agregar Extras — Entrada #{{ $entrada->id }}')

@section('content_header')
    <h1>Agregar Extras a Entrada</h1>
@stop

@section('plugins.Sweetalert2', true)
@include('backend.urlglobal')

@section('content_top_nav_right')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet"/>
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
        .cursor-pointer:hover { cursor: pointer; color: #401fd2; font-weight: bold; }
        *:focus { outline: none; }
    </style>

    <div id="divcontenedor">

        {{-- Info de la entrada existente --}}
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-10">
                        <div class="card card-gray-dark">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Entrada #{{ $entrada->id }} —
                                    {{ $entrada->tipoproyecto->nombre ?? '' }}
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="text-muted">Fecha</label>
                                        <p><strong>{{ date('d/m/Y', strtotime($entrada->fecha)) }}</strong></p>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="text-muted">Factura</label>
                                        <p><strong>{{ $entrada->factura ?? '' }}</strong></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted">Descripción</label>
                                        <p><strong>{{ $entrada->descripcion ?? '' }}</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Botón agregar material --}}
        <section class="content-header">
            <div class="row">
                <button type="button" style="margin-left: 15px" onclick="abrirModal()" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Agregar Material
                </button>
                <a href="{{ route('admin.historial.entradas.index') }}"
                   style="margin-left: 10px"
                   class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </section>

        {{-- Modal buscar material --}}
        <div class="modal fade" id="modalRepuesto">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Agregar Material</h4>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formulario-repuesto">
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Material <span style="color:red">*</span></label>
                                    <table class="table" id="matriz-busqueda">
                                        <tbody>
                                        <tr>
                                            <td>
                                                <input id="repuesto" data-info="0" autocomplete="off"
                                                       class="form-control" style="width:100%"
                                                       onkeyup="buscarMaterial(this)" maxlength="400" type="text">
                                                <div class="droplista" style="position:absolute;z-index:9;width:75% !important;"></div>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="form-group">
                                    <label>Cantidad (2 decimales máximo) <span style="color:red">*</span></label>
                                    <div class="col-md-6">
                                        <input type="text" id="cantidad" inputmode="decimal" maxlength="10"
                                               class="form-control" autocomplete="off" placeholder="0.00"
                                               onkeydown="return validateInput(event);"
                                               oninput="validateCantidadInput(this);">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Marca</label>
                                    <div class="col-md-6">
                                        <input type="text" id="codigo" maxlength="100"
                                               class="form-control" autocomplete="off">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Precio <span style="color:red">*</span></label>
                                    <div class="col-md-3">
                                        <input type="number" min="0" max="1000000" autocomplete="off"
                                               class="form-control" id="precio-producto" placeholder="0.00">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" onclick="agregarFila()">Agregar</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabla de detalle a agregar --}}
        <section class="content-header">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h2>Materiales a Agregar</h2>
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
                            <th style="width:3%">#</th>
                            <th style="width:35%">Material</th>
                            <th style="width:10%">Cantidad</th>
                            <th style="width:12%">Marca</th>
                            <th style="width:10%">Precio</th>
                            <th style="width:8%">Opciones</th>
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
        const ID_ENTRADA = {{ $entrada->id }};
        window.seguroBuscador = true;
        window.txtContenedorGlobal = null;

        // ── Constantes de cantidad ────────────────────────────────────────
        var CANTIDAD_MAXIMA = 1000000;               // máximo permitido por material
        var REGLA_CANTIDAD  = /^\d+(\.\d{1,2})?$/;   // entero o hasta 2 decimales

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

        // Validar teclas (permite un solo punto decimal)
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

        // Limpia el input de cantidad y respeta el máximo de 1,000,000
        function validateCantidadInput(input) {
            input.value = sanitizarDecimal(input.value);
            if (Number(input.value) > CANTIDAD_MAXIMA) {
                input.value = String(CANTIDAD_MAXIMA);
            }
        }

        $(document).ready(function () {
            $(document).click(function () { $(".droplista").hide(); });
        });

        function abrirModal() {
            document.getElementById("formulario-repuesto").reset();
            $('#repuesto').attr('data-info', '0');
            $('#modalRepuesto').modal({ backdrop: 'static', keyboard: false });
        }

        function agregarFila() {
            var repuesto      = document.querySelector('#repuesto');
            var nomRepuesto   = repuesto.value;
            var cantidad      = document.getElementById('cantidad').value;
            var codigo        = document.getElementById('codigo').value;
            var precio        = document.getElementById('precio-producto').value;

            var reglaDecimal  = /^([0-9]+\.?[0-9]{0,10})$/;

            if (repuesto.dataset.info == 0) { toastr.error('Material es requerido'); return; }
            if (cantidad === '')            { toastr.error('Cantidad es requerida'); return; }
            if (!REGLA_CANTIDAD.test(cantidad)) { toastr.error('Cantidad debe ser número válido (máximo 2 decimales) y no negativo'); return; }
            if (redondear2(cantidad) <= 0)  { toastr.error('Cantidad debe ser mayor a 0'); return; }
            if (redondear2(cantidad) > CANTIDAD_MAXIMA) { toastr.error('Cantidad máximo 1 millón'); return; }
            if (precio === '')              { toastr.error('Precio es requerido'); return; }
            if (!precio.match(reglaDecimal)){ toastr.error('Precio inválido'); return; }
            if (precio < 0)                 { toastr.error('Precio no puede ser negativo'); return; }

            // Normalizar siempre a 2 decimales
            var cantidadFormato = redondear2(cantidad).toFixed(2);

            var nFilas = $('#matriz > tbody > tr').length + 1;

            var markup = `<tr>
                <td><p id="fila${nFilas}" class="form-control" style="max-width:65px">${nFilas}</p></td>
                <td><input name="descripcionArray[]" disabled data-info="${repuesto.dataset.info}" value="${nomRepuesto}" class="form-control" type="text"></td>
                <td><input name="cantidadArray[]" disabled data-cantidad="${cantidadFormato}" value="${cantidadFormato}" class="form-control" type="text"></td>
                <td><input name="codigoArray[]" disabled value="${codigo}" class="form-control" type="text"></td>
                <td><input name="arrayPrecio[]" data-precio="${precio}" disabled value="$${precio}" class="form-control" type="text"></td>
                <td><button type="button" class="btn btn-danger btn-block" onclick="borrarFila(this)">Borrar</button></td>
            </tr>`;

            $("#matriz tbody").append(markup);
            document.getElementById("formulario-repuesto").reset();
            $('#repuesto').attr('data-info', '0');
            $('#modalRepuesto').modal('hide');
            toastr.success('Material agregado');
        }

        function borrarFila(el) {
            el.closest('tr').remove();
            setearFila();
        }

        function setearFila() {
            var table = document.getElementById('matriz');
            var conteo = 0;
            for (var r = 1; r < table.rows.length; r++) {
                conteo++;
                var el = table.rows[r].cells[0].children[0];
                el.innerHTML = conteo;
            }
        }

        function buscarMaterial(e) {
            if (seguroBuscador) {
                seguroBuscador = false;
                var row = $(e).closest('tr');
                txtContenedorGlobal = e;
                let texto = e.value;
                if (texto === '') $(e).attr('data-info', 0);

                axios.post(urlAdmin + '/admin/buscar/material', { query: texto })
                    .then((response) => {
                        seguroBuscador = true;
                        $(row).find(".droplista").fadeIn().html(response.data);
                    })
                    .catch(() => { seguroBuscador = true; });
            }
        }

        function modificarValor(edrop) {
            let texto = $(edrop).text();
            $(txtContenedorGlobal).val(texto);
            $(txtContenedorGlobal).attr('data-info', edrop.id);
        }

        function preguntaGuardar() {
            var nFilas = $('#matriz > tbody > tr').length;
            if (nFilas === 0) {
                toastr.error('Agrega al menos un material');
                return;
            }

            Swal.fire({
                title: '¿Guardar materiales extras?',
                text: 'Se agregarán a la entrada #' + ID_ENTRADA,
                type: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Sí, guardar'
            }).then((result) => {
                if (result.value) {
                    guardarExtras();
                }
            });
        }

        function guardarExtras() {
            var descripcionAtributo = $("input[name='descripcionArray[]']").map(function () { return $(this).attr('data-info'); }).get();
            var cantidad            = $("input[name='cantidadArray[]']").map(function () { return $(this).attr('data-cantidad'); }).get();
            var codigo              = $("input[name='codigoArray[]']").map(function () { return $(this).val(); }).get();
            var arrayPrecio         = $("input[name='arrayPrecio[]']").map(function () { return $(this).attr('data-precio'); }).get();

            // Validación final antes de enviar
            for (var a = 0; a < cantidad.length; a++) {
                var c = cantidad[a];
                if (!c || !REGLA_CANTIDAD.test(c) || redondear2(c) <= 0 || redondear2(c) > CANTIDAD_MAXIMA) {
                    toastr.error('Fila #' + (a + 1) + ' Cantidad inválida (máximo 2 decimales, entre 0.01 y 1,000,000)');
                    return;
                }
            }

            const contenedorArray = [];
            for (var i = 0; i < cantidad.length; i++) {
                contenedorArray.push({
                    idMaterial:   descripcionAtributo[i],
                    infoCantidad: cantidad[i],
                    infoCodigo:   codigo[i],
                    infoPrecio:   arrayPrecio[i],
                });
            }

            openLoading();
            const formData = new FormData();
            formData.append('id_entrada',      ID_ENTRADA);
            formData.append('contenedorArray', JSON.stringify(contenedorArray));

            axios.post(urlAdmin + '/admin/historial/entradas/extras/guardar', formData)
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success(response.data.mensaje);
                    }
                    else if (response.data.success === 2) {
                        toastr.success('Materiales agregados correctamente');
                        $("#matriz tbody tr").remove();
                    } else {
                        toastr.error('Error al guardar');
                    }
                })
                .catch(() => {
                    closeLoading();
                    toastr.error('Error al guardar');
                });
        }
    </script>
@endsection
