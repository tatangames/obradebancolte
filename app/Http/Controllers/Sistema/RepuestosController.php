<?php

namespace App\Http\Controllers\Sistema;

use App\Http\Controllers\Controller;
use App\Models\Entradas;
use App\Models\EntradasDetalle;
use App\Models\Herramientas;
use App\Models\HistoHerramientaDescartada;
use App\Models\HistorialEntradas;
use App\Models\HistorialEntradasDeta;
use App\Models\Materiales;
use App\Models\ObjetoEspecifico;
use App\Models\SalidasDetalle;
use App\Models\TipoProyecto;
use App\Models\UnidadMedida;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RepuestosController extends Controller
{

    public function index()
    {
        $lUnidad           = UnidadMedida::orderBy('nombre', 'ASC')->get();
        $lObjetoEspecifico = ObjetoEspecifico::with('cuenta')
            ->orderBy('nombre', 'ASC')->get();

        return view('backend.admin.inventario.vistainventario',
            compact('lUnidad', 'lObjetoEspecifico'));
    }

    public function tablaMateriales()
    {
        $filtro = request('filtro', 'todos'); // 'todos' | 'sin_objeto'

        $query = Materiales::with(['objetoEspecifico', 'unidadMedida'])
            ->orderBy('nombre', 'ASC');

        if ($filtro === 'sin_objeto') {
            $query->whereNull('id_objespecifico');
        }

        $lista = $query->get();

        // Entradas totales por material (1 sola consulta)
        $entradas = DB::table('entradas_detalle')
            ->select('id_material', DB::raw('SUM(cantidad_inicial) as total'))
            ->groupBy('id_material')
            ->pluck('total', 'id_material');

        // Salidas totales por material (1 sola consulta)
        $salidas = DB::table('salidas_detalle as sd')
            ->join('entradas_detalle as ed', 'ed.id', '=', 'sd.id_entrada_detalle')
            ->select('ed.id_material', DB::raw('SUM(sd.cantidad_salida) as total'))
            ->groupBy('ed.id_material')
            ->pluck('total', 'id_material');

        foreach ($lista as $item) {
            $ent = round((float) ($entradas[$item->id] ?? 0), 2);
            $sal = round((float) ($salidas[$item->id] ?? 0), 2);

            $item->medida   = optional($item->unidadMedida)->nombre ?? '';
            $item->entradas = $ent;
            $item->salidas  = $sal;
            $item->total    = round($ent - $sal, 2);

            // Relación ya cargada con with()
            $item->objeto_especifico = $item->objetoEspecifico;
        }

        return view('backend.admin.inventario.tablainventario', compact('lista'));
    }

    public function nuevoMaterial(Request $request)
    {
        $validar = Validator::make($request->all(), [
            'nombre'           => 'required',
            'id_objespecifico' => 'required|exists:objeto_especifico,id',
        ]);
        if ($validar->fails()) { return ['success' => 0]; }

        $dato = new Materiales();
        $dato->nombre           = $request->nombre;
        $dato->id_medida        = $request->unidad ?: null;
        $dato->id_objespecifico = $request->id_objespecifico;
        $dato->codigo           = $request->codigo;

        return $dato->save() ? ['success' => 1] : ['success' => 2];
    }

    public function informacionMaterial(Request $request)
    {
        $validar = Validator::make($request->all(), ['id' => 'required']);
        if ($validar->fails()) { return ['success' => 0]; }

        if ($lista = Materiales::where('id', $request->id)->first()) {
            $arrayUnidad         = UnidadMedida::orderBy('nombre', 'ASC')->get();
            $arrayObjetoEspecifico = ObjetoEspecifico::with('cuenta.rubro')
                ->orderBy('nombre', 'ASC')->get();

            return [
                'success'           => 1,
                'material'          => $lista,
                'unidad'            => $arrayUnidad,
                'objeto_especifico' => $arrayObjetoEspecifico,
            ];
        }

        return ['success' => 2];
    }

    public function editarMaterial(Request $request)
    {
        $validar = Validator::make($request->all(), [
            'id'               => 'required',
            'nombre'           => 'required',
            'id_objespecifico' => 'required|exists:objeto_especifico,id',
        ]);
        if ($validar->fails()) { return ['success' => 0]; }

        $material = Materiales::find($request->id);

        if (!$material) {
            return ['success' => 0];
        }

        $tieneEntradas = EntradasDetalle::where('id_material', $request->id)->exists();

        if ($tieneEntradas) {

            // Si ya tiene un Objeto Específico asignado, no se puede tocar nada.
            if (!is_null($material->id_objespecifico)) {
                return [
                    'success' => 3,
                    'msg'     => 'Este material ya tiene entradas registradas, no puede editarse.',
                ];
            }

            // Objeto Específico vacío: se permite asignarlo, pero nada más se actualiza.
            $material->update([
                'id_objespecifico' => $request->id_objespecifico,
            ]);

            return ['success' => 1];
        }

        // Sin entradas registradas: edición completa permitida.
        $material->update([
            'id_medida'        => $request->unidad ?: null,
            'id_objespecifico' => $request->id_objespecifico,
            'nombre'           => $request->nombre,
            'codigo'           => $request->codigo,
        ]);

        return ['success' => 1];
    }




    //*******************************************************************

    public function indexRegistroEntrada(){

        $arrayProyecto = TipoProyecto::where('transferido', 0)
            ->orderBy('nombre')
            ->get();

        return view('backend.admin.repuestos.registros.vistaentradaregistro', compact('arrayProyecto'));
    }


    public function buscadorMaterial(Request $request){

        if($request->get('query')){
            $query = $request->get('query');
            $data = Materiales::where('nombre', 'LIKE', "%{$query}%")
                ->get();

            foreach ($data as $dd){
                if($info = UnidadMedida::where('id', $dd->id_medida)->first()){
                    $dd->medida = "- " . $info->nombre;
                }else{
                    $dd->medida = "";
                }
            }

            $output = '<ul class="dropdown-menu" style="display:block; position:relative;">';
            $tiene = true;
            foreach($data as $row){

                // si solo hay 1 fila, No mostrara el hr, salto de linea
                if(count($data) == 1){
                    if(!empty($row)){
                        $tiene = false;
                        $output .= '
                 <li onclick="modificarValor(this)" id="'.$row->id.'"><a href="#" style="margin-left: 3px">'.$row->nombre . '  ' .$row->medida .'</a></li>
                ';
                    }
                }

                else{
                    if(!empty($row)){
                        $tiene = false;
                        $output .= '
                 <li onclick="modificarValor(this)" id="'.$row->id.'"><a href="#" style="margin-left: 3px">'.$row->nombre . ' ' .$row->medida .'</a></li>
                   <hr>
                ';
                    }
                }
            }
            $output .= '</ul>';
            if($tiene){
                $output = '';
            }
            echo $output;
        }
    }

    // GUARDAR ENTRADAS
    public function guardarEntrada(Request $request)
    {
        $rules = [
            'fecha'     => 'required',
            'tipoproyecto' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ['success' => 0];
        }

        // ── Validar que el proyecto no esté cerrado ──
        $proyecto = Tipoproyecto::find($request->tipoproyecto);
        if (!$proyecto || $proyecto->transferido == 1) {
            return ['success' => 2]; // proyecto cerrado o no existe
        }

        DB::beginTransaction();

        try {
            $datosContenedor = json_decode($request->contenedorArray, true);

            // ── Cabecera ──
            $registro = new Entradas();
            $registro->id_tipoproyecto = $request->tipoproyecto;
            $registro->fecha = Carbon::parse($request->fecha);
            $registro->descripcion = $request->descripcion;
            $registro->factura = $request->lote;
            $registro->es_transferencia = 0;
            $registro->id_tipoproyecto_transferencia = null;
            $registro->save();

            // ── Detalle ──
            foreach ($datosContenedor as $fila) {
                $detalle = new EntradasDetalle();
                $detalle->id_entradas        = $registro->id;
                $detalle->id_material        = $fila['idMaterial'];
                $detalle->cantidad_inicial   = $fila['infoCantidad'];
                $detalle->precio             = $fila['infoPrecio'];
                $detalle->codigo             = $fila['infoCodigo'];
                $detalle->save();
            }

            DB::commit();
            return ['success' => 1];

        } catch (\Throwable $e) {
            Log::error('guardarEntrada: ' . $e);
            DB::rollback();
            return ['success' => 99];
        }
    }


    public function proyectosPorMaterial(Request $request)
    {
        $idMaterial = $request->id;

        // IDs de entradas_detalle de este material
        $idsEntradasDetalle = EntradasDetalle::where('id_material', $idMaterial)
            ->pluck('id');

        // Entradas por proyecto
        $entradas = EntradasDetalle::where('id_material', $idMaterial)
            ->with('entrada.tipoproyecto')
            ->get()
            ->filter(fn($item) => $item->entrada) // evita error si falta la relación
            ->groupBy(fn($item) => $item->entrada->id_tipoproyecto)
            ->map(function ($grupo) {

                // Total de unidades que entraron al proyecto (real + transferencia)
                $entradasTotal = round((float) $grupo->sum('cantidad_inicial'), 2);

                // Solo las que son ingreso REAL (no transferencia)
                $entradasReales = round((float) $grupo
                    ->filter(fn($item) => ! $item->entrada->es_transferencia)
                    ->sum('cantidad_inicial'), 2);

                return [
                    'proyecto'        => $grupo->first()->entrada->tipoproyecto->nombre ?? '—',
                    'entradas'        => $entradasTotal,
                    'entradas_reales' => $entradasReales,
                ];
            });

        // Salidas por proyecto
        $salidas = SalidasDetalle::whereIn('id_entrada_detalle', $idsEntradasDetalle)
            ->with('salida')
            ->get()
            ->filter(fn($item) => $item->salida) // evita error si falta la relación
            ->groupBy(fn($item) => $item->salida->id_tipoproyecto)
            ->map(fn($grupo) => round((float) $grupo->sum('cantidad_salida'), 2));

        // Combinar (TODOS los proyectos, sin filtrar todavía)
        $todos = $entradas->map(function ($dato, $idProyecto) use ($salidas) {
            $sal = (float) ($salidas[$idProyecto] ?? 0);
            return [
                'proyecto'        => $dato['proyecto'],
                'entradas'        => $dato['entradas'],
                'entradas_reales' => $dato['entradas_reales'],
                'salidas'         => round($sal, 2),
                'disponible'      => round($dato['entradas'] - $sal, 2),
            ];
        })->values();

        // ── Totales (se calculan ANTES de filtrar) ────────────────
        // El total general usa SOLO las entradas reales: las transferencias
        // son el mismo material moviéndose entre proyectos.
        $totalEntradas = round((float) $todos->sum('entradas_reales'), 2);
        $totalSalidas  = round((float) $todos->sum('salidas'), 2);

        // Salidas que NO son transferencia (consumo real)
        $salidasReales = round((float) SalidasDetalle::whereIn('id_entrada_detalle', $idsEntradasDetalle)
            ->whereHas('salida', fn($q) => $q->where('es_transferencia', 0))
            ->sum('cantidad_salida'), 2);

        $totalDisponible = round($totalEntradas - $salidasReales, 2);

        // ── Listado: solo proyectos con disponible ────────────────
        $proyectos = $todos
            ->filter(fn($p) => $p['disponible'] != 0)
            ->values();

        return response()->json([
            'success'   => 1,
            'proyectos' => $proyectos,
            'totales'   => [
                'entradas'   => $totalEntradas,
                'salidas'    => $totalSalidas,
                'disponible' => $totalDisponible,
            ],
        ]);
    }




    //*******************************************

    public function vistaDetalleMaterial($id){

        $infomaterial = Materiales::where('id', $id)->first();
        $medida = '';
        if($infoMedida = UnidadMedida::where('id', $infomaterial->id_medida)->first()){
            $medida = $infoMedida->nombre;
        }

        return view('backend.admin.inventario.detalle.vistadetalle', compact('id', 'infomaterial', 'medida'));
    }


    public function tablaDetalleMaterial($id){

        // SOLO HABRA 1 MATERIAL POR CADA PROYECTO
        $arrayEntradas =  Entradas::where('id_material', $id)->get();

        $pilaArrayEntrada = array();


        foreach ($arrayEntradas as $data){

            // VERIFICAR QUE LA CANTIDAD SEA MAYOR A 0 PARA PODER
            // MOSTRARLO
            if($data->cantidad > 0){
                array_push($pilaArrayEntrada, $data->id);
            }
        }

        $lista = Entradas::whereIn('id', $pilaArrayEntrada)
            ->orderBy('id_tipoproyecto', 'ASC')
            ->get();

        foreach ($lista as $info){
            // OBTENER NOMBRE DE PROYECTO

            $infoProyecto = TipoProyecto::where('id', $info->id_tipoproyecto)->first();
            $info->nombrepro = $infoProyecto->nombre;
        }

        return view('backend.admin.inventario.detalle.tabladetallematerial', compact('lista'));
    }



}
