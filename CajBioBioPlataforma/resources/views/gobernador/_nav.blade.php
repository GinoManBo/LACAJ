<nav class="subnav" aria-label="Secciones del gobernador">
    <a href="{{ route('gobernador.inicio') }}" @if (request()->routeIs('gobernador.inicio')) aria-current="page" @endif>Cola de autorizaciones</a>
    <a href="{{ route('gobernador.firma') }}" @if (request()->routeIs('gobernador.firma*')) aria-current="page" @endif>Firma electrónica</a>
</nav>
