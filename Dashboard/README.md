# Dashboard — Sistema de Gestión de Practicantes (CAJ Biobío)

Aplicación de gestión de prácticas profesionales para la Corporación de Asistencia Judicial de la Región del Biobío.

## Funciones

### Formulario de postulación
- Formulario por pasos: Carta de presentación, Datos personales, Antecedentes educativos, Consultorio y fecha de inicio, y Resumen.
- Definición del asterisco (campos obligatorios) en cada paso.
- Contacto de emergencia con número de teléfono.
- Selección de consultorios por comuna del Biobío, ordenados por cercanía al domicilio, con buscador.
- Orden de preferencia numerado de los consultorios seleccionados.
- Calendario de fecha de inicio que no permite seleccionar fines de semana ni feriados.
- Resumen final para revisar los datos antes de enviar y confirmación de envío.

### Panel
- **Consultorios:** colores dinámicos de disponibilidad según cupos (Muchos / Pocos / Sin cupos) con leyenda.
- **Postulaciones:** al aceptar se abre un menú superpuesto con los datos del postulante, permite reasignar (redirigir) el consultorio con buscador y muestra el consultorio postulado primero.
- **Prácticas Activas:** botón de estadísticas que abre una página con el detalle de la práctica, y menú superpuesto (lápiz) para cancelar la práctica con motivo.
- **Historial:** incluye el estado "Cancelada".

### Vista Practicante
- Calificaciones por instancias (primera obligatoria, segunda y tercera opcionales), nota de la práctica y nota promedio (escala 1–7).
- Documentación: descarga de certificado de práctica en PDF, disponible solo cuando la práctica ha finalizado.

## Desarrollo

Proyecto React + Vite + Tailwind CSS.

```bash
pnpm install
pnpm dev
```
