@extends('layouts.publico')
@section('titulo', 'Carta y términos')

@section('contenido')
<x-postulacion.marco :n="$n" :pasos="$pasos" :permitido="$permitido" titulo="Carta de presentación y términos" bajada="Lea las condiciones del programa antes de continuar.">
    <form method="POST" action="{{ route('postulacion.guardar', 1) }}" data-paso-terminos novalidate>
        @csrf
        <article class="carta" lang="es" tabindex="0" aria-label="Carta de presentación y términos de postulación">
            <p class="carta-saludo">Estimado/a Postulante:</p>
            <p>La <strong>Corporación de Asistencia Judicial de la Región del Biobío</strong>, en cumplimiento de su misión institucional de facilitar el acceso a la justicia a toda la ciudadanía, pone a disposición de los estudiantes de Derecho el presente Programa de Práctica Profesional, el cual constituye una oportunidad de formación de excelencia en el ejercicio del Derecho.</p>
            <p>Este programa tiene como propósito contribuir a la formación práctica de futuros abogados, permitiéndoles aplicar los conocimientos adquiridos durante su formación académica en situaciones reales y de alto impacto social. Los practicantes colaborarán directamente con abogados tutores de esta Corporación en la atención de usuarios, elaboración de escritos jurídicos y seguimiento de causas.</p>
            <h2 class="carta-subtitulo">Términos y condiciones de participación</h2>
            <ol class="carta-terminos">
                <li><strong>Requisitos de postulación.</strong> Ser estudiante regular de la carrera de Derecho en alguna universidad reconocida por el Estado de Chile, haber cursado al menos el cuarto año de la carrera, contar con certificado de alumno regular vigente y no tener reprobaciones pendientes en ramos obligatorios del plan de estudios.</li>
                <li><strong>Duración y modalidad.</strong> La práctica profesional tiene una duración mínima de seis meses y máxima de doce meses, en modalidad presencial en las dependencias de la Corporación. El horario será de lunes a viernes, con jornada completa o parcial según se acuerde con el abogado tutor asignado.</li>
                <li><strong>Compromisos del practicante.</strong> El practicante se compromete a mantener absoluta confidencialidad respecto de los casos atendidos, asistir con puntualidad y regularidad, respetar el reglamento interno de la institución, actuar conforme a los principios éticos de la profesión jurídica y participar activamente en las jornadas de capacitación que la Corporación organice.</li>
                <li><strong>Tratamiento de datos personales.</strong> Los datos personales proporcionados en este formulario serán tratados con estricta confidencialidad, conforme a la Ley N° 19.628 sobre Protección de la Vida Privada. Serán utilizados exclusivamente para los fines del proceso de selección y gestión de la práctica. No serán cedidos a terceros sin su consentimiento expreso.</li>
                <li><strong>Proceso de selección.</strong> La Corporación se reserva el derecho de aceptar o rechazar postulaciones según sus necesidades institucionales. La notificación del resultado se realizará vía correo electrónico en un plazo máximo de 15 días hábiles.</li>
                <li><strong>Beneficios.</strong> Abogado tutor designado, acceso a recursos institucionales, certificado de práctica al término del período y posibilidad de vinculación continua según desempeño.</li>
            </ol>
            <p class="carta-fecha"><em>Concepción, {{ now()->translatedFormat('j \d\e F \d\e Y') }}</em></p>
        </article>

        @php $errAcepta = $errors->first('acepta'); @endphp
        <div class="campo {{ $errAcepta ? 'tiene-error' : '' }}" style="margin-top:1.25rem">
            <label class="casilla" for="campo-acepta">
                <input type="checkbox" id="campo-acepta" name="acepta" value="1" class="campo-control casilla-control" required data-mensaje="Debe aceptar los términos y condiciones para continuar." aria-describedby="campo-acepta-error" @checked(old('acepta', $borrador['acepta'] ?? false)) @if ($errAcepta) aria-invalid="true" @endif>
                <span>He leído y acepto los términos y condiciones del programa de práctica profesional de la Corporación de Asistencia Judicial del Biobío.<span class="campo-obligatorio" aria-hidden="true">*</span></span>
            </label>
            <p id="campo-acepta-error" class="campo-error" role="alert">{{ $errAcepta }}</p>
        </div>

        <div class="acciones-paso">
            <x-boton tipo="fantasma" :href="route('postulacion.inicio')">Volver al inicio</x-boton>
            <x-boton type="submit" data-continuar>Continuar</x-boton>
        </div>
    </form>
</x-postulacion.marco>
@endsection
