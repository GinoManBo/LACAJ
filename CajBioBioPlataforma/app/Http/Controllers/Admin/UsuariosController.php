<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consultorio;
use App\Models\Region;
use App\Models\Usuario;
use App\Services\UsuariosAdmin;
use App\Support\Rut;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuariosController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate(['rol' => ['nullable', Rule::in(Usuario::ROLES_GESTIONABLES)], 'estado' => ['nullable', 'in:activo,inactivo'], 'q' => ['nullable', 'string', 'max:100']]);

        $usuarios = Usuario::with(['region', 'abogado.consultorios', 'abogado.region'])->whereIn('rol', Usuario::ROLES_GESTIONABLES)
            ->when($f['rol'] ?? null, fn ($q, $r) => $q->where('rol', $r))
            ->when($f['estado'] ?? null, fn ($q, $e) => $q->where('activo', $e === 'activo'))
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('nombre', 'like', "%{$t}%")->orWhere('apellido', 'like', "%{$t}%")->orWhere('email', 'like', "%{$t}%")))
            ->orderBy('apellido')->orderBy('nombre')->paginate(25)->withQueryString();

        return view('admin.usuarios.index', ['usuarios' => $usuarios, 'filtros' => $f, 'roles' => Usuario::ROLES_GESTIONABLES]);
    }

    public function crear()
    {
        return view('admin.usuarios.form', $this->datosForm(new Usuario(['rol' => 'secretaria'])));
    }

    public function guardar(Request $request)
    {
        $d = $request->validate($this->reglas(null), $this->mensajes());
        $r = UsuariosAdmin::crear($d, $request->user()->id);

        return redirect()->route('admin.usuarios.index')
            ->with('ok', "Usuario {$r['usuario']->email} creado.")
            ->with('password_temporal', ['email' => $r['usuario']->email, 'password' => $r['passwordTemporal']]);
    }

    public function editar(Usuario $usuario)
    {
        abort_unless(in_array($usuario->rol, Usuario::ROLES_GESTIONABLES, true), 404);

        return view('admin.usuarios.form', $this->datosForm($usuario->load('abogado.consultorios')));
    }

    public function actualizar(Request $request, Usuario $usuario)
    {
        abort_unless(in_array($usuario->rol, Usuario::ROLES_GESTIONABLES, true), 404);
        $d = $request->validate($this->reglas($usuario), $this->mensajes());
        UsuariosAdmin::editar($usuario, $d, $request->user()->id);

        return redirect()->route('admin.usuarios.index')->with('ok', 'Cambios guardados correctamente.');
    }

    public function activar(Request $request, Usuario $usuario)
    {
        UsuariosAdmin::cambiarActivo($usuario, true, $request->user()->id);

        return back()->with('ok', 'Usuario activado.');
    }

    public function desactivar(Request $request, Usuario $usuario)
    {
        UsuariosAdmin::cambiarActivo($usuario, false, $request->user()->id);

        return back()->with('ok', 'Usuario desactivado. Su historial se conserva.');
    }

    public function resetearPassword(Request $request, Usuario $usuario)
    {
        $temporal = UsuariosAdmin::resetearPassword($usuario, $request->user()->id);

        return back()->with('ok', 'Contraseña restablecida.')
            ->with('password_temporal', ['email' => $usuario->email, 'password' => $temporal]);
    }

    private function datosForm(Usuario $u): array
    {
        return [
            'usuario' => $u, 'roles' => Usuario::ROLES_GESTIONABLES,
            'regiones' => Region::operativas()->orderBy('orden')->get(['id', 'nombre']),
            'consultorios' => Consultorio::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
        ];
    }

    private function reglas(?Usuario $u): array
    {
        return [
            'nombre' => ['required', 'string', 'max:80'],
            'apellido' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:160', Rule::unique('usuarios', 'email')->ignore($u?->id)],
            'rut' => ['required', 'string', fn ($a, $v, $fail) => Rut::valido($v) ?: $fail('El RUT no es válido (revise el dígito verificador).'),
                fn ($a, $v, $fail) => Usuario::where('rut', Rut::normalizar($v))->where('id', '!=', $u?->id ?? 0)->exists() ? $fail('Ya existe un usuario con ese RUT.') : null],
            'rol' => ['required', Rule::in(Usuario::ROLES_GESTIONABLES)],
            'region_id' => [
                'nullable',
                Rule::requiredIf(fn () => in_array(request('rol'), ['secretaria', 'director_regional', 'jefe_ucoe'], true) || (request('rol') === 'abogado' && request()->boolean('es_segunda_instancia'))),
                Rule::exists('regiones', 'id')->where('operativa', true),
            ],
            'es_segunda_instancia' => ['nullable', 'boolean'],
            'area' => ['nullable', 'string', 'max:50'],
            'consultorios' => ['nullable', 'array'],
            'consultorios.*' => ['integer', 'exists:consultorios,id'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
            'region_id.required' => 'La región es obligatoria para este cargo.',
            'region_id.required_if' => 'La región es obligatoria para este cargo.',
            'email' => 'Ingrese un correo válido.', 'email.unique' => 'Ya existe un usuario con ese correo.',
            'max' => 'Es demasiado largo.', 'in' => 'Seleccione una opción válida.', 'exists' => 'Seleccione una opción válida.',
        ];
    }
}
