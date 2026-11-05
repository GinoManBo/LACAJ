const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function iniciarTerminos() {
    const form = document.querySelector('[data-paso-terminos]');
    if (!form) return;
    const caja = form.querySelector('input[name=acepta]');
    const boton = form.querySelector('[data-continuar]');
    const sync = () => boton.setAttribute('aria-disabled', String(!caja.checked));
    caja.addEventListener('change', sync);
    sync();
}

function formatearRut(valor) {
    const limpio = valor.replace(/[^0-9kK]/g, '').toUpperCase().slice(0, 9);
    if (limpio.length < 2) return limpio;
    const cuerpo = limpio.slice(0, -1).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return `${cuerpo}-${limpio.slice(-1)}`;
}

function iniciarDatos() {
    const form = document.querySelector('[data-paso-datos]');
    if (!form) return;

    const rut = form.querySelector('[data-rut]');
    rut?.addEventListener('input', () => { rut.value = formatearRut(rut.value); });
    if (rut?.value) rut.value = formatearRut(rut.value);

    const region = form.querySelector('[data-region]');
    const comuna = form.querySelector('[data-comuna]');
    const comunas = JSON.parse(document.getElementById('datos-comunas')?.textContent || '[]');
    region?.addEventListener('change', () => {
        const previa = comuna.value;
        comuna.replaceChildren();
        const vacia = new Option(region.value ? 'Selecciona una comuna' : 'Primero elige una región', '');
        comuna.add(vacia);
        comunas.filter((c) => String(c.r) === region.value).forEach((c) => comuna.add(new Option(c.n, c.id)));
        comuna.disabled = !region.value;
        if ([...comuna.options].some((o) => o.value === previa)) comuna.value = previa;
        window.cajLimpiarError?.(comuna);
    });

    const detalle = form.querySelector('[data-disc-detalle]');
    const texto = detalle?.querySelector('textarea');
    const aplicar = () => {
        const si = form.querySelector('input[name=discapacidad]:checked')?.value === '1';
        detalle.hidden = !si;
        texto.disabled = !si;
        texto.required = si;
        if (!si) window.cajLimpiarError?.(texto);
    };
    form.querySelectorAll('[data-disc]').forEach((r) => r.addEventListener('change', aplicar));
    if (detalle) aplicar();
}

const MAX_IMG = 5 * 1024 * 1024;

function mostrarArchivo(bloque, file) {
    const input = bloque.querySelector('.cedula-input');
    const vista = bloque.querySelector('[data-vista]');
    const zona = bloque.querySelector('[data-zona]');
    const marcador = bloque.querySelector('[data-marcador]');
    const cargada = bloque.querySelector('[data-cargada]');
    const nombre = bloque.querySelector('[data-nombre]');
    if (vista.dataset.url) URL.revokeObjectURL(vista.dataset.url);

    const esPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
    if (esPdf) {
        vista.hidden = true;
        zona.classList.remove('con-imagen');
    } else {
        const url = URL.createObjectURL(file);
        vista.dataset.url = url;
        vista.src = url;
        vista.alt = `Vista previa: ${file.name}`;
        vista.hidden = false;
        zona.classList.add('con-imagen');
    }
    marcador.hidden = true;
    cargada.hidden = false;
    nombre.textContent = file.name;
    input.required = true;
}

function asignarArchivo(bloque, file) {
    const input = bloque.querySelector('.cedula-input');
    const esPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
    const esImg = /^image\/(jpeg|png)$/i.test(file.type) || /\.(jpe?g|png)$/i.test(file.name);
    if (!esPdf && !esImg) {
        window.cajMarcarError(input, 'El archivo debe ser un documento PDF o imagen JPG/PNG.');
        input.value = '';
        return false;
    }
    if (file.size > MAX_IMG) {
        window.cajMarcarError(input, 'El archivo supera los 5 MB. Intente con un archivo de menor tamaño.');
        input.value = '';
        return false;
    }
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    window.cajLimpiarError(input);
    mostrarArchivo(bloque, file);
    return true;
}

function iniciarArchivos() {
    const form = document.querySelector('[data-paso-archivos]');
    if (!form) return;
    const modal = document.getElementById('modal-camara');
    const video = modal.querySelector('[data-camara-video]');
    const estado = modal.querySelector('[data-camara-estado]');
    const capturar = modal.querySelector('[data-camara-capturar]');
    let flujo = null;
    let destino = null;

    const detener = () => { flujo?.getTracks().forEach((t) => t.stop()); flujo = null; video.srcObject = null; };
    modal.addEventListener('close', detener);

    form.querySelectorAll('[data-cedula]').forEach((bloque) => {
        const input = bloque.querySelector('.cedula-input');
        const nativa = bloque.querySelector('[data-nativa]');

        bloque.querySelector('[data-subir]').addEventListener('click', () => input.click());
        input.addEventListener('change', () => {
            const f = input.files[0];
            if (f) asignarArchivo(bloque, f);
        });
        nativa.addEventListener('change', () => {
            const f = nativa.files[0];
            if (f) asignarArchivo(bloque, f);
            nativa.value = '';
        });

        bloque.querySelector('[data-camara]').addEventListener('click', async () => {
            if (!navigator.mediaDevices?.getUserMedia) return nativa.click();
            destino = bloque;
            estado.textContent = '';
            modal.querySelector('.modal-cabecera h2').textContent = bloque.dataset.camaraTitulo;
            modal.showModal();
            try {
                flujo = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } }, audio: false });
                video.srcObject = flujo;
                await video.play().catch(() => {});
            } catch (err) {
                modal.close();
                nativa.click();
            }
        });
    });

    capturar.addEventListener('click', () => {
        if (!flujo || !video.videoWidth) { estado.textContent = 'La cámara aún no está lista.'; return; }
        const lienzo = document.createElement('canvas');
        lienzo.width = video.videoWidth;
        lienzo.height = video.videoHeight;
        lienzo.getContext('2d').drawImage(video, 0, 0);
        lienzo.toBlob((blob) => {
            if (!blob) { estado.textContent = 'No se pudo capturar la imagen. Intente nuevamente.'; return; }
            const tipo = destino.querySelector('.cedula-input').name === 'cedula_frontal' ? 'frente' : 'reverso';
            asignarArchivo(destino, new File([blob], `cedula-${tipo}.jpg`, { type: 'image/jpeg' }));
            modal.close();
        }, 'image/jpeg', 0.9);
    });

    form.querySelectorAll('input[type=file][data-max-mb]').forEach((input) => {
        input.addEventListener('change', () => {
            const f = input.files[0];
            if (f && f.size > Number(input.dataset.maxMb) * 1024 * 1024) {
                window.cajMarcarError(input, `El archivo supera los ${input.dataset.maxMb} MB.`);
                input.value = '';
            }
        });
    });
}

function iniciarConsultorios() {
    const form = document.querySelector('[data-paso-consultorios]');
    if (!form) return;
    const grupo = form.querySelector('[data-hidden-cons]').closest('[data-grupo-obligatorio]');
    const contenedor = form.querySelector('[data-hidden-cons]');
    const listaOrden = form.querySelector('[data-orden-lista]');
    const vacio = form.querySelector('[data-orden-vacio]');
    const items = [...form.querySelectorAll('[data-item]')];
    const botones = new Map(items.map((li) => { const b = li.querySelector('.cons-item'); return [Number(b.dataset.id), b]; }));
    let orden = [...contenedor.querySelectorAll('input')].map((i) => Number(i.value)).filter((id) => botones.has(id));

    const render = () => {
        contenedor.replaceChildren(...orden.map((id) => {
            const h = document.createElement('input');
            h.type = 'hidden'; h.name = 'consultorios[]'; h.value = id;
            return h;
        }));
        listaOrden.replaceChildren(...orden.map((id, i) => {
            const li = document.createElement('li');
            const num = Object.assign(document.createElement('span'), { className: 'cons-num', textContent: i + 1 });
            const nom = Object.assign(document.createElement('span'), { className: 'cons-nombre', textContent: botones.get(id).querySelector('[data-nombre]').textContent });
            const quitar = document.createElement('button');
            quitar.type = 'button'; quitar.className = 'cons-quitar';
            quitar.setAttribute('aria-label', `Quitar ${nom.textContent}`);
            quitar.innerHTML = '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>';
            quitar.addEventListener('click', () => alternar(id));
            li.append(num, nom, quitar);
            return li;
        }));
        vacio.hidden = orden.length > 0;
        botones.forEach((b, id) => {
            const pos = orden.indexOf(id);
            b.setAttribute('aria-pressed', String(pos >= 0));
            b.querySelector('.cons-marca').textContent = pos >= 0 ? pos + 1 : '';
        });
    };

    function alternar(id) {
        orden = orden.includes(id) ? orden.filter((x) => x !== id) : [...orden, id];
        render();
        if (orden.length) window.cajLimpiarGrupo?.(grupo);
    }
    botones.forEach((b, id) => b.addEventListener('click', () => alternar(id)));

    const buscar = form.querySelector('[data-buscar]');
    const sin = form.querySelector('[data-sin-resultados]');
    const norm = (t) => t.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
    buscar.addEventListener('input', () => {
        const q = norm(buscar.value);
        let visibles = 0;
        items.forEach((li) => { const ok = !q || li.dataset.buscable.includes(q); li.hidden = !ok; if (ok) visibles++; });
        sin.hidden = visibles > 0;
    });
    buscar.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });
    render();
}

const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
const DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
const desdeIso = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
const larga = (d) => `${DIAS[d.getDay()]} ${d.getDate()} de ${MESES[d.getMonth()]} de ${d.getFullYear()}`;

function iniciarCalendario() {
    const cal = document.querySelector('[data-calendario]');
    if (!cal) return;
    const grupo = cal.closest('[data-grupo-obligatorio]');
    const hidden = grupo.querySelector('[data-cal-valor]');
    const salida = grupo.querySelector('[data-cal-elegida]');
    const cuadricula = cal.querySelector('[data-cal-dias]');
    const titulo = cal.querySelector('[data-cal-titulo]');
    const prev = cal.querySelector('[data-cal-prev]');
    const next = cal.querySelector('[data-cal-next]');
    const feriados = new Set(JSON.parse(cal.querySelector('[data-cal-feriados]').textContent || '[]'));
    const hoy = desdeIso(cal.dataset.hoy);
    const limite = new Date(hoy.getFullYear() + 1, hoy.getMonth(), 1);
    let elegida = hidden.value ? desdeIso(hidden.value) : null;
    let mes = new Date((elegida || hoy).getFullYear(), (elegida || hoy).getMonth(), 1);
    let foco = elegida && elegida >= hoy ? elegida : null;

    const estado = (d) => {
        if (d < hoy) return 'pasado';
        if (feriados.has(iso(d))) return 'feriado';
        if (d.getDay() === 0 || d.getDay() === 6) return 'finde';
        return 'ok';
    };
    const textoElegida = () => { salida.textContent = elegida ? `Inicio: ${larga(elegida)}` : 'Aún no ha elegido una fecha.'; };

    function primerHabil(desde) {
        const d = new Date(desde);
        for (let i = 0; i < 400 && estado(d) !== 'ok'; i++) d.setDate(d.getDate() + 1);
        return d;
    }

    function dibujar(enfocar = false) {
        titulo.textContent = `${MESES[mes.getMonth()]} ${mes.getFullYear()}`;
        prev.disabled = mes <= new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        next.disabled = mes >= new Date(limite.getFullYear(), limite.getMonth(), 1);
        if (!foco || foco.getMonth() !== mes.getMonth() || foco.getFullYear() !== mes.getFullYear()) {
            const inicio = new Date(mes.getFullYear(), mes.getMonth(), 1);
            const h = primerHabil(new Date(Math.max(inicio, hoy)));
            foco = h.getMonth() === mes.getMonth() ? h : new Date(Math.max(inicio, hoy));
        }
        const celdas = [];
        const desfase = (new Date(mes.getFullYear(), mes.getMonth(), 1).getDay() + 6) % 7;
        for (let i = 0; i < desfase; i++) celdas.push(Object.assign(document.createElement('span'), { className: 'cal-vacio', ariaHidden: 'true' }));
        const total = new Date(mes.getFullYear(), mes.getMonth() + 1, 0).getDate();
        for (let n = 1; n <= total; n++) {
            const d = new Date(mes.getFullYear(), mes.getMonth(), n);
            const est = estado(d);
            const b = document.createElement('button');
            b.type = 'button';
            b.className = `cal-dia${est === 'feriado' ? ' es-feriado' : ''}${iso(d) === iso(hoy) ? ' es-hoy' : ''}`;
            b.textContent = n;
            b.dataset.fecha = iso(d);
            if (est !== 'ok') b.setAttribute('aria-disabled', 'true');
            b.setAttribute('aria-label', `${larga(d)}${est === 'feriado' ? ', feriado, no disponible' : est === 'finde' ? ', fin de semana, no disponible' : est === 'pasado' ? ', fecha pasada, no disponible' : ''}`);
            b.setAttribute('aria-pressed', String(!!elegida && iso(d) === iso(elegida)));
            b.tabIndex = foco && iso(d) === iso(foco) ? 0 : -1;
            celdas.push(b);
        }
        cuadricula.replaceChildren(...celdas);
        if (enfocar) cuadricula.querySelector('[tabindex="0"]')?.focus();
    }

    function elegir(d) {
        elegida = d;
        hidden.value = iso(d);
        foco = d;
        textoElegida();
        window.cajLimpiarGrupo?.(grupo);
        dibujar();
    }

    cuadricula.addEventListener('click', (e) => {
        const b = e.target.closest('.cal-dia');
        if (b && b.getAttribute('aria-disabled') !== 'true') elegir(desdeIso(b.dataset.fecha));
    });
    cuadricula.addEventListener('keydown', (e) => {
        const paso = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }[e.key];
        if (!paso || !foco) return;
        e.preventDefault();
        const d = new Date(foco);
        d.setDate(d.getDate() + paso);
        if (d < hoy || d >= new Date(limite.getFullYear(), limite.getMonth() + 1, 1)) return;
        foco = d;
        mes = new Date(d.getFullYear(), d.getMonth(), 1);
        dibujar();
        const btn = cuadricula.querySelector(`[data-fecha="${iso(d)}"]`);
        btn?.setAttribute('tabindex', '0');
        btn?.focus();
    });
    const moverMes = (delta) => { mes = new Date(mes.getFullYear(), mes.getMonth() + delta, 1); foco = null; dibujar(); };
    prev.addEventListener('click', () => moverMes(-1));
    next.addEventListener('click', () => moverMes(1));

    textoElegida();
    dibujar();
}

document.addEventListener('DOMContentLoaded', () => {
    iniciarTerminos();
    iniciarDatos();
    iniciarArchivos();
    iniciarConsultorios();
    iniciarCalendario();
});
