@extends('layouts.portal', ['migas' => [['Administración']]])
@section('titulo', 'Panel de Control — Administración')
@section('contenido')
@include('admin._nav')

<div class="sec-cabecera" style="margin-top:1rem;">
    <div>
        <h1 class="titulo-pagina">Panel de Control de Administración</h1>
        <p class="texto-suave" style="margin:.25rem 0 0">
            Soporte técnico central, gobernanza territorial y control macroinstitucional de la CAJ Biobío.
        </p>
    </div>
</div>

<div class="rejilla-indicadores">
    <x-indicador :valor="$indicadores['practicas_activas']" etiqueta="Prácticas en Curso" href="{{ route('admin.practicantes.index') }}" />
    <x-indicador :valor="$indicadores['postulaciones_pendientes']" etiqueta="Postulaciones Pendientes" detalle="Esperan respuesta de secretarías" :critico="$indicadores['postulaciones_pendientes'] > 0" />
    <x-indicador :valor="$indicadores['ocupacion_pct'].'%'" etiqueta="Ocupación Global de Cupos" href="{{ route('admin.estadisticas.index') }}" />
    <x-indicador :valor="$indicadores['practicantes_registrados']" etiqueta="Postulantes Registrados" href="{{ route('admin.practicantes.index') }}" />
</div>

<div class="rejilla-dos">
    <x-tarjeta titulo="Información del Sistema y Entorno">
        <dl class="lista-datos">
            <div>
                <dt>Versión del software</dt>
                <dd>
                    <span class="badge" style="background:#dbeafe; color:#1e40af; font-weight:700;">
                        v{{ $sistema['version'] }}
                    </span>
                </dd>
            </div>
            <div><dt>Usuarios del sistema</dt><dd>{{ $sistema['usuarios'] }} <span class="texto-suave">({{ $sistema['usuarios_activos'] }} activos)</span></dd></div>
            <div><dt>Consultorios y sedes</dt><dd>{{ $sistema['consultorios'] }} unidades en 6 regiones</dd></div>
            <div><dt>Capacidad global de cupos</dt><dd><strong>{{ $sistema['cupos_totales'] }}</strong> vacantes autorizadas</dd></div>
            <div><dt>Políticas de seguridad</dt><dd><span style="color:#15803d; font-weight:600;">Log Inmutable &bull; Desactivación Lógica (Ley 19.628)</span></dd></div>
        </dl>
    </x-tarjeta>

    <x-tarjeta titulo="Módulos de Gestión Central">
        <div class="accesos">
            <a href="{{ route('admin.practicantes.index') }}" class="acceso">
                <strong>Gestión de postulantes y prácticas</strong>
                <span>Subsanación técnica de errores, corrección de datos personales, traslados y cancelaciones.</span>
            </a>
            <a href="{{ route('admin.consultorios.index') }}" class="acceso">
                <strong>Consultorios y gestión de cupos</strong>
                <span>Modificación centralizada de vacantes por sede con candado de seguridad para cupos activos.</span>
            </a>
            <a href="{{ route('admin.usuarios.index') }}" class="acceso">
                <strong>Usuarios y gobernanza territorial</strong>
                <span>Creación de cuentas, asignación de secretarías por región y desactivación lógica de personal.</span>
            </a>
            <a href="{{ route('admin.estadisticas.index') }}" class="acceso">
                <strong>Métricas globales y reportes</strong>
                <span>Tasas de ocupación por región, tiempos promedio de asignación y causales anuales de cancelación.</span>
            </a>
            <a href="{{ route('admin.auditoria.index') }}" class="acceso">
                <strong>Log inmutable de auditoría</strong>
                <span>Visor y exportación del historial inalterable de operaciones, eventos, IP y payloads JSON.</span>
            </a>
        </div>
    </x-tarjeta>
</div>
@endsection
