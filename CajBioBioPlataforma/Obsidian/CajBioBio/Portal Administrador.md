---
tags: [portal, admin]
actualizado: 2026-10-06
---
# Portal Administrador

Rol `administrador`: ve **todas las regiones**. Existe porque las secretarias a veces se equivocan y alguien debe poder corregir. Spec: `docs/vistas/admin.md`.

## Inicio
4 indicadores (prácticas activas, postulaciones pendientes, % ocupación, practicantes registrados) + información del sistema (versión, usuarios, consultorios, cupos totales).

## Gestión de practicantes (`admin.practicantes.*`)
- **Editar** datos del practicante y de la práctica (nombre, universidad, correo único, teléfono +56, fechas con término ≥ inicio, tutor, discapacidad).
- **Trasladar de sede**: elegir consultorio con cupos libres (error si no hay), tutor nuevo opcional, motivo; queda **historial de traslados** (`practica_traslados`).
- **Cancelar** (por_iniciar/activa) con motivo y detalle.

## Capacidad (`admin.capacidad.*`)
Tarjeta por sede con barra de ocupación y badge (muchos/pocos/sin). Botones **+/−** para ajustar cupos; no se puede bajar de los practicantes asignados ("traslade primero").

## Usuarios (`admin.usuarios.*`)
Crear/editar roles `administrador|secretaria|abogado|gobernador`; filtros por rol, estado y búsqueda; **activar/desactivar** (nunca borrar); **restablecer contraseña** (temporal mostrada una vez). RUT opcional pero es la llave de ingreso con [[ClaveÚnica]]. Secretaria exige región; abogado exige área (civil, penal, laboral, familia) y consultorios.

## Conexiones
- [[Roles y Permisos]] · [[Auditoría]] · [[Consultorio]]
