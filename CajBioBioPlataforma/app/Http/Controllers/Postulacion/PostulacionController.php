<?php

namespace App\Http\Controllers\Postulacion;

use App\Http\Controllers\Controller;
use App\Models\ArchivoStaging;
use App\Models\Comuna;
use App\Models\Consultorio;
use App\Models\Documento;
use App\Models\Especialidad;
use App\Models\Postulacion;
use App\Models\PostulacionBorrador;
use App\Models\Postulante;
use App\Models\Region;
use App\Models\Usuario;
use App\Services\Almacenamiento;
use App\Services\Auditoria;
use App\Services\Notificaciones;
use App\Support\Calendario;
use App\Support\Rut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PostulacionController extends Controller
{
    public const PASOS = [1 => 'Carta y términos', 2 => 'Datos personales', 3 => 'Antecedentes educativos', 4 => 'Consultorio y fecha', 5 => 'Resumen'];

    public const TIPOS_OBLIGATORIOS = ['cedula_frontal', 'cedula_reverso', 'certificado_egreso'];

    public const TIPOS_OPCIONALES = [];

    private const CLAVE = 'postulacion';

    public function inicio()
    {
        return view('postulacion.inicio');
    }

    public function paso(Request $request, int $n)
    {
        abort_unless(isset(self::PASOS[$n]), 404);

        $permitido = $this->primerPasoIncompleto($request);
        if ($n > $permitido) {
            return redirect()->route('postulacion.paso', $permitido)
                ->with('aviso', 'Complete este paso antes de continuar.');
        }

        $b = $this->borrador($request);
        $datos = ['n' => $n, 'pasos' => self::PASOS, 'borrador' => $b, 'permitido' => $permitido];

        $datos += match ($n) {
            2 => [
                'regiones' => Region::operativas()->orderBy('orden')->get(['id', 'nombre']),
                'comunas' => Comuna::whereHas('region', fn ($q) => $q->operativas())->orderBy('nombre')->get(['id', 'region_id', 'nombre']),
                'generos' => ['femenino' => 'Femenino', 'masculino' => 'Masculino', 'otro' => 'Otro', 'prefiere_no_decir' => 'Prefiero no decirlo'],
            ],
            3 => [
                'especialidades' => Especialidad::where('activo', true)->orderBy('orden')->get(['id', 'nombre']),
                'archivos' => ArchivoStaging::whereIn('id', array_values($b['archivos'] ?? []))->get()->keyBy('tipo'),
                'tiposObligatorios' => self::TIPOS_OBLIGATORIOS,
                'tiposOpcionales' => self::TIPOS_OPCIONALES,
            ],
            4 => $this->datosPasoConsultorios($b),
            5 => $this->datosResumen($b),
            default => [],
        };

        return view("postulacion.paso{$n}", $datos);
    }

    public function guardar(Request $request, int $n)
    {
        abort_unless($n >= 1 && $n <= 4, 404);
        if ($n > $this->primerPasoIncompleto($request)) {
            return redirect()->route('postulacion.paso', $this->primerPasoIncompleto($request));
        }

        $b = $this->borrador($request);

        switch ($n) {
            case 1:
                $request->validate(['acepta' => ['accepted']], ['acepta.accepted' => 'Debe aceptar los términos y condiciones para continuar.']);
                $b['acepta'] = true;
                break;
            case 2:
                $entrada = $request->all();
                if ($this->esPostulante($request)) {
                    $entrada['rut'] = Rut::formatear($request->user()->rut);
                }
                $b['datos'] = $this->validar($entrada, $this->reglasPaso2($entrada), $this->mensajes());
                break;
            case 3:
                $b['educ'] = $this->validar($request->all(), $this->reglasPaso3(), $this->mensajes());
                $b['archivos'] = $this->procesarArchivos($request, $b['archivos'] ?? []);
                break;
            case 4:
                $b['consultorios'] = $this->validar($request->all(), $this->reglasPaso4(), $this->mensajes())['consultorios'];
                $b['fecha_inicio'] = $request->input('fecha_inicio');
                break;
        }

        $this->guardarBorrador($request, $b);

        return redirect()->route('postulacion.paso', $n + 1);
    }

    public function enviar(Request $request)
    {
        $b = $this->borrador($request);
        $pendiente = $this->primerPasoIncompleto($request);
        if ($pendiente < 5) {
            return redirect()->route('postulacion.paso', $pendiente)->with('aviso', 'Hay información obligatoria sin completar.');
        }

        $d = $b['datos'] + $b['educ'];
        $rut = $this->esPostulante($request) ? Rut::normalizar($request->user()->rut) : Rut::normalizar($d['rut']);
        $yaPostulo = Postulacion::whereHas('postulante', fn ($q) => $q->where('rut', $rut))
            ->whereIn('estado', ['pendiente', 'derivada'])->exists();
        if ($yaPostulo) {
            return redirect()->route('postulacion.paso', 2)->withErrors(['rut' => 'Ya existe una postulación en revisión para este RUT.']);
        }

        $regionId = Consultorio::findOrFail($b['consultorios'][0])->region_id;

        $postulacion = DB::transaction(function () use ($b, $d, $rut, $regionId) {
            $postulante = Postulante::updateOrCreate(['rut' => $rut], [
                'nombre' => $d['nombre'], 'apellido' => $d['apellido'],
                'fecha_nacimiento' => $d['fecha_nacimiento'], 'email' => mb_strtolower($d['email']),
                'telefono' => $d['telefono'], 'genero' => $d['genero'], 'nacionalidad' => $d['nacionalidad'],
                'discapacidad' => (bool) $d['discapacidad'],
                'discapacidad_descripcion' => $d['discapacidad'] ? ($d['discapacidad_descripcion'] ?? null) : null,
                'region_id' => $d['region_id'], 'comuna_id' => $d['comuna_id'], 'direccion' => $d['direccion'],
                'universidad' => $d['universidad'], 'anio_carrera' => 'egresado',
                'especialidad_id' => $d['especialidad_id'], 'antecedentes_salud' => $d['antecedentes_salud'],
                'emergencia_nombre' => $d['emergencia_nombre'], 'emergencia_vinculo' => $d['emergencia_vinculo'],
                'emergencia_telefono' => $d['emergencia_telefono'], 'emergencia_email' => $d['emergencia_email'] ?? null,
            ]);

            $postulacion = Postulacion::create([
                'postulante_id' => $postulante->id, 'estado' => 'pendiente', 'region_id' => $regionId,
                'fecha_postulacion' => now(), 'fecha_inicio_solicitada' => $b['fecha_inicio'],
            ]);

            foreach ($b['consultorios'] as $i => $consultorioId) {
                $postulacion->preferencias()->create(['consultorio_id' => $consultorioId, 'orden' => $i + 1]);
            }

            foreach (ArchivoStaging::whereIn('id', array_values($b['archivos']))->whereNull('consumed_at')->get() as $a) {
                Documento::create([
                    'tipo' => $a->tipo, 'postulacion_id' => $postulacion->id,
                    'nombre_original' => $a->nombre_original, 'nombre_almacenado' => $a->nombre_almacenado,
                    'mime' => $a->mime, 'tamano_bytes' => $a->tamano_bytes, 'sha256' => $a->sha256,
                ]);
                $a->update(['consumed_at' => now()]);
            }

            Auditoria::registrar('postulacion.crear', 'postulacion', $postulacion->id, ['rut' => $rut], null);

            Usuario::where('rol', 'secretaria')->where('region_id', $regionId)->where('activo', true)->each(
                fn ($s) => Notificaciones::crear($s->id, 'aviso', 'Nueva postulación', "{$d['nombre']} {$d['apellido']} envió una postulación.", 'postulacion', $postulacion->id)
            );

            return $postulacion;
        });

        $this->descartarBorrador($request);
        $request->session()->flash('postulacion_enviada', $postulacion->id);

        return redirect()->route('postulacion.enviada');
    }

    public function enviada(Request $request)
    {
        abort_unless($request->session()->has('postulacion_enviada'), 404);

        return view('postulacion.enviada');
    }

    public const CAMPOS_EDITABLES = [
        'email', 'telefono', 'genero', 'nacionalidad', 'discapacidad', 'discapacidad_descripcion', 'region_id', 'comuna_id', 'direccion',
        'emergencia_nombre', 'emergencia_vinculo', 'emergencia_telefono', 'emergencia_email', 'antecedentes_salud',
        'universidad', 'especialidad_id',
    ];

    public function validarEdicion(array $input): array
    {
        $reglas = array_intersect_key($this->reglasPaso2($input) + $this->reglasPaso3(), array_flip(self::CAMPOS_EDITABLES));

        return $this->validar($input, $reglas, $this->mensajes());
    }

    private function reglasPaso2(array $input): array
    {
        return [
            'nombre' => ['required', 'string', 'max:80'],
            'apellido' => ['required', 'string', 'max:80'],
            'rut' => ['required', 'string', fn ($a, $v, $fail) => Rut::valido($v) ?: $fail('El RUT no es válido (revise el dígito verificador).')],
            'fecha_nacimiento' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'email' => ['required', 'email', 'max:160'],
            'telefono' => ['required', 'regex:/^\+?[0-9 ()\-]{8,20}$/'],
            'genero' => ['required', Rule::in(['femenino', 'masculino', 'otro', 'prefiere_no_decir'])],
            'nacionalidad' => ['required', 'string', 'max:60'],
            'discapacidad' => ['required', 'boolean'],
            'discapacidad_descripcion' => ['nullable', 'required_if:discapacidad,1', 'string', 'max:2000'],
            'region_id' => ['required', Rule::exists('regiones', 'id')->where('operativa', true)],
            'comuna_id' => ['required', Rule::exists('comunas', 'id')->where('region_id', $input['region_id'] ?? null)],
            'direccion' => ['required', 'string', 'max:200'],
            'emergencia_nombre' => ['required', 'string', 'max:140'],
            'emergencia_vinculo' => ['required', 'string', 'max:60'],
            'emergencia_telefono' => ['required', 'regex:/^\+?[0-9 ()\-]{8,20}$/'],
            'emergencia_email' => ['nullable', 'email', 'max:160'],
            'antecedentes_salud' => ['required', 'string', 'max:2000'],
        ];
    }

    private function reglasPaso3(): array
    {
        return [
            'universidad' => ['required', 'string', 'max:160'],
            'especialidad_id' => ['required', Rule::exists('especialidades', 'id')->where('activo', true)],
        ];
    }

    private function reglasPaso4(): array
    {
        return [
            'consultorios' => ['required', 'array', 'min:1'],
            'consultorios.*' => ['integer', 'distinct', Rule::exists('consultorios', 'id')->where('activo', true)->where('acepta_postulaciones', true)],
            'fecha_inicio' => ['required', 'date', 'after_or_equal:today', fn ($a, $v, $fail) => Calendario::esHabil($v) ?: $fail('La fecha no puede ser fin de semana ni feriado legal.')],
        ];
    }

    private function mensajes(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
            'required_if' => 'Este campo es obligatorio.',
            'email' => 'Ingrese un correo válido.',
            'regex' => 'Ingrese un teléfono válido.',
            'max' => 'Es demasiado largo.',
            'in' => 'Seleccione una opción válida.',
            'exists' => 'Seleccione una opción válida.',
            'before' => 'La fecha debe ser anterior a hoy.',
            'after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'consultorios.required' => 'Seleccione al menos un consultorio.',
            'consultorios.min' => 'Seleccione al menos un consultorio.',
        ];
    }

    private function validar(array $input, array $reglas, array $mensajes): array
    {
        return Validator::make($input, $reglas, $mensajes)->validate();
    }

    /** @return array<string,string> tipo => id de ArchivoStaging */
    private function procesarArchivos(Request $request, array $actuales): array
    {
        $reglaCedula = ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
        $reglaDoc = ['file', 'mimes:pdf,jpg,jpeg,png', 'max:'.(config('caj.max_upload_mb', 8) * 1024)];
        $reglas = [];
        foreach (self::TIPOS_OBLIGATORIOS as $t) {
            $reglaTipo = in_array($t, ['cedula_frontal', 'cedula_reverso'], true) ? $reglaCedula : $reglaDoc;
            $reglas[$t] = [isset($actuales[$t]) ? 'nullable' : 'required', ...$reglaTipo];
        }
        foreach (self::TIPOS_OPCIONALES as $t) {
            $reglas[$t] = ['nullable', ...$reglaDoc];
        }
        $request->validate($reglas, [
            'required' => 'Debe adjuntar este archivo.',
            'image' => 'El archivo debe ser un documento PDF o imagen JPG/PNG.',
            'mimes' => 'Formato no permitido (se acepta PDF, JPG o PNG).',
            'max' => 'El archivo supera el tamaño máximo.',
        ]);

        foreach (array_merge(self::TIPOS_OBLIGATORIOS, self::TIPOS_OPCIONALES) as $tipo) {
            if (! $request->hasFile($tipo)) {
                continue;
            }
            if (isset($actuales[$tipo])) {
                $viejo = ArchivoStaging::find($actuales[$tipo]);
                if ($viejo && ! $viejo->consumed_at) {
                    Almacenamiento::eliminar($viejo->nombre_almacenado);
                    $viejo->delete();
                }
            }
            $g = Almacenamiento::guardar($request->file($tipo));
            $staging = ArchivoStaging::create([
                'id' => (string) Str::uuid(), 'tipo' => $tipo, 'expires_at' => $this->esPostulante($request) ? now()->addDays(30) : now()->addDay(),
            ] + $g);
            $actuales[$tipo] = $staging->id;
        }

        return $actuales;
    }

    private function esPostulante(Request $request): bool
    {
        return $request->user()?->rol === 'postulante';
    }

    private function borrador(Request $request): array
    {
        if ($this->esPostulante($request)) {
            $u = $request->user();

            return PostulacionBorrador::where('usuario_id', $u->id)->first()?->datos
                ?? ['datos' => array_filter(['rut' => Rut::formatear($u->rut), 'nombre' => $u->nombre, 'apellido' => $u->apellido])];
        }

        return $request->session()->get(self::CLAVE, []);
    }

    private function guardarBorrador(Request $request, array $b): void
    {
        if ($this->esPostulante($request)) {
            PostulacionBorrador::updateOrCreate(['usuario_id' => $request->user()->id], ['datos' => $b]);

            return;
        }
        $request->session()->put(self::CLAVE, $b);
    }

    private function descartarBorrador(Request $request): void
    {
        $request->session()->forget(self::CLAVE);
        if ($this->esPostulante($request)) {
            PostulacionBorrador::where('usuario_id', $request->user()->id)->delete();
        }
    }

    private function primerPasoIncompleto(Request $request): int
    {
        return $this->pasoIncompleto($this->borrador($request));
    }

    public function pasoIncompleto(array $b): int
    {
        if (empty($b['acepta'])) {
            return 1;
        }
        if (empty($b['datos']) || Validator::make($b['datos'], $this->reglasPaso2($b['datos']))->fails()) {
            return 2;
        }
        $archivosOk = collect(self::TIPOS_OBLIGATORIOS)->every(
            fn ($t) => isset($b['archivos'][$t]) && ArchivoStaging::whereKey($b['archivos'][$t])->whereNull('consumed_at')->exists()
        );
        if (empty($b['educ']) || Validator::make($b['educ'], $this->reglasPaso3())->fails() || ! $archivosOk) {
            return 3;
        }
        if (empty($b['consultorios']) || empty($b['fecha_inicio'])
            || Validator::make(['consultorios' => $b['consultorios'], 'fecha_inicio' => $b['fecha_inicio']], $this->reglasPaso4())->fails()) {
            return 4;
        }

        return 5;
    }

    private function datosPasoConsultorios(array $b): array
    {
        $comuna = isset($b['datos']['comuna_id']) ? Comuna::find($b['datos']['comuna_id']) : null;
        $consultorios = Consultorio::with('comuna')->where('activo', true)->where('acepta_postulaciones', true)->orderBy('nombre')->get()
            ->each(function ($c) use ($comuna) {
                $c->distancia = ($comuna && $comuna->lat && $c->comuna->lat)
                    ? hypot($comuna->lat - $c->comuna->lat, $comuna->lng - $c->comuna->lng) : null;
            });
        $ordenados = $consultorios->sortBy(fn ($c) => $c->distancia ?? INF)->values();
        $cercano = $ordenados->first()?->distancia !== null ? $ordenados->first()->id : null;

        return [
            'consultorios' => $ordenados, 'masCercanoId' => $cercano,
            'seleccionados' => $b['consultorios'] ?? [],
            'feriados' => config('feriados'), 'hoy' => now()->toDateString(),
        ];
    }

    private function datosResumen(array $b): array
    {
        $d = $b['datos'] ?? [];

        return [
            'datos' => $d, 'educ' => $b['educ'] ?? [],
            'region' => Region::find($d['region_id'] ?? 0), 'comuna' => Comuna::find($d['comuna_id'] ?? 0),
            'especialidad' => Especialidad::find($b['educ']['especialidad_id'] ?? 0),
            'consultoriosElegidos' => collect($b['consultorios'] ?? [])->map(fn ($id) => Consultorio::find($id))->filter(),
            'fechaInicio' => $b['fecha_inicio'] ?? null,
            'archivos' => ArchivoStaging::whereIn('id', array_values($b['archivos'] ?? []))->get()->keyBy('tipo'),
        ];
    }
}
