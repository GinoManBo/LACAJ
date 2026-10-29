---
tags: [portal, postulacion]
actualizado: 2026-10-06
---
# Portal Público y Postulante

Código: `Postulacion\PostulacionController`, `Postulante\PerfilController`, spec en `docs/vistas/postulacion.md`.

## Formulario público por pasos (`/postular`)
1. **Carta y términos** — carta institucional + 6 términos; checkbox obligatorio.
2. **Datos personales** — identidad, accesibilidad (discapacidad + descripción), domicilio (región → comuna), contacto de emergencia, antecedentes de salud.
3. **Antecedentes educativos y archivos** — universidad, año (5° / Egresado), especialidad; **cédula frente y reverso obligatorias** (JPG/PNG ≤5 MB, cámara o archivo); opcionales: alumno regular, concentración de notas, certificado de egreso, currículum.
4. **Consultorio y fecha** — consultorios ordenados por cercanía al domicilio, el **orden de marcado es la preferencia**; calendario sin fines de semana, feriados ni fechas pasadas.
5. **Resumen y envío** — revisar/editar por bloque y confirmar.

Reglas de producto (las impone el servidor):
- No se puede saltar pasos: abrir el paso N sin completar los anteriores redirige al primero incompleto.
- Solo bloquean los campos obligatorios; al fallar, scroll al primer campo con error y contorno rojo.
- Un RUT con postulación ya en revisión no puede enviar otra.
- Confirmación: el resultado llega por correo en **máx. 15 días hábiles**.
- Ya **no hay validación automática de cédula con IA**.

## Perfil del postulante (`/mi-postulacion`)
Quien ingresa con [[ClaveÚnica]] ve el estado de su postulación, continúa su **borrador** y puede **actualizar sus datos**. Al aceptarse su postulación, ese mismo usuario pasa a rol `practicante` (se reutiliza el perfil).

## Conexiones
- Genera una postulación → [[Postulaciones]] (bandeja de [[Portal Secretaría]]).
- Entidad: [[Postulante]]. Vista de referencia: [[Formulario de Postulación]].
