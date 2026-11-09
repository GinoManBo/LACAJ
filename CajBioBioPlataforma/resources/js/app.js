const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function envoltura(control) {
    return control.closest('.campo');
}

function marcarError(control, mensaje) {
    const campo = envoltura(control);
    control.setAttribute('aria-invalid', 'true');
    if (!campo) return;
    campo.classList.add('tiene-error');
    const caja = campo.querySelector('.campo-error');
    if (caja && mensaje) caja.textContent = mensaje;
}

function limpiarError(control) {
    const campo = envoltura(control);
    control.removeAttribute('aria-invalid');
    if (!campo) return;
    campo.classList.remove('tiene-error', 'campo-destacado');
    const caja = campo.querySelector('.campo-error');
    if (caja && caja.dataset.cliente === '1') {
        caja.textContent = '';
        delete caja.dataset.cliente;
    }
}

function irAlPrimerError(raiz = document) {
    const primero = raiz.querySelector('.campo.tiene-error, .campo-control[aria-invalid="true"]');
    if (!primero) return;
    const campo = primero.closest('.campo') || primero;
    const foco = primero.matches('.campo-control') ? primero
        : campo.querySelector('.campo-control, input:not([type=hidden]), button, select, textarea');
    campo.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
    campo.classList.add('campo-destacado');
    foco?.focus({ preventScroll: true });
    setTimeout(() => campo.classList.remove('campo-destacado'), 2500);
}

function mensajeNativo(control) {
    if (control.dataset.mensaje && control.validity.valueMissing) return control.dataset.mensaje;
    if (control.validity.typeMismatch && control.type === 'email') return 'Ingresa un correo válido.';
    if (control.validity.tooShort) return `Debe tener al menos ${control.minLength} caracteres.`;
    return 'Este campo es obligatorio.';
}

window.cajValidar = function (raiz) {
    let ok = true;
    raiz.querySelectorAll('.campo-control').forEach((c) => {
        if (c.disabled || c.type === 'hidden') return;
        limpiarError(c);
        const vacio = c.type === 'checkbox' ? !c.checked : !String(c.value).trim();
        if ((c.required && vacio) || (!vacio && !c.validity.valid)) {
            ok = false;
            marcarError(c, mensajeNativo(c));
            const caja = envoltura(c)?.querySelector('.campo-error');
            if (caja) caja.dataset.cliente = '1';
        }
    });
    raiz.querySelectorAll('[data-grupo-obligatorio]').forEach((g) => {
        g.classList.remove('tiene-error', 'campo-destacado');
        const lleno = [...g.querySelectorAll('input')].some((i) => (i.type === 'hidden' ? i.value !== '' : i.checked));
        if (lleno) {
            const caja = g.querySelector('.campo-error');
            if (caja && caja.dataset.cliente === '1') { caja.textContent = ''; delete caja.dataset.cliente; }
            return;
        }
        ok = false;
        g.classList.add('tiene-error');
        const caja = g.querySelector('.campo-error');
        if (caja && !caja.textContent.trim()) { caja.textContent = g.dataset.grupoObligatorio || 'Este campo es obligatorio.'; caja.dataset.cliente = '1'; }
    });
    if (!ok) irAlPrimerError(raiz);
    return ok;
};

window.cajLimpiarGrupo = function (g) {
    g.classList.remove('tiene-error', 'campo-destacado');
    const caja = g.querySelector('.campo-error');
    if (caja) { caja.textContent = ''; delete caja.dataset.cliente; }
};
document.addEventListener('change', (e) => {
    const g = e.target.closest?.('[data-grupo-obligatorio]');
    if (g) window.cajLimpiarGrupo(g);
});
window.cajMarcarError = marcarError;
window.cajLimpiarError = limpiarError;

document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-sin-validar')) return;
    form.noValidate = true;
    const submitter = e.submitter;
    if (submitter && submitter.hasAttribute('formnovalidate')) return;
    if (!window.cajValidar(form)) e.preventDefault();
});

document.addEventListener('input', (e) => {
    if (e.target.classList?.contains('campo-control')) limpiarError(e.target);
});
document.addEventListener('change', (e) => {
    if (e.target.classList?.contains('campo-control')) limpiarError(e.target);
});

document.addEventListener('DOMContentLoaded', () => {
    irAlPrimerError();

    document.querySelectorAll('[data-modal-abrir]').forEach((b) =>
        b.addEventListener('click', () => document.getElementById(b.dataset.modalAbrir)?.showModal()));
    document.querySelectorAll('dialog.modal').forEach((d) => {
        d.addEventListener('click', (e) => {
            if (e.target === d) return d.close();
            if (e.target.closest('[data-modal-cerrar]')) d.close();
        });
    });

    const sidebar = document.getElementById('sidebar');
    const velo = document.getElementById('velo');
    const toggle = document.getElementById('menu-toggle');
    const cambiar = (abrir) => {
        sidebar?.classList.toggle('abierto', abrir);
        velo?.classList.toggle('visible', abrir);
        toggle?.setAttribute('aria-expanded', String(abrir));
    };
    toggle?.addEventListener('click', () => cambiar(!sidebar.classList.contains('abierto')));
    velo?.addEventListener('click', () => cambiar(false));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cambiar(false); });

    document.querySelectorAll('tr[data-href]').forEach((tr) =>
        tr.addEventListener('click', (e) => { if (!e.target.closest('a,button')) window.location = tr.dataset.href; }));

    // Notificaciones dropdown
    const btnCampana = document.getElementById('btn-campana');
    const dropdownNotif = document.getElementById('dropdown-notificaciones');
    const contenedorNotif = document.getElementById('contenedor-notificaciones');

    if (btnCampana && dropdownNotif) {
        let leidasEnviadas = false;

        const abrirDropdown = () => {
            dropdownNotif.removeAttribute('hidden');
            btnCampana.setAttribute('aria-expanded', 'true');

            const contador = document.getElementById('campana-contador');
            const badgeNoLeidas = document.getElementById('dropdown-badge-noleidas');
            if (contador || badgeNoLeidas) {
                if (contador) contador.remove();
                if (badgeNoLeidas) badgeNoLeidas.remove();
                btnCampana.setAttribute('aria-label', 'Notificaciones');

                const urlLeer = btnCampana.dataset.urlLeer;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                if (urlLeer && csrfToken && !leidasEnviadas) {
                    leidasEnviadas = true;
                    fetch(urlLeer, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                    }).catch(() => {
                        leidasEnviadas = false;
                    });
                }
            }
        };

        const cerrarDropdown = () => {
            dropdownNotif.setAttribute('hidden', '');
            btnCampana.setAttribute('aria-expanded', 'false');
        };

        btnCampana.addEventListener('click', (e) => {
            e.stopPropagation();
            if (dropdownNotif.hasAttribute('hidden')) {
                abrirDropdown();
            } else {
                cerrarDropdown();
            }
        });

        document.addEventListener('click', (e) => {
            if (!dropdownNotif.hasAttribute('hidden') && !contenedorNotif?.contains(e.target)) {
                cerrarDropdown();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !dropdownNotif.hasAttribute('hidden')) {
                cerrarDropdown();
                btnCampana.focus();
            }
        });
    }

    document.querySelectorAll('form').forEach((f) =>
        f.addEventListener('submit', (e) => {
            setTimeout(() => {
                if (e.defaultPrevented) return;
                f.querySelectorAll('button[type=submit]:not([data-permitir-repetir])').forEach((b) => (b.disabled = true));
            }, 0);
        }));
});

(() => {
    const ruta = document.querySelector('[data-ruta]');
    if (!ruta) return;
    if (reduceMotion || !('IntersectionObserver' in window)) { ruta.classList.add('visible'); return; }
    document.documentElement.classList.add('js');
    const obs = new IntersectionObserver((es) => {
        if (es.some((e) => e.isIntersecting)) { ruta.classList.add('visible'); obs.disconnect(); }
    }, { threshold: 0.2 });
    obs.observe(ruta);
    setTimeout(() => ruta.classList.add('visible'), 2500);
})();

function formatearRutCampo(valor) {
    const limpio = valor.replace(/[^0-9kK]/g, '').toUpperCase().slice(0, 9);
    if (limpio.length < 2) return limpio;
    return `${limpio.slice(0, -1).replace(/\B(?=(\d{3})+(?!\d))/g, '.')}-${limpio.slice(-1)}`;
}
document.querySelectorAll('[data-rut]').forEach((campo) => {
    campo.addEventListener('input', () => { campo.value = formatearRutCampo(campo.value); });
    if (campo.value) campo.value = formatearRutCampo(campo.value);
});
