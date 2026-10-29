@extends('layouts.publico')
@section('titulo', 'Postulación enviada')

@section('contenido')
<section class="post-enviada">
    <div class="contenedor post-contenedor">
        <div class="sello-ok" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></div>
        <h1>Postulación enviada</h1>
        <p>El resultado llegará por correo electrónico en un plazo máximo de 15 días hábiles.</p>
        <p class="texto-suave">Revise también su carpeta de correo no deseado. No necesita hacer nada más por ahora.</p>
        <x-boton tipo="secundario" :href="route('inicio')">Volver al inicio</x-boton>
    </div>
</section>
@endsection
