import { createContext, useContext, useEffect, useRef, useState } from 'react'
import { certLogo } from './certLogo'
import { APP_VERSION } from './version'
import { geminiConfigurado, MODELO_GEMINI, validarCedula } from './validacionCedula'
import type { ResultadoValidacion, Severidad } from './validacionCedula'

type Page = 'panel' | 'postulaciones' | 'practicas' | 'historial' | 'consultorio' | 'formulario' | 'adminDash' | 'abogadoDash' | 'practicanteDash' | 'estadisticaPractica'

const abogados = [
  { id: 1, ini: 'CM', nombre: 'Carlos Muñoz Sepúlveda', area: 'Derecho Civil', activos: 3, historicos: 12, color: '#5b7fd4' },
  { id: 2, ini: 'AR', nombre: 'Andrea Rojas Fuentes', area: 'Derecho Penal', activos: 2, historicos: 9, color: '#27ae60' },
  { id: 3, ini: 'FC', nombre: 'Felipe Contreras Vidal', area: 'Derecho Laboral', activos: 2, historicos: 7, color: '#e67e22' },
  { id: 4, ini: 'ME', nombre: 'Marcela Espinoza Torres', area: 'Derecho de Familia', activos: 2, historicos: 11, color: '#8e6dbf' },
  { id: 5, ini: 'RS', nombre: 'Roberto Sánchez Aravena', area: 'Derecho Civil', activos: 1, historicos: 8, color: '#2980b9' },
  { id: 6, ini: 'LF', nombre: 'Lorena Figueroa Mella', area: 'Derecho Penal', activos: 1, historicos: 6, color: '#9b59b6' },
  { id: 7, ini: 'PH', nombre: 'Patricio Henríquez Castro', area: 'Derecho Laboral', activos: 1, historicos: 5, color: '#3498db' },
  { id: 8, ini: 'CD', nombre: 'Carolina Díaz Poblete', area: 'Derecho de Familia', activos: 0, historicos: 4, color: '#c0392b' },
]

const consultorios = [
  { id: 1, nombre: 'Consultorio Centro — Concepción', direccion: 'O\'Higgins 440, piso 3, Concepción', practicantes: 2, capacidad: 6, color: '#2980b9', region: 'Región del Biobío' },
  { id: 2, nombre: 'Consultorio Talcahuano', direccion: 'Colón 1262, Talcahuano', practicantes: 3, capacidad: 4, color: '#27ae60', region: 'Región del Biobío' },
  { id: 3, nombre: 'Consultorio San Pedro de la Paz', direccion: 'Av. Gran Bretaña 3035, San Pedro', practicantes: 2, capacidad: 5, color: '#8e6dbf', region: 'Región del Biobío' },
  { id: 4, nombre: 'Consultorio Los Ángeles', direccion: 'Caupolicán 380, Los Ángeles', practicantes: 3, capacidad: 3, color: '#e67e22', region: 'Región del Biobío' },
  { id: 5, nombre: 'Consultorio Chillán', direccion: 'Arauco 584, Chillán', practicantes: 2, capacidad: 3, color: '#16a085', region: 'Región de Ñuble' },
  { id: 6, nombre: 'Consultorio Coronel', direccion: 'Manuel Rodríguez 481, Coronel', practicantes: 2, capacidad: 2, color: '#c0392b', region: 'Región del Biobío' },
  { id: 7, nombre: 'Consultorio Santiago Centro', direccion: 'Agustinas 1235, Santiago', practicantes: 4, capacidad: 8, color: '#34495e', region: 'Región Metropolitana' },
  { id: 8, nombre: 'Consultorio Maipú', direccion: 'Av. Pajaritos 2020, Maipú', practicantes: 3, capacidad: 5, color: '#d35400', region: 'Región Metropolitana' },
  { id: 9, nombre: 'Consultorio Temuco', direccion: 'Manuel Montt 855, Temuco', practicantes: 2, capacidad: 4, color: '#16a085', region: 'Región de La Araucanía' },
  { id: 10, nombre: 'Consultorio Valparaíso', direccion: 'Condell 1490, Valparaíso', practicantes: 1, capacidad: 4, color: '#7f8c8d', region: 'Región de Valparaíso' },
]

const regionesOperativas = Array.from(new Set(consultorios.map(c => c.region)))

const secretariaPorRegion: Record<string, { nombre: string; ini: string }> = {
  'Región del Biobío': { nombre: 'Secretaría Biobío', ini: 'SB' },
  'Región de Ñuble': { nombre: 'Secretaría Ñuble', ini: 'SÑ' },
  'Región Metropolitana': { nombre: 'Secretaría Metropolitana', ini: 'SM' },
  'Región de La Araucanía': { nombre: 'Secretaría Araucanía', ini: 'SA' },
  'Región de Valparaíso': { nombre: 'Secretaría Valparaíso', ini: 'SV' },
}

const regionDeConsultorioId = (id: number) => consultorios.find(c => c.id === id)?.region ?? ''
const regionDeConsultorioNombre = (nombre: string) =>
  consultorios.find(c => c.nombre === nombre || c.nombre.includes(nombre))?.region ?? ''

function nivelDisponibilidad(practicantes: number, capacidad: number) {
  const libres = capacidad - practicantes
  const ratio = capacidad > 0 ? libres / capacidad : 0
  if (libres <= 0) return { label: 'Sin cupos', color: '#c0392b', bg: '#fdecea', accent: '#e74c3c' }
  if (ratio >= 0.5) return { label: 'Muchos cupos', color: '#2e7d32', bg: '#e8f5e9', accent: '#27ae60' }
  return { label: 'Pocos cupos', color: '#e65100', bg: '#fff3e0', accent: '#f39c12' }
}

const postulaciones = [
  { id: 1, ini: 'PV', nombre: 'Paula Villanueva Cortés', rut: '20.456.789-1', universidad: 'U. de Concepción', año: '5°', email: 'paula.villanueva@udec.cl', tel: '+56 9 8765 4321', fecha: '22/07/2026', color: '#5b7fd4', estado: 'pendiente', especialidad: 'Derecho de Familia', consultorioId: 2 },
  { id: 2, ini: 'RM', nombre: 'Ricardo Mendoza Jara', rut: '21.234.567-8', universidad: 'U. del Bío-Bío', año: 'Egresado', email: 'r.mendoza@ubiobio.cl', tel: '+56 9 1122 3344', fecha: '21/07/2026', color: '#27ae60', estado: 'pendiente', especialidad: 'Derecho Laboral', consultorioId: 4 },
  { id: 3, ini: 'MF', nombre: 'Martín Fuentes Oliva', rut: '20.876.543-2', universidad: 'UCSC', año: '5°', email: 'm.fuentes@ucsc.cl', tel: '+56 9 5566 7788', fecha: '19/07/2026', color: '#9b59b6', estado: 'pendiente', especialidad: 'Derecho Penal', consultorioId: 1 },
  { id: 4, ini: 'CA', nombre: 'Camila Arriagada Núñez', rut: '20.112.334-5', universidad: 'U. de Chile', año: '5°', email: 'c.arriagada@uchile.cl', tel: '+56 9 6677 8899', fecha: '23/07/2026', color: '#34495e', estado: 'pendiente', especialidad: 'Derecho Civil', consultorioId: 7 },
  { id: 5, ini: 'JC', nombre: 'Joaquín Cárdenas Millán', rut: '21.556.778-9', universidad: 'U. de La Frontera', año: 'Egresado', email: 'j.cardenas@ufro.cl', tel: '+56 9 2211 5566', fecha: '20/07/2026', color: '#16a085', estado: 'pendiente', especialidad: 'Derecho de Familia', consultorioId: 9 },
]

type ArchivoPostulacion = { nombre: string; tipo: string; peso: string; fecha: string; categoria: 'universitario' | 'cedula' }

const archivosPostulacion: Record<number, ArchivoPostulacion[]> = {
  1: [
    { nombre: 'certificado-alumno-regular.pdf', tipo: 'Certificado de alumno regular', peso: '218 KB', fecha: '22/07/2026', categoria: 'universitario' },
    { nombre: 'concentracion-notas.pdf', tipo: 'Concentración de notas', peso: '540 KB', fecha: '22/07/2026', categoria: 'universitario' },
    { nombre: 'curriculum-paula-villanueva.pdf', tipo: 'Currículum vitae', peso: '162 KB', fecha: '22/07/2026', categoria: 'universitario' },
    { nombre: 'cedula-frontal.jpg', tipo: 'Cédula — lado frontal', peso: '1.1 MB', fecha: '22/07/2026', categoria: 'cedula' },
    { nombre: 'cedula-reverso.jpg', tipo: 'Cédula — lado reverso', peso: '1.0 MB', fecha: '22/07/2026', categoria: 'cedula' },
  ],
  2: [
    { nombre: 'certificado-alumno-regular.pdf', tipo: 'Certificado de alumno regular', peso: '203 KB', fecha: '21/07/2026', categoria: 'universitario' },
    { nombre: 'concentracion-notas.pdf', tipo: 'Concentración de notas', peso: '612 KB', fecha: '21/07/2026', categoria: 'universitario' },
    { nombre: 'certificado-egreso.pdf', tipo: 'Certificado de egreso o licenciatura', peso: '187 KB', fecha: '21/07/2026', categoria: 'universitario' },
    { nombre: 'ayudantia-derecho-laboral.pdf', tipo: 'Otros antecedentes académicos', peso: '96 KB', fecha: '21/07/2026', categoria: 'universitario' },
    { nombre: 'cedula-frontal.jpg', tipo: 'Cédula — lado frontal', peso: '980 KB', fecha: '21/07/2026', categoria: 'cedula' },
    { nombre: 'cedula-reverso.jpg', tipo: 'Cédula — lado reverso', peso: '944 KB', fecha: '21/07/2026', categoria: 'cedula' },
  ],
  3: [
    { nombre: 'certificado-alumno-regular.pdf', tipo: 'Certificado de alumno regular', peso: '231 KB', fecha: '19/07/2026', categoria: 'universitario' },
    { nombre: 'concentracion-notas.pdf', tipo: 'Concentración de notas', peso: '498 KB', fecha: '19/07/2026', categoria: 'universitario' },
    { nombre: 'cedula-frontal.jpg', tipo: 'Cédula — lado frontal', peso: '1.3 MB', fecha: '19/07/2026', categoria: 'cedula' },
  ],
  4: [
    { nombre: 'certificado-alumno-regular.pdf', tipo: 'Certificado de alumno regular', peso: '244 KB', fecha: '23/07/2026', categoria: 'universitario' },
    { nombre: 'concentracion-notas.pdf', tipo: 'Concentración de notas', peso: '571 KB', fecha: '23/07/2026', categoria: 'universitario' },
    { nombre: 'cedula-frontal.jpg', tipo: 'Cédula — lado frontal', peso: '1.2 MB', fecha: '23/07/2026', categoria: 'cedula' },
    { nombre: 'cedula-reverso.jpg', tipo: 'Cédula — lado reverso', peso: '1.1 MB', fecha: '23/07/2026', categoria: 'cedula' },
  ],
  5: [
    { nombre: 'certificado-alumno-regular.pdf', tipo: 'Certificado de alumno regular', peso: '199 KB', fecha: '20/07/2026', categoria: 'universitario' },
    { nombre: 'concentracion-notas.pdf', tipo: 'Concentración de notas', peso: '523 KB', fecha: '20/07/2026', categoria: 'universitario' },
    { nombre: 'certificado-egreso.pdf', tipo: 'Certificado de egreso o licenciatura', peso: '176 KB', fecha: '20/07/2026', categoria: 'universitario' },
    { nombre: 'cedula-frontal.jpg', tipo: 'Cédula — lado frontal', peso: '1.0 MB', fecha: '20/07/2026', categoria: 'cedula' },
    { nombre: 'cedula-reverso.jpg', tipo: 'Cédula — lado reverso', peso: '1.2 MB', fecha: '20/07/2026', categoria: 'cedula' },
  ],
}

const slugNombre = (nombre: string) =>
  nombre.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').split(' ').slice(0, 2).join('-')

function archivosDePractica(p: { id: number; practicante: string; inicio: string }): ArchivoPostulacion[] {
  const slug = slugNombre(p.practicante)
  const kb = (n: number) => `${180 + ((p.id * 37 + n * 53) % 420)} KB`
  const docs: ArchivoPostulacion[] = [
    { nombre: `certificado-alumno-regular-${slug}.pdf`, tipo: 'Certificado de alumno regular', peso: kb(1), fecha: p.inicio, categoria: 'universitario' },
    { nombre: `concentracion-notas-${slug}.pdf`, tipo: 'Concentración de notas', peso: kb(2), fecha: p.inicio, categoria: 'universitario' },
    { nombre: `curriculum-${slug}.pdf`, tipo: 'Currículum vitae', peso: kb(3), fecha: p.inicio, categoria: 'universitario' },
  ]
  if (p.id % 2 === 0) docs.push({ nombre: `certificado-egreso-${slug}.pdf`, tipo: 'Certificado de egreso o licenciatura', peso: kb(4), fecha: p.inicio, categoria: 'universitario' })
  docs.push(
    { nombre: `cedula-frontal-${slug}.jpg`, tipo: 'Cédula — lado frontal', peso: `1.${p.id % 9} MB`, fecha: p.inicio, categoria: 'cedula' },
    { nombre: `cedula-reverso-${slug}.jpg`, tipo: 'Cédula — lado reverso', peso: `1.${(p.id + 3) % 9} MB`, fecha: p.inicio, categoria: 'cedula' },
  )
  return docs
}

const practicas = [
  { id: 1, practicante: 'Catalina Vera Muñoz', universidad: 'U. de Concepción', inicio: '01/03/2026', termino: '31/08/2026', abogado: 'Carlos Muñoz S.', abogadoIni: 'CM', abogadoColor: '#5b7fd4', estado: 'Activa', uniColor: '#2980b9', consultorio: 'Centro — Concepción', tel: '+56 9 7654 3210', email: 'c.vera@udec.cl', discapacidad: 'Ninguna' },
  { id: 2, practicante: 'Andrés Riquelme Soto', universidad: 'U. del Bío-Bío', inicio: '15/03/2026', termino: '15/09/2026', abogado: 'Andrea Rojas F.', abogadoIni: 'AR', abogadoColor: '#27ae60', estado: 'Activa', uniColor: '#e67e22', consultorio: 'Talcahuano', tel: '+56 9 8877 6655', email: 'a.riquelme@ubb.cl', discapacidad: 'Ninguna' },
  { id: 3, practicante: 'Fernanda Lagos Parra', universidad: 'UCSC', inicio: '01/04/2026', termino: '30/09/2026', abogado: 'Felipe Contreras V.', abogadoIni: 'FC', abogadoColor: '#e67e22', estado: 'Activa', uniColor: '#8e6dbf', consultorio: 'Centro — Concepción', tel: '+56 9 9988 1122', email: 'f.lagos@ucsc.cl', discapacidad: 'Ninguna' },
  { id: 4, practicante: 'Diego Fuentes Alarcón', universidad: 'U. de Concepción', inicio: '01/07/2026', termino: '31/12/2026', abogado: 'Marcela Espinoza T.', abogadoIni: 'ME', abogadoColor: '#8e6dbf', estado: 'Por iniciar', uniColor: '#2980b9', consultorio: 'San Pedro de la Paz', tel: '+56 9 6655 4433', email: 'd.fuentes@udec.cl', discapacidad: 'Ninguna' },
  { id: 5, practicante: 'Javiera Monsalve Reyes', universidad: 'U. San Sebastián', inicio: '01/01/2026', termino: '30/07/2026', abogado: 'Roberto Sánchez A.', abogadoIni: 'RS', abogadoColor: '#2980b9', estado: 'Por terminar', uniColor: '#c0392b', consultorio: 'Centro — Concepción', tel: '+56 9 5544 3322', email: 'j.monsalve@uss.cl', discapacidad: 'Hipoacusia leve' },
  { id: 6, practicante: 'Tomás Sepúlveda Ortiz', universidad: 'U. del Bío-Bío', inicio: '15/04/2026', termino: '15/10/2026', abogado: 'Lorena Figueroa M.', abogadoIni: 'LF', abogadoColor: '#9b59b6', estado: 'Activa', uniColor: '#e67e22', consultorio: 'Talcahuano', tel: '+56 9 3322 1100', email: 't.sepulveda@ubb.cl', discapacidad: 'Ninguna' },
  { id: 7, practicante: 'Ignacio Torres Bahamondes', universidad: 'U. de Concepción', inicio: '01/10/2026', termino: '31/03/2027', abogado: 'Carlos Muñoz S.', abogadoIni: 'CM', abogadoColor: '#5b7fd4', estado: 'Por iniciar', uniColor: '#2980b9', consultorio: 'Centro — Concepción', tel: '+56 9 4433 2211', email: 'i.torres@udec.cl', discapacidad: 'Ninguna' },
  { id: 8, practicante: 'Constanza Neira Vidal', universidad: 'U. San Sebastián', inicio: '01/02/2026', termino: '10/08/2026', abogado: 'Carlos Muñoz S.', abogadoIni: 'CM', abogadoColor: '#5b7fd4', estado: 'Por terminar', uniColor: '#c0392b', consultorio: 'Centro — Concepción', tel: '+56 9 7788 9900', email: 'c.neira@uss.cl', discapacidad: 'Ninguna' },
  { id: 9, practicante: 'Matías Poblete Cárcamo', universidad: 'UCSC', inicio: '01/09/2025', termino: '28/02/2026', abogado: 'Carlos Muñoz S.', abogadoIni: 'CM', abogadoColor: '#5b7fd4', estado: 'Finalizada', uniColor: '#8e6dbf', consultorio: 'Centro — Concepción', tel: '+56 9 2233 4455', email: 'm.poblete@ucsc.cl', discapacidad: 'Ninguna' },
  { id: 10, practicante: 'Antonia Silva Duarte', universidad: 'U. de Chile', inicio: '01/03/2026', termino: '31/08/2026', abogado: 'Patricio Henríquez C.', abogadoIni: 'PH', abogadoColor: '#3498db', estado: 'Activa', uniColor: '#34495e', consultorio: 'Santiago Centro', tel: '+56 9 1010 2020', email: 'a.silva@uchile.cl', discapacidad: 'Ninguna' },
  { id: 11, practicante: 'Benjamín Curihual Paillán', universidad: 'U. de La Frontera', inicio: '15/03/2026', termino: '15/09/2026', abogado: 'Carolina Díaz P.', abogadoIni: 'CD', abogadoColor: '#c0392b', estado: 'Activa', uniColor: '#16a085', consultorio: 'Temuco', tel: '+56 9 3030 4040', email: 'b.curihual@ufro.cl', discapacidad: 'Ninguna' },
  { id: 12, practicante: 'Isidora Bravo Leiva', universidad: 'U. de Valparaíso', inicio: '01/06/2026', termino: '30/11/2026', abogado: 'Roberto Sánchez A.', abogadoIni: 'RS', abogadoColor: '#2980b9', estado: 'Por iniciar', uniColor: '#7f8c8d', consultorio: 'Valparaíso', tel: '+56 9 5050 6060', email: 'i.bravo@uv.cl', discapacidad: 'Ninguna' },
]

type CancelacionHistorial = { fecha: string; motivo: string; antesDeIniciar: boolean }

const historial: {
  id: number; practicante: string; universidad: string; periodo: string; abogado: string
  abogadoIni: string; abogadoColor: string; uniColor: string; estado: string; consultorio: string
  email: string; tel: string; cancelacion?: CancelacionHistorial
}[] = [
  { id: 1, practicante: 'Macarena Soto Pizarro', universidad: 'U. de Concepción', periodo: '01/01/2026 – 30/06/2026', abogado: 'Carlos Muñoz S.', abogadoIni: 'CM', abogadoColor: '#5b7fd4', uniColor: '#2980b9', estado: 'Finalizada', consultorio: 'Centro — Concepción', email: 'm.soto@udec.cl', tel: '+56 9 9081 2233' },
  { id: 2, practicante: 'Sebastián Mora Acuña', universidad: 'U. del Bío-Bío', periodo: '01/01/2026 – 30/06/2026', abogado: 'Andrea Rojas F.', abogadoIni: 'AR', abogadoColor: '#27ae60', uniColor: '#e67e22', estado: 'Finalizada', consultorio: 'Talcahuano', email: 's.mora@ubiobio.cl', tel: '+56 9 8172 4455' },
  { id: 3, practicante: 'Valentina Cuevas Roa', universidad: 'UCSC', periodo: '01/08/2025 – 31/01/2026', abogado: 'Felipe Contreras V.', abogadoIni: 'FC', abogadoColor: '#e67e22', uniColor: '#8e6dbf', estado: 'Cancelada', consultorio: 'Centro — Concepción', email: 'v.cuevas@ucsc.cl', tel: '+56 9 7364 8899', cancelacion: { fecha: '12/11/2025', motivo: 'Suspendió la práctica por licencia médica prolongada. Se interrumpió al tercer mes.', antesDeIniciar: false } },
  { id: 4, practicante: 'Nicolás Bravo Herrera', universidad: 'U. San Sebastián', periodo: '01/08/2025 – 31/01/2026', abogado: 'Marcela Espinoza T.', abogadoIni: 'ME', abogadoColor: '#8e6dbf', uniColor: '#c0392b', estado: 'Finalizada', consultorio: 'San Pedro de la Paz', email: 'n.bravo@uss.cl', tel: '+56 9 6255 1177' },
  { id: 5, practicante: 'Rocío Palma Ibarra', universidad: 'U. de Chile', periodo: '01/01/2026 – 30/06/2026', abogado: 'Patricio Henríquez C.', abogadoIni: 'PH', abogadoColor: '#3498db', uniColor: '#34495e', estado: 'Finalizada', consultorio: 'Santiago Centro', email: 'r.palma@uchile.cl', tel: '+56 9 4488 3322' },
  { id: 6, practicante: 'Cristóbal Ñanco Huenchul', universidad: 'U. de La Frontera', periodo: '01/08/2025 – 31/01/2026', abogado: 'Carolina Díaz P.', abogadoIni: 'CD', abogadoColor: '#c0392b', uniColor: '#16a085', estado: 'Finalizada', consultorio: 'Temuco', email: 'c.nanco@ufro.cl', tel: '+56 9 3311 7788' },
  { id: 7, practicante: 'Emilia Rojas Navarrete', universidad: 'U. del Bío-Bío', periodo: '01/03/2026 – 31/08/2026', abogado: 'Andrea Rojas F.', abogadoIni: 'AR', abogadoColor: '#27ae60', uniColor: '#e67e22', estado: 'Cancelada', consultorio: 'Talcahuano', email: 'e.rojas@ubiobio.cl', tel: '+56 9 5127 9944', cancelacion: { fecha: '24/02/2026', motivo: 'Desistió por motivos personales cinco días antes de la fecha de inicio. No alcanzó a comenzar la práctica.', antesDeIniciar: true } },
]

const evaluacionHistorial = (id: number): Record<string, number> =>
  Object.fromEntries(encuestaItems.map((item, i) => [item.id, Number((4.8 + ((id * 13 + i * 7) % 21) / 10).toFixed(1))]))

const usuarioPorVista: Partial<Record<Page, { nombre: string; ini: string }>> = {
  adminDash: { nombre: 'Administrador', ini: 'AD' },
  abogadoDash: { nombre: 'Abogado tutor', ini: 'AT' },
  practicanteDash: { nombre: 'Practicante', ini: 'PR' },
}

const vistasRapidas: { id: Page; label: string; icon: string }[] = [
  { id: 'adminDash', label: 'Vista Administrador', icon: 'shield' },
  { id: 'abogadoDash', label: 'Vista Abogado', icon: 'users' },
  { id: 'practicanteDash', label: 'Vista Practicante', icon: 'user_plus' },
]

const navItems: { id: Page; label: string; icon: string; badge?: number }[] = [
  { id: 'panel', label: 'Panel General', icon: 'grid' },
  { id: 'postulaciones', label: 'Postulaciones', icon: 'doc', badge: 3 },
  { id: 'practicas', label: 'Prácticas Activas', icon: 'calendar' },
  { id: 'historial', label: 'Historial', icon: 'clock' },
  { id: 'consultorio', label: 'Consultorios', icon: 'building' },
]

const practicanteData = {
  nombre: 'Catalina Vera Muñoz',
  nombreLegal: 'Catalina Andrea Vera Muñoz',
  rut: '20.114.872-6',
  genero: 'F' as 'F' | 'M',
  universidad: 'Universidad de Concepción',
  consultorio: 'Consultorio Centro — Concepción',
  abogado: 'Carlos Muñoz Sepúlveda',
  abogadoEmail: 'carlos.munoz@cajbiobio.cl',
  abogadoTelefono: '+56 9 7654 3210',
  inicio: '01/03/2026',
  termino: '31/07/2026',
  estado: 'Finalizada',
  notaPractica: 6.5,
  calificaciones: [
    { instancia: 'Primera instancia', tipo: 'Obligatoria', nota: 6.4 as number | null, detalle: 'Evaluación emitida al término de la práctica.' },
    { instancia: 'Segunda instancia', tipo: 'Opcional', nota: null as number | null, detalle: 'Solo aplica si la práctica contempla una segunda instancia.' },
    { instancia: 'Tercera instancia', tipo: 'Opcional', nota: null as number | null, detalle: 'Solo aplica si la práctica contempla una tercera instancia.' },
  ],
  mensajes: [
    { id: 1, remitente: 'abogado', texto: 'Hola Catalina, recuerda revisar el expediente del caso de familia antes de la reunión de mañana.' },
    { id: 2, remitente: 'practicante', texto: 'Perfecto, lo revisaré esta tarde y te entrego mis observaciones.' },
  ],
  resolucion: { numero: 597, anio: 2026, oficio: '605/2026', comuna: 'Concepción' },
  evaluacion: {
    conocimiento: 5.0,
    responsabilidad: 5.2,
    iniciativa: 6.0,
    sentidoSocial: 6.2,
    conducta: 6.8,
    honorabilidad: 6.9,
    asistencia: 6.1,
  } as Record<string, number>,
}

const conceptoDeNota = (n: number) =>
  n >= 6.5 ? 'SOBRESALIENTE' : n >= 5.5 ? 'MUY BUENO' : n >= 4.5 ? 'BUENO' : n >= 4 ? 'SUFICIENTE' : 'DEFICIENTE'

function Icon({ name, size = 16 }: { name: string; size?: number }) {
  const s = size
  const icons: Record<string, JSX.Element> = {
    grid: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>,
    doc: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>,
    calendar: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>,
    clock: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>,
    users: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>,
    building: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="7" width="20" height="15"/><polyline points="16 7 16 3 8 3 8 7"/><line x1="12" y1="22" x2="12" y2="7"/></svg>,
    search: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>,
    chevron_left: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="15 18 9 12 15 6"/></svg>,
    chevron_right: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="9 18 15 12 9 6"/></svg>,
    eye: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>,
    edit: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>,
    check: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><polyline points="20 6 9 17 4 12"/></svg>,
    upload: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>,
    arrow_right: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>,
    x: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>,
    user_plus: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>,
    bell: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>,
    alert: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><triangle/><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>,
    star: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>,
    map_pin: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>,
    phone: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13 19.79 19.79 0 0 1 1.61 4.38 2 2 0 0 1 3.59 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.08 6.08l.82-.82a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>,
    mail: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>,
    heart: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>,
    clipboard: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>,
    send: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>,
    info: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>,
    message: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>,
    chart: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>,
    download: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>,
    lock: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>,
    shield: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>,
    settings: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>,
    activity: <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>,
  }
  return icons[name] || <span />
}

function Avatar({ ini, color, size = 36 }: { ini: string; color: string; size?: number }) {
  return (
    <div style={{ width: size, height: size, borderRadius: '50%', background: color, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0, fontSize: size * 0.36, fontWeight: 600, color: '#fff', letterSpacing: '0.02em' }}>
      {ini}
    </div>
  )
}

const estadoLabels: Record<string, string> = {
  'Activa': 'En curso',
}

function StatusBadge({ estado }: { estado: string }) {
  const map: Record<string, { bg: string; color: string; dot: string }> = {
    'Activa': { bg: '#e8f5e9', color: '#2e7d32', dot: '#27ae60' },
    'Por iniciar': { bg: '#fff3e0', color: '#e65100', dot: '#e67e22' },
    'Por terminar': { bg: '#fff8e1', color: '#f57f17', dot: '#f39c12' },
    'Finalizada': { bg: '#e3f2fd', color: '#1565c0', dot: '#2980b9' },
    'Cancelada': { bg: '#fdecea', color: '#c0392b', dot: '#e74c3c' },
  }
  const s = map[estado] || { bg: '#f5f5f5', color: '#555', dot: '#aaa' }
  return (
    <span style={{ display: 'inline-flex', alignItems: 'center', gap: 5, padding: '3px 10px', borderRadius: 20, background: s.bg, color: s.color, fontSize: 12, fontWeight: 600 }}>
      <span style={{ width: 6, height: 6, borderRadius: '50%', background: s.dot, flexShrink: 0 }} />
      {estadoLabels[estado] || estado}
    </span>
  )
}

function Sidebar({ page, setPage }: { page: Page; setPage: (p: Page) => void }) {
  return (
    <aside style={{ width: 220, background: '#1a2744', display: 'flex', flexDirection: 'column', minHeight: '100vh', flexShrink: 0, position: 'relative' }}>
      <div style={{ padding: '18px 16px 14px', borderBottom: '1px solid rgba(255,255,255,0.08)' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
          <div style={{ width: 40, height: 40, background: '#c0392b', borderRadius: 6, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
          </div>
          <div>
            <div style={{ color: '#fff', fontSize: 10, fontWeight: 700, lineHeight: 1.3 }}>Corporación de<br/>Asistencia Judicial</div>
            <div style={{ color: '#c0392b', fontSize: 9, fontWeight: 600, letterSpacing: '0.08em', marginTop: 1 }}>BIOBÍO</div>
          </div>
        </div>
      </div>
      <nav style={{ padding: '16px 10px', flex: 1 }}>
        <div style={{ color: 'rgba(255,255,255,0.4)', fontSize: 10, fontWeight: 700, letterSpacing: '0.1em', padding: '0 8px', marginBottom: 8 }}>PRINCIPAL</div>
        {navItems.map(item => {
          const active = page === item.id
          return (
            <button key={item.id} onClick={() => setPage(item.id)}
              style={{ width: '100%', display: 'flex', alignItems: 'center', gap: 10, padding: '9px 10px', borderRadius: 7, border: 'none', cursor: 'pointer', background: active ? 'rgba(255,255,255,0.12)' : 'transparent', color: active ? '#fff' : 'rgba(255,255,255,0.65)', fontSize: 13.5, fontWeight: active ? 600 : 400, marginBottom: 2, transition: 'all 0.15s', textAlign: 'left', position: 'relative' }}
              onMouseEnter={e => { if (!active) (e.currentTarget as HTMLElement).style.background = 'rgba(255,255,255,0.06)' }}
              onMouseLeave={e => { if (!active) (e.currentTarget as HTMLElement).style.background = 'transparent' }}
            >
              {active && <span style={{ position: 'absolute', left: 0, top: '50%', transform: 'translateY(-50%)', width: 3, height: 28, background: '#c0392b', borderRadius: '0 2px 2px 0' }} />}
              <Icon name={item.icon} size={15} />
              <span style={{ flex: 1 }}>{item.label}</span>
              {item.badge && <span style={{ background: '#c0392b', color: '#fff', fontSize: 10, fontWeight: 700, padding: '1px 6px', borderRadius: 10 }}>{item.badge}</span>}
            </button>
          )
        })}

        <div style={{ marginTop: 20, borderTop: '1px solid rgba(255,255,255,0.08)', paddingTop: 14 }}>
          <div style={{ color: 'rgba(255,255,255,0.4)', fontSize: 10, fontWeight: 700, letterSpacing: '0.1em', padding: '0 8px', marginBottom: 8 }}>ACCESO RÁPIDO</div>
          {vistasRapidas.map((vista, i) => {
            const active = page === vista.id
            return (
              <button key={vista.id} onClick={() => setPage(vista.id)}
                style={{ width: '100%', display: 'flex', alignItems: 'center', gap: 10, padding: '9px 10px', borderRadius: 7, border: 'none', cursor: 'pointer', background: active ? 'rgba(255,255,255,0.12)' : 'transparent', color: active ? '#fff' : 'rgba(255,255,255,0.65)', fontSize: 13.5, fontWeight: active ? 600 : 400, transition: 'all 0.15s', textAlign: 'left', position: 'relative', marginTop: i === 0 ? 0 : 4 }}
                onMouseEnter={e => { if (!active) (e.currentTarget as HTMLElement).style.background = 'rgba(255,255,255,0.06)' }}
                onMouseLeave={e => { if (!active) (e.currentTarget as HTMLElement).style.background = 'transparent' }}
              >
                {active && <span style={{ position: 'absolute', left: 0, top: '50%', transform: 'translateY(-50%)', width: 3, height: 28, background: '#c0392b', borderRadius: '0 2px 2px 0' }} />}
                <Icon name={vista.icon} size={15} />
                <span style={{ flex: 1 }}>{vista.label}</span>
              </button>
            )
          })}
        </div>
      </nav>
      <div style={{ padding: '12px 16px', borderTop: '1px solid rgba(255,255,255,0.08)' }}>
        <div style={{ color: 'rgba(255,255,255,0.35)', fontSize: 10, lineHeight: 1.5 }}>Ministerio de Justicia<br/>y Derechos Humanos</div>
        <div style={{ color: 'rgba(255,255,255,0.3)', fontSize: 10, marginTop: 6, letterSpacing: '0.04em' }}>Versión {APP_VERSION}</div>
      </div>
    </aside>
  )
}

const RegionContext = createContext<string>(regionesOperativas[0])
const useRegion = () => useContext(RegionContext)

function SelectorRegion({ region, setRegion }: { region: string; setRegion: (r: string) => void }) {
  const [abierto, setAbierto] = useState(false)

  return (
    <div style={{ position: 'relative' }}>
      <button onClick={() => setAbierto(a => !a)} title="Cambiar la región que administra"
        style={{ display: 'flex', alignItems: 'center', gap: 8, background: 'rgba(255,255,255,0.12)', border: 'none', color: '#fff', padding: '7px 12px', borderRadius: 8, cursor: 'pointer', fontSize: 13, fontWeight: 600, fontFamily: 'Inter, sans-serif' }}>
        <Icon name="map_pin" size={14} />
        {region}
        <span style={{ display: 'flex', opacity: 0.7, transform: abierto ? 'rotate(180deg)' : 'none', transition: 'transform 0.15s' }}><Icon name="chevron_right" size={13} /></span>
      </button>

      {abierto && (
        <>
          <div onClick={() => setAbierto(false)} style={{ position: 'fixed', inset: 0, zIndex: 900 }} />
          <div style={{ position: 'absolute', top: 'calc(100% + 8px)', right: 0, background: '#fff', borderRadius: 10, border: '1px solid #e9ecef', boxShadow: '0 12px 32px rgba(0,0,0,0.18)', minWidth: 280, zIndex: 901, overflow: 'hidden' }}>
            <div style={{ padding: '10px 14px', borderBottom: '1px solid #f1f3f5', color: '#6c757d', fontSize: 11, fontWeight: 700, letterSpacing: '0.05em' }}>REGIÓN QUE ADMINISTRA</div>
            {regionesOperativas.map(r => {
              const activa = r === region
              const cantidad = consultorios.filter(c => c.region === r).length
              return (
                <button key={r} onClick={() => { setRegion(r); setAbierto(false) }}
                  style={{ width: '100%', display: 'flex', alignItems: 'center', gap: 10, padding: '11px 14px', border: 'none', background: activa ? '#f1f5fb' : '#fff', cursor: 'pointer', textAlign: 'left', fontFamily: 'Inter, sans-serif' }}>
                  <span style={{ display: 'flex', color: activa ? '#2980b9' : '#ced4da', flexShrink: 0 }}><Icon name={activa ? 'check' : 'map_pin'} size={14} /></span>
                  <span style={{ flex: 1, minWidth: 0 }}>
                    <span style={{ display: 'block', color: '#1a2744', fontSize: 13, fontWeight: activa ? 700 : 500 }}>{r}</span>
                    <span style={{ display: 'block', color: '#adb5bd', fontSize: 11, marginTop: 1 }}>{cantidad} consultorio{cantidad === 1 ? '' : 's'} · {secretariaPorRegion[r]?.nombre ?? 'Sin secretaría asignada'}</span>
                  </span>
                </button>
              )
            })}
          </div>
        </>
      )}
    </div>
  )
}

function Header({ title, notifCount = 0, onNotif, usuario = 'Secretaria', usuarioIni = 'SE', region, setRegion }: { title: string; notifCount?: number; onNotif?: () => void; usuario?: string; usuarioIni?: string; region?: string; setRegion?: (r: string) => void }) {
  return (
    <header style={{ background: '#1a2744', borderBottom: '3px solid #c0392b', padding: '0 28px', height: 56, display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexShrink: 0 }}>
      <span style={{ color: '#fff', fontSize: 16, fontWeight: 600 }}>{title}</span>
      <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
        {region && setRegion && <SelectorRegion region={region} setRegion={setRegion} />}
        {onNotif && (
          <button onClick={onNotif} style={{ position: 'relative', border: 'none', background: 'rgba(255,255,255,0.1)', color: '#fff', width: 36, height: 36, borderRadius: 8, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <Icon name="bell" size={17} />
            {notifCount > 0 && <span style={{ position: 'absolute', top: 6, right: 7, width: 8, height: 8, background: '#c0392b', borderRadius: '50%', border: '1.5px solid #1a2744' }} />}
          </button>
        )}
        <span style={{ color: 'rgba(255,255,255,0.75)', fontSize: 13 }}>{usuario}</span>
        <div style={{ width: 34, height: 34, borderRadius: '50%', background: '#c0392b', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#fff', fontWeight: 700, fontSize: 13 }}>{usuarioIni}</div>
      </div>
    </header>
  )
}

function Breadcrumb({ items }: { items: string[] }) {
  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: 6, marginTop: 4 }}>
      {items.map((item, i) => (
        <span key={i} style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
          {i > 0 && <span style={{ color: '#adb5bd', fontSize: 12 }}>/</span>}
          <span style={{ color: i === items.length - 1 ? '#c0392b' : '#6c757d', fontSize: 12.5 }}>{item}</span>
        </span>
      ))}
    </div>
  )
}

function Card({ children, style }: { children: React.ReactNode; style?: React.CSSProperties }) {
  return (
    <div style={{ background: '#fff', borderRadius: 10, border: '1px solid #e9ecef', padding: 20, ...style }}>
      {children}
    </div>
  )
}

function PanelGeneral({ setPage }: { setPage: (p: Page) => void }) {
  const region = useRegion()
  const postulacionesRegion = postulaciones.filter(p => regionDeConsultorioId(p.consultorioId) === region)
  const practicasRegion = practicas.filter(p => regionDeConsultorioNombre(p.consultorio) === region)

  const stats = [
    { label: 'POSTULACIONES PENDIENTES', value: postulacionesRegion.length, sub: 'Por revisar', icon: 'doc', color: '#2980b9', bg: '#e8f4fd' },
    { label: 'PRÁCTICAS ACTIVAS', value: practicasRegion.filter(p => p.estado === 'Activa').length, sub: 'En curso', icon: 'check', color: '#27ae60', bg: '#e8f5e9' },
    { label: 'POR TERMINAR', value: practicasRegion.filter(p => p.estado === 'Por terminar').length, sub: 'Próximos 30 días', icon: 'calendar', color: '#e67e22', bg: '#fff3e0' },
  ]
  return (
    <div style={{ padding: '28px 28px 40px' }}>
      <h1 style={{ fontSize: 24, fontWeight: 700, color: '#1a2744', margin: 0 }}>Panel General</h1>
      <Breadcrumb items={['Inicio', region, 'Panel General']} />
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 16, marginTop: 24 }}>
        {stats.map((s, i) => (
          <Card key={i} style={{ display: 'flex', alignItems: 'center', gap: 16, padding: '20px 24px' }}>
            <div style={{ width: 48, height: 48, borderRadius: 10, background: s.bg, display: 'flex', alignItems: 'center', justifyContent: 'center', color: s.color, flexShrink: 0 }}><Icon name={s.icon} size={22} /></div>
            <div>
              <div style={{ color: '#6c757d', fontSize: 10.5, fontWeight: 700, letterSpacing: '0.07em' }}>{s.label}</div>
              <div style={{ color: '#1a2744', fontSize: 32, fontWeight: 700, lineHeight: 1.1 }}>{s.value}</div>
              <div style={{ color: '#adb5bd', fontSize: 12 }}>{s.sub}</div>
            </div>
          </Card>
        ))}
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16, marginTop: 16 }}>
        <Card>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}><Icon name="doc" size={15} /><span style={{ fontWeight: 600, color: '#1a2744', fontSize: 14 }}>Últimas postulaciones</span></div>
            <button onClick={() => setPage('postulaciones')} style={{ border: 'none', background: '#f1f3f5', color: '#495057', fontSize: 12, padding: '4px 12px', borderRadius: 6, cursor: 'pointer', fontWeight: 500 }}>Ver todas</button>
          </div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
            {postulacionesRegion.length === 0 && <div style={{ textAlign: 'center', padding: '18px 0', color: '#adb5bd', fontSize: 13 }}>Sin postulaciones en {region}</div>}
            {postulacionesRegion.slice(0, 2).map(p => (
              <div key={p.id} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '10px 12px', background: '#f8f9fa', borderRadius: 8 }}>
                <Avatar ini={p.ini} color={p.color} size={34} />
                <div style={{ flex: 1 }}><div style={{ fontWeight: 600, color: '#1a2744', fontSize: 13.5 }}>{p.nombre}</div><div style={{ color: '#6c757d', fontSize: 12 }}>{p.universidad} · {p.año} año</div></div>
                <span style={{ color: '#adb5bd', fontSize: 12 }}>{p.fecha}</span>
              </div>
            ))}
          </div>
        </Card>
        <Card>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}><Icon name="clock" size={15} /><span style={{ fontWeight: 600, color: '#1a2744', fontSize: 14 }}>Prácticas por terminar</span></div>
            <button onClick={() => setPage('practicas')} style={{ border: 'none', background: '#f1f3f5', color: '#495057', fontSize: 12, padding: '4px 12px', borderRadius: 6, cursor: 'pointer', fontWeight: 500 }}>Ver todas</button>
          </div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
            {practicasRegion.filter(p => p.estado !== 'Finalizada' && p.estado !== 'Cancelada').slice(0, 3).length === 0 && (
              <div style={{ textAlign: 'center', padding: '18px 0', color: '#adb5bd', fontSize: 13 }}>Sin prácticas próximas a terminar en {region}</div>
            )}
            {practicasRegion.filter(p => p.estado !== 'Finalizada' && p.estado !== 'Cancelada').slice(0, 3).map(p => (
              <div key={p.id} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '10px 12px', background: '#f8f9fa', borderRadius: 8 }}>
                <Avatar ini={p.practicante.split(' ').map(n => n[0]).join('').slice(0, 2)} color={p.uniColor} size={34} />
                <div style={{ flex: 1 }}><div style={{ fontWeight: 600, color: '#1a2744', fontSize: 13.5 }}>{p.practicante}</div><div style={{ color: '#6c757d', fontSize: 12 }}>Tutor: {p.abogado}</div></div>
                <span style={{ color: '#c0392b', fontWeight: 600, fontSize: 12 }}>Termina {p.termino}</span>
              </div>
            ))}
          </div>
        </Card>
      </div>
    </div>
  )
}

type NotaPostulacion = { id: number; autor: string; region: string; fecha: string; texto: string }

const notasIniciales: Record<number, NotaPostulacion[]> = {
  2: [{
    id: 1,
    autor: 'Secretaría Biobío',
    region: 'Región del Biobío',
    fecha: '21/07/2026 10:42',
    texto: 'El postulante indicó por teléfono que solo tiene disponibilidad en jornada de mañana. Considerarlo al asignar el consultorio.',
  }],
}

const fechaHoraActual = () => {
  const d = new Date()
  const dos = (n: number) => String(n).padStart(2, '0')
  return `${dos(d.getDate())}/${dos(d.getMonth() + 1)}/${d.getFullYear()} ${dos(d.getHours())}:${dos(d.getMinutes())}`
}

function Postulaciones() {
  const [items, setItems] = useState(postulaciones)
  const [expandedId, setExpandedId] = useState<number | null>(null)
  const [selConsultorio, setSelConsultorio] = useState<number | null>(null)
  const [busqueda, setBusqueda] = useState('')

  const activo = items.find(x => x.id === expandedId) || null

  const abrirAceptar = (p: typeof postulaciones[0]) => {
    setExpandedId(p.id)
    setSelConsultorio(p.consultorioId)
    setBusqueda('')
  }
  const cerrar = () => { setExpandedId(null); setBusqueda(''); setNuevaNota('') }
  const quitar = (id: number) => { setItems(prev => prev.filter(x => x.id !== id)); if (expandedId === id) cerrar() }

  const [detalleId, setDetalleId] = useState<number | null>(null)
  const detalle = items.find(x => x.id === detalleId) || null

  const region = useRegion()
  const visibles = items.filter(x => regionDeConsultorioId(x.consultorioId) === region)
  const [derivacion, setDerivacion] = useState<{ nombre: string; region: string; consultorio: string } | null>(null)

  const [notas, setNotas] = useState<Record<number, NotaPostulacion[]>>(notasIniciales)
  const [nuevaNota, setNuevaNota] = useState('')

  const agregarNota = (postulacionId: number) => {
    const texto = nuevaNota.trim()
    if (!texto) return
    const nota: NotaPostulacion = {
      id: Date.now(),
      autor: secretariaPorRegion[region]?.nombre ?? 'Secretaría',
      region,
      fecha: fechaHoraActual(),
      texto,
    }
    setNotas(prev => ({ ...prev, [postulacionId]: [...(prev[postulacionId] ?? []), nota] }))
    setNuevaNota('')
  }

  const eliminarNota = (postulacionId: number, notaId: number) =>
    setNotas(prev => ({ ...prev, [postulacionId]: (prev[postulacionId] ?? []).filter(n => n.id !== notaId) }))

  const confirmarIngreso = (p: typeof postulaciones[0]) => {
    const destino = consultorios.find(c => c.id === selConsultorio)
    if (destino && destino.region !== region) {
      setItems(prev => prev.map(x => x.id === p.id ? { ...x, consultorioId: destino.id } : x))
      setDerivacion({ nombre: p.nombre, region: destino.region, consultorio: destino.nombre })
      cerrar()
      return
    }
    setDerivacion(null)
    quitar(p.id)
  }

  return (
    <div style={{ padding: '28px 28px 40px' }}>
      <h1 style={{ fontSize: 24, fontWeight: 700, color: '#1a2744', margin: 0 }}>Postulaciones</h1>
      <Breadcrumb items={['Inicio', region, 'Postulaciones pendientes']} />

      {derivacion && (
        <div style={{ display: 'flex', alignItems: 'flex-start', gap: 10, background: '#fff8ec', border: '1px solid #f5dbb0', borderRadius: 10, padding: '12px 16px', marginTop: 16, color: '#8a5a12', fontSize: 13 }}>
          <span style={{ display: 'flex', marginTop: 1, flexShrink: 0 }}><Icon name="send" size={15} /></span>
          <span style={{ flex: 1 }}>
            <strong>{derivacion.nombre}</strong> fue derivado a {derivacion.consultorio} ({derivacion.region}).
            Ahora la postulación es visible para {secretariaPorRegion[derivacion.region]?.nombre ?? 'la secretaría de esa región'} y ya no aparece en su listado.
          </span>
          <button onClick={() => setDerivacion(null)} style={{ border: 'none', background: 'transparent', color: '#b98a3d', cursor: 'pointer', display: 'flex', flexShrink: 0 }}><Icon name="x" size={14} /></button>
        </div>
      )}
      <Card style={{ marginTop: 24 }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}><Icon name="doc" size={15} /><span style={{ fontWeight: 600, color: '#1a2744', fontSize: 15 }}>Postulaciones por revisar</span></div>
          <span style={{ color: '#6c757d', fontSize: 13 }}>{visibles.length} pendientes en {region}</span>
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
          {visibles.length === 0 && <div style={{ textAlign: 'center', padding: '40px 0', color: '#adb5bd', fontSize: 14 }}>No hay postulaciones pendientes en {region}</div>}
          {visibles.map(p => (
            <div key={p.id} onClick={() => setDetalleId(p.id)} title="Ver ficha y archivos del postulante"
              style={{ display: 'flex', alignItems: 'center', gap: 14, padding: '14px 16px', background: '#f8f9fa', borderRadius: 9, border: '1px solid #f1f3f5', cursor: 'pointer', transition: 'background 0.15s, border-color 0.15s' }}
              onMouseEnter={e => { e.currentTarget.style.background = '#f1f5fb'; e.currentTarget.style.borderColor = '#d6e2f5' }}
              onMouseLeave={e => { e.currentTarget.style.background = '#f8f9fa'; e.currentTarget.style.borderColor = '#f1f3f5' }}>
              <Avatar ini={p.ini} color={p.color} size={40} />
              <div style={{ flex: 1 }}>
                <div style={{ fontWeight: 700, color: '#1a2744', fontSize: 14.5 }}>{p.nombre}</div>
                <div style={{ color: '#495057', fontSize: 12.5, marginTop: 2 }}><span style={{ fontWeight: 600 }}>RUT:</span> {p.rut} &nbsp;<span style={{ fontWeight: 600 }}>Universidad:</span> {p.universidad} &nbsp;<span style={{ fontWeight: 600 }}>Año:</span> {p.año}</div>
                <div style={{ color: '#6c757d', fontSize: 12, marginTop: 2 }}>{p.email} &nbsp; {p.tel}</div>
              </div>
              <span style={{ color: '#adb5bd', fontSize: 12, marginRight: 12 }}>{p.fecha}</span>
              <button onClick={e => { e.stopPropagation(); abrirAceptar(p) }} style={{ background: '#27ae60', color: '#fff', border: 'none', padding: '7px 16px', borderRadius: 6, fontWeight: 600, fontSize: 13, cursor: 'pointer' }}>Aceptar</button>
              <button onClick={e => { e.stopPropagation(); quitar(p.id) }} style={{ background: '#fff', color: '#c0392b', border: '1.5px solid #c0392b', padding: '7px 16px', borderRadius: 6, fontWeight: 600, fontSize: 13, cursor: 'pointer' }}>Rechazar</button>
            </div>
          ))}
        </div>
      </Card>

      {/* Ficha superpuesta del postulante con sus archivos */}
      {detalle && (() => {
        const p = detalle
        const postulado = consultorios.find(c => c.id === p.consultorioId)
        const archivos = archivosPostulacion[p.id] ?? []
        const universitarios = archivos.filter(a => a.categoria === 'universitario')
        const cedula = archivos.filter(a => a.categoria === 'cedula')

        const filaArchivo = (a: ArchivoPostulacion) => (
          <div key={a.nombre} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '10px 12px', background: '#fff', border: '1px solid #e9ecef', borderRadius: 8 }}>
            <span style={{ color: a.categoria === 'cedula' ? '#8e6dbf' : '#1a2744', display: 'flex', flexShrink: 0 }}><Icon name="doc" size={17} /></span>
            <div style={{ flex: 1, minWidth: 0 }}>
              <div style={{ color: '#1a2744', fontSize: 13, fontWeight: 600, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{a.nombre}</div>
              <div style={{ color: '#6c757d', fontSize: 11.5, marginTop: 1 }}>{a.tipo} · {a.peso} · subido el {a.fecha}</div>
            </div>
            <button style={{ border: '1.5px solid #dee2e6', background: '#fff', color: '#495057', padding: '6px 12px', borderRadius: 6, fontWeight: 600, fontSize: 12, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6, flexShrink: 0 }}>
              <Icon name="eye" size={13} /> Ver
            </button>
            <button style={{ border: '1.5px solid #dee2e6', background: '#fff', color: '#495057', padding: '6px 12px', borderRadius: 6, fontWeight: 600, fontSize: 12, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6, flexShrink: 0 }}>
              <Icon name="download" size={13} /> Descargar
            </button>
          </div>
        )

        return (
          <div onClick={() => setDetalleId(null)} style={{ position: 'fixed', inset: 0, background: 'rgba(26,39,68,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000, padding: 20 }}>
            <div onClick={e => e.stopPropagation()} style={{ background: '#fff', borderRadius: 12, width: '100%', maxWidth: 720, maxHeight: '88vh', display: 'flex', flexDirection: 'column', boxShadow: '0 20px 60px rgba(0,0,0,0.35)', overflow: 'hidden' }}>
              {/* Header: identificación y contacto */}
              <div style={{ background: '#1a2744', padding: '20px 24px', flexShrink: 0 }}>
                <div style={{ display: 'flex', alignItems: 'flex-start', gap: 14 }}>
                  <Avatar ini={p.ini} color={p.color} size={46} />
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <div style={{ color: '#fff', fontSize: 17, fontWeight: 700 }}>{p.nombre}</div>
                    <div style={{ color: 'rgba(255,255,255,0.65)', fontSize: 12.5, marginTop: 2 }}>Postulación recibida el {p.fecha}</div>
                  </div>
                  <button onClick={() => setDetalleId(null)} style={{ border: 'none', background: 'rgba(255,255,255,0.12)', color: '#fff', width: 32, height: 32, borderRadius: 8, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}><Icon name="x" size={15} /></button>
                </div>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 12, marginTop: 16 }}>
                  {[
                    { icon: 'doc', label: 'RUT', value: p.rut },
                    { icon: 'phone', label: 'Número de teléfono', value: p.tel },
                    { icon: 'mail', label: 'Correo electrónico', value: p.email },
                    { icon: 'building', label: 'Consultorio al que postuló', value: postulado?.nombre || '—' },
                  ].map(f => (
                    <div key={f.label}>
                      <div style={{ color: 'rgba(255,255,255,0.5)', fontSize: 10.5, fontWeight: 700, letterSpacing: '0.05em', textTransform: 'uppercase', marginBottom: 3 }}>{f.label}</div>
                      <div style={{ color: '#fff', fontSize: 13, fontWeight: 500, display: 'flex', alignItems: 'center', gap: 6, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                        <span style={{ display: 'flex', opacity: 0.6, flexShrink: 0 }}><Icon name={f.icon} size={13} /></span>{f.value}
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Body: archivos subidos */}
              <div style={{ padding: '20px 24px', overflowY: 'auto', background: '#f8f9fa' }}>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 12, fontWeight: 700, color: '#1a2744', letterSpacing: '0.05em' }}>
                    <Icon name="upload" size={14} /> ARCHIVOS SUBIDOS POR EL POSTULANTE
                  </div>
                  <span style={{ color: '#6c757d', fontSize: 12 }}>{archivos.length} archivo{archivos.length === 1 ? '' : 's'}</span>
                </div>

                {archivos.length === 0 && (
                  <div style={{ textAlign: 'center', padding: '30px 0', color: '#adb5bd', fontSize: 13.5 }}>El postulante no adjuntó archivos</div>
                )}

                {universitarios.length > 0 && (
                  <>
                    <div style={{ color: '#6c757d', fontSize: 11.5, fontWeight: 700, letterSpacing: '0.04em', marginBottom: 8 }}>DOCUMENTOS UNIVERSITARIOS</div>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 8, marginBottom: 18 }}>{universitarios.map(filaArchivo)}</div>
                  </>
                )}

                {cedula.length > 0 && (
                  <>
                    <div style={{ color: '#6c757d', fontSize: 11.5, fontWeight: 700, letterSpacing: '0.04em', marginBottom: 8 }}>CÉDULA DE IDENTIDAD</div>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>{cedula.map(filaArchivo)}</div>
                    {cedula.length < 2 && (
                      <div style={{ display: 'flex', alignItems: 'center', gap: 8, background: '#fdf3f2', border: '1px solid #f2d6d2', borderRadius: 8, padding: '10px 12px', marginTop: 10, color: '#a5342a', fontSize: 12.5 }}>
                        <Icon name="alert" size={14} /> Falta uno de los lados de la cédula; no es posible validar la identidad.
                      </div>
                    )}
                  </>
                )}
              </div>

              {/* Footer: acciones sobre la postulación */}
              <div style={{ padding: '14px 24px', borderTop: '1px solid #e9ecef', display: 'flex', justifyContent: 'flex-end', gap: 10, flexShrink: 0 }}>
                <button onClick={() => setDetalleId(null)} style={{ background: '#fff', color: '#495057', border: '1.5px solid #dee2e6', padding: '9px 18px', borderRadius: 8, fontWeight: 600, fontSize: 13.5, cursor: 'pointer' }}>Cerrar</button>
                <button onClick={() => { setDetalleId(null); quitar(p.id) }} style={{ background: '#fff', color: '#c0392b', border: '1.5px solid #c0392b', padding: '9px 18px', borderRadius: 8, fontWeight: 600, fontSize: 13.5, cursor: 'pointer' }}>Rechazar</button>
                <button onClick={() => { setDetalleId(null); abrirAceptar(p) }} style={{ background: '#27ae60', color: '#fff', border: 'none', padding: '9px 20px', borderRadius: 8, fontWeight: 700, fontSize: 13.5, cursor: 'pointer' }}>Aceptar</button>
              </div>
            </div>
          </div>
        )
      })()}

      {/* Modal superpuesto de aceptación / asignación */}
      {activo && (() => {
        const p = activo
        const postulado = consultorios.find(c => c.id === p.consultorioId)
        const q = busqueda.trim().toLowerCase()
        const propios = consultorios.filter(c => c.region === region && c.id !== postulado?.id)
        const otros = consultorios.filter(c => c.region !== region && c.id !== postulado?.id)
        const ordenados = [...(postulado ? [postulado] : []), ...propios, ...otros]
        const lista = q ? ordenados.filter(c => c.nombre.toLowerCase().includes(q) || c.direccion.toLowerCase().includes(q) || c.region.toLowerCase().includes(q)) : ordenados
        const destino = consultorios.find(c => c.id === selConsultorio)
        const redirigido = selConsultorio !== p.consultorioId
        const otraRegion = !!destino && destino.region !== region
        const notasDeLaPostulacion = notas[p.id] ?? []
        return (
          <div onClick={cerrar} style={{ position: 'fixed', inset: 0, background: 'rgba(26,39,68,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000, padding: 20 }}>
            <div onClick={e => e.stopPropagation()} style={{ background: '#fff', borderRadius: 12, width: '100%', maxWidth: 720, maxHeight: '88vh', display: 'flex', flexDirection: 'column', boxShadow: '0 20px 60px rgba(0,0,0,0.35)', overflow: 'hidden' }}>
              {/* Header */}
              <div style={{ background: '#1a2744', padding: '18px 24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexShrink: 0 }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                  <Avatar ini={p.ini} color={p.color} size={38} />
                  <div>
                    <div style={{ color: '#fff', fontSize: 16, fontWeight: 700 }}>Aceptar postulación</div>
                    <div style={{ color: 'rgba(255,255,255,0.65)', fontSize: 12.5 }}>{p.nombre}</div>
                  </div>
                </div>
                <button onClick={cerrar} style={{ border: 'none', background: 'rgba(255,255,255,0.12)', color: '#fff', width: 32, height: 32, borderRadius: 8, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}><Icon name="x" size={15} /></button>
              </div>

              {/* Body scrolleable */}
              <div style={{ padding: '20px 24px', overflowY: 'auto' }}>
                <div style={{ fontSize: 12, fontWeight: 700, color: '#1a2744', letterSpacing: '0.05em', marginBottom: 12 }}>DATOS DEL POSTULANTE</div>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 12, marginBottom: 22 }}>
                  {[
                    { label: 'Nombre del postulante', value: p.nombre },
                    { label: 'Número de teléfono', value: p.tel },
                    { label: 'Correo electrónico', value: p.email },
                    { label: 'Especialidad', value: p.especialidad },
                    { label: 'Consultorio al que postuló', value: postulado?.nombre || '—' },
                  ].map(f => (
                    <div key={f.label}>
                      <div style={{ color: '#6c757d', fontSize: 11, fontWeight: 600, marginBottom: 2 }}>{f.label}</div>
                      <div style={{ color: '#1a2744', fontSize: 13, fontWeight: 500 }}>{f.value}</div>
                    </div>
                  ))}
                </div>

                {/* Notas dejadas sobre esta postulación, visibles antes de asignar consultorio */}
                {notasDeLaPostulacion.length > 0 && (
                  <div style={{ background: '#fff8ec', border: '1px solid #f5dbb0', borderRadius: 10, padding: '14px 16px', marginBottom: 22 }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 12, fontWeight: 700, color: '#8a5a12', letterSpacing: '0.05em', marginBottom: 10 }}>
                      <Icon name="message" size={14} /> NOTAS A CONSIDERAR ({notasDeLaPostulacion.length})
                    </div>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                      {notasDeLaPostulacion.map(n => (
                        <div key={n.id} style={{ background: '#fff', border: '1px solid #f1e4cc', borderRadius: 8, padding: '10px 12px' }}>
                          <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 4 }}>
                            <span style={{ color: '#1a2744', fontSize: 12.5, fontWeight: 700 }}>{n.autor}</span>
                            <span style={{ color: '#adb5bd', fontSize: 11 }}>{n.region} · {n.fecha}</span>
                            {n.region === region && (
                              <button onClick={() => eliminarNota(p.id, n.id)} title="Eliminar nota"
                                style={{ marginLeft: 'auto', border: 'none', background: 'transparent', color: '#ced4da', cursor: 'pointer', display: 'flex' }}>
                                <Icon name="x" size={13} />
                              </button>
                            )}
                          </div>
                          <div style={{ color: '#495057', fontSize: 13, lineHeight: 1.5 }}>{n.texto}</div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 12, fontWeight: 700, color: '#1a2744', letterSpacing: '0.05em', marginBottom: 4 }}>
                  <Icon name="building" size={14} /> ASIGNAR CONSULTORIO
                </div>
                <div style={{ color: '#6c757d', fontSize: 12.5, marginBottom: 12 }}>Puede redirigir la postulación a otro consultorio. El consultorio al que postuló aparece primero.</div>
                <div style={{ position: 'relative', marginBottom: 12 }}>
                  <span style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: '#adb5bd' }}><Icon name="search" size={15} /></span>
                  <input value={busqueda} onChange={e => setBusqueda(e.target.value)} placeholder="Buscar consultorio por nombre o dirección..." style={{ ...inputStyle, paddingLeft: 36, background: '#f8f9fa' }} />
                </div>

                {lista.length === 0 ? (
                  <div style={{ textAlign: 'center', padding: '20px 0', color: '#adb5bd', fontSize: 13 }}>No se encontraron consultorios con ese criterio.</div>
                ) : (
                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(240px, 1fr))', gap: 10 }}>
                    {lista.map(c => {
                      const nv = nivelDisponibilidad(c.practicantes, c.capacidad)
                      const sel = selConsultorio === c.id
                      const esPostulado = c.id === p.consultorioId
                      return (
                        <div key={c.id} onClick={() => setSelConsultorio(c.id)}
                          style={{ border: `1.5px solid ${sel ? '#1a2744' : '#e9ecef'}`, borderRadius: 9, padding: '12px 14px', cursor: 'pointer', background: '#fff', display: 'flex', alignItems: 'flex-start', gap: 10 }}>
                          <div style={{ width: 16, height: 16, borderRadius: '50%', border: `2px solid ${sel ? '#1a2744' : '#ced4da'}`, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0, marginTop: 2 }}>
                            {sel && <span style={{ width: 8, height: 8, borderRadius: '50%', background: '#1a2744' }} />}
                          </div>
                          <div style={{ flex: 1 }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 6, flexWrap: 'wrap' }}>
                              <span style={{ fontWeight: 700, color: '#1a2744', fontSize: 13 }}>{c.nombre}</span>
                              {esPostulado && <span style={{ background: '#e8f4fd', color: '#1565c0', fontSize: 9.5, fontWeight: 700, letterSpacing: '0.03em', padding: '1px 6px', borderRadius: 20 }}>POSTULÓ AQUÍ</span>}
                              {c.region !== region && <span style={{ background: '#fff3e0', color: '#e65100', fontSize: 9.5, fontWeight: 700, letterSpacing: '0.03em', padding: '1px 6px', borderRadius: 20 }}>OTRA REGIÓN</span>}
                            </div>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 4, color: '#adb5bd', fontSize: 11.5, marginTop: 3 }}><Icon name="map_pin" size={11} /> {c.direccion}</div>
                            <div style={{ color: '#adb5bd', fontSize: 11, marginTop: 2 }}>{c.region}</div>
                            <span style={{ display: 'inline-flex', alignItems: 'center', gap: 5, background: nv.bg, color: nv.color, fontSize: 10.5, fontWeight: 700, padding: '2px 9px', borderRadius: 20, marginTop: 7 }}>
                              <span style={{ width: 6, height: 6, borderRadius: '50%', background: nv.accent, flexShrink: 0 }} /> {nv.label}
                            </span>
                          </div>
                        </div>
                      )
                    })}
                  </div>
                )}

                {/* Comentario de la secretaria: queda adjunto a la postulación aunque se derive */}
                <div style={{ marginTop: 22, paddingTop: 18, borderTop: '1px solid #f1f3f5' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 12, fontWeight: 700, color: '#1a2744', letterSpacing: '0.05em', marginBottom: 4 }}>
                    <Icon name="message" size={14} /> COMENTARIOS
                  </div>
                  <div style={{ color: '#6c757d', fontSize: 12.5, marginBottom: 10 }}>
                    Deje una anotación si redirige la postulación. La nota acompaña al postulante y la verá la secretaría que lo reciba.
                  </div>
                  <textarea
                    value={nuevaNota}
                    onChange={e => setNuevaNota(e.target.value)}
                    placeholder="Ej.: Se deriva por falta de cupos en el consultorio al que postuló; tiene disponibilidad solo en jornada de mañana."
                    rows={3}
                    style={{ ...inputStyle, resize: 'vertical', lineHeight: 1.5, background: '#f8f9fa' }}
                  />
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 10, marginTop: 8 }}>
                    <span style={{ color: '#adb5bd', fontSize: 11.5 }}>Se guardará como {secretariaPorRegion[region]?.nombre ?? 'Secretaría'}</span>
                    <button onClick={() => agregarNota(p.id)} disabled={!nuevaNota.trim()}
                      style={{ border: 'none', background: nuevaNota.trim() ? '#1a2744' : '#e9ecef', color: nuevaNota.trim() ? '#fff' : '#adb5bd', padding: '8px 16px', borderRadius: 7, fontWeight: 700, fontSize: 12.5, cursor: nuevaNota.trim() ? 'pointer' : 'not-allowed', display: 'flex', alignItems: 'center', gap: 7 }}>
                      <Icon name="send" size={13} /> Agregar comentario
                    </button>
                  </div>
                </div>
              </div>

              {/* Footer fijo */}
              <div style={{ borderTop: '1px solid #e9ecef', padding: '14px 24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 12, flexWrap: 'wrap', flexShrink: 0 }}>
                <span style={{ color: redirigido ? '#e65100' : '#6c757d', fontSize: 12.5, fontWeight: redirigido ? 600 : 400, maxWidth: 420 }}>
                  {otraRegion
                    ? `Se derivará a ${destino?.nombre} (${destino?.region}). Quedará a cargo de ${secretariaPorRegion[destino!.region]?.nombre ?? 'la secretaría de esa región'} y saldrá de su listado.`
                    : redirigido
                      ? `Se redirigirá a: ${destino?.nombre}`
                      : `Se ingresará al consultorio al que postuló: ${destino?.nombre}`}
                </span>
                <div style={{ display: 'flex', gap: 8 }}>
                  <button onClick={cerrar} style={{ background: '#fff', color: '#495057', border: '1.5px solid #dee2e6', padding: '8px 18px', borderRadius: 7, fontWeight: 600, fontSize: 13, cursor: 'pointer' }}>Cancelar</button>
                  <button onClick={() => confirmarIngreso(p)} style={{ background: otraRegion ? '#e67e22' : '#27ae60', color: '#fff', border: 'none', padding: '8px 18px', borderRadius: 7, fontWeight: 700, fontSize: 13, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6 }}>
                    <Icon name={otraRegion ? 'send' : 'check'} size={15} /> {otraRegion ? 'Derivar a otra región' : 'Confirmar ingreso'}
                  </button>
                </div>
              </div>
            </div>
          </div>
        )
      })()}
    </div>
  )
}

function PracticasActivas({ onVerEstadistica }: { onVerEstadistica: (p: typeof practicas[0]) => void }) {
  const [search, setSearch] = useState('')
  const [rows, setRows] = useState(practicas)
  const [editId, setEditId] = useState<number | null>(null)
  const [motivo, setMotivo] = useState('')
  const [motivoDetalle, setMotivoDetalle] = useState('')
  const region = useRegion()
  const deLaRegion = rows.filter(p => regionDeConsultorioNombre(p.consultorio) === region)
  const filtered = deLaRegion.filter(p => p.practicante.toLowerCase().includes(search.toLowerCase()))
  const editP = rows.find(r => r.id === editId) || null

  const consultoriosRegion = consultorios.filter(c => c.region === region)
  const totalCupos = consultoriosRegion.reduce((n, c) => n + c.capacidad, 0)
  const cuposLibres = consultoriosRegion.reduce((n, c) => n + Math.max(0, c.capacidad - c.practicantes), 0)

  const abrirEdit = (id: number) => { setEditId(id); setMotivo(''); setMotivoDetalle('') }
  const cerrarEdit = () => { setEditId(null); setMotivo(''); setMotivoDetalle('') }
  const cancelarPractica = (id: number) => {
    setRows(prev => prev.map(r => r.id === id ? { ...r, estado: 'Cancelada' } : r))
    cerrarEdit()
  }
  return (
    <div style={{ padding: '28px 28px 40px' }}>
      <h1 style={{ fontSize: 24, fontWeight: 700, color: '#1a2744', margin: 0 }}>Prácticas Activas</h1>
      <Breadcrumb items={['Inicio', region, 'Prácticas Activas']} />
      <Card style={{ marginTop: 24 }}>
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 10, marginBottom: 12, alignItems: 'center', justifyContent: 'space-between' }}>
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: 10, alignItems: 'center' }}>
            <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8, background: '#e3f2fd', color: '#1565c0', padding: '8px 14px', borderRadius: 999, fontSize: 12, fontWeight: 700 }}>Cupos disponibles: {cuposLibres}</span>
            <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8, background: '#f8f9fa', color: '#495057', padding: '8px 14px', borderRadius: 999, fontSize: 12, fontWeight: 700 }}>Total de cupos: {totalCupos}</span>
          </div>
          <button style={{ border: 'none', background: '#1a2744', color: '#fff', padding: '10px 18px', borderRadius: 8, fontWeight: 700, fontSize: 13, cursor: 'pointer' }}>Agregar cupos</button>
        </div>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 16, gap: 12 }}>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}><Icon name="calendar" size={15} /><span style={{ fontWeight: 600, color: '#1a2744', fontSize: 15 }}>Prácticas en curso</span></div>
            <span style={{ color: '#6c757d', fontSize: 13, maxWidth: 540 }}>Cuando hayan cupos disponibles se lo dará al próximo practicante en la lista.</span>
          </div>
          <span style={{ color: '#6c757d', fontSize: 13 }}>{filtered.length} registros</span>
        </div>
        <div style={{ position: 'relative', marginBottom: 16 }}>
          <span style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: '#adb5bd' }}><Icon name="search" size={15} /></span>
          <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Buscar practicante..." style={{ width: '100%', padding: '9px 12px 9px 36px', border: '1.5px solid #dee2e6', borderRadius: 8, fontSize: 13, outline: 'none', color: '#495057', background: '#f8f9fa' }} />
        </div>
        <div style={{ overflowX: 'auto' }}>
          <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
            <thead>
              <tr style={{ borderBottom: '2px solid #f1f3f5' }}>
                {['PRACTICANTE', 'UNIVERSIDAD', 'INICIO', 'TÉRMINO', 'ABOGADO TUTOR', 'CONSULTORIO', 'ESTADO', ''].map((h, i) => (
                  <th key={i} style={{ textAlign: 'left', padding: '8px 12px', color: '#6c757d', fontSize: 11, fontWeight: 700, letterSpacing: '0.05em' }}>{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {filtered.map((p, i) => (
                <tr key={p.id} style={{ borderBottom: '1px solid #f1f3f5', background: i % 2 === 0 ? '#fff' : '#fafafa' }}>
                  <td style={{ padding: '12px 12px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                      <span style={{ width: 8, height: 8, borderRadius: '50%', background: p.uniColor, flexShrink: 0 }} />
                      <span style={{ fontWeight: 600, color: '#1a2744' }}>{p.practicante}</span>
                    </div>
                  </td>
                  <td style={{ padding: '12px 12px', color: '#495057' }}>{p.universidad}</td>
                  <td style={{ padding: '12px 12px', color: '#495057' }}>{p.inicio}</td>
                  <td style={{ padding: '12px 12px', color: '#495057' }}>{p.termino}</td>
                  <td style={{ padding: '12px 12px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}><Avatar ini={p.abogadoIni} color={p.abogadoColor} size={26} /><span style={{ color: '#495057' }}>{p.abogado}</span></div>
                  </td>
                  <td style={{ padding: '12px 12px', color: '#6c757d', fontSize: 12 }}>{p.consultorio}</td>
                  <td style={{ padding: '12px 12px' }}><StatusBadge estado={p.estado} /></td>
                  <td style={{ padding: '12px 12px' }}>
                    <div style={{ display: 'flex', gap: 6 }}>
                      <button title="Ver detalle" style={{ border: 'none', background: '#f1f3f5', color: '#6c757d', width: 28, height: 28, borderRadius: 6, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}><Icon name="eye" size={13} /></button>
                      <button title="Ver estadísticas" onClick={() => onVerEstadistica(p)} style={{ border: 'none', background: '#e8f4fd', color: '#1565c0', width: 28, height: 28, borderRadius: 6, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}><Icon name="chart" size={13} /></button>
                      <button title="Gestionar práctica" onClick={() => abrirEdit(p.id)} style={{ border: 'none', background: '#f1f3f5', color: '#6c757d', width: 28, height: 28, borderRadius: 6, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}><Icon name="edit" size={13} /></button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: 16 }}>
          <span style={{ color: '#6c757d', fontSize: 12.5 }}>Mostrando 1-{filtered.length} de {rows.length} prácticas</span>
          <div style={{ display: 'flex', gap: 4 }}>
            {[1, 2].map(n => (<button key={n} style={{ width: 30, height: 30, border: n === 1 ? 'none' : '1px solid #dee2e6', background: n === 1 ? '#1a2744' : '#fff', color: n === 1 ? '#fff' : '#495057', borderRadius: 6, fontWeight: n === 1 ? 700 : 400, fontSize: 13, cursor: 'pointer' }}>{n}</button>))}
            <button style={{ width: 30, height: 30, border: '1px solid #dee2e6', background: '#fff', color: '#495057', borderRadius: 6, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}><Icon name="chevron_right" size={14} /></button>
          </div>
        </div>
      </Card>

      {/* Modal superpuesto — gestionar / cancelar práctica */}
      {editP && (
        <div onClick={cerrarEdit} style={{ position: 'fixed', inset: 0, background: 'rgba(26,39,68,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000, padding: 20 }}>
          <div onClick={e => e.stopPropagation()} style={{ background: '#fff', borderRadius: 12, width: '100%', maxWidth: 520, boxShadow: '0 20px 60px rgba(0,0,0,0.35)', overflow: 'hidden' }}>
            <div style={{ background: '#1a2744', padding: '18px 24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <div>
                <div style={{ color: '#fff', fontSize: 16, fontWeight: 700 }}>Gestionar práctica</div>
                <div style={{ color: 'rgba(255,255,255,0.65)', fontSize: 12.5 }}>{editP.practicante}</div>
              </div>
              <button onClick={cerrarEdit} style={{ border: 'none', background: 'rgba(255,255,255,0.12)', color: '#fff', width: 32, height: 32, borderRadius: 8, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}><Icon name="x" size={15} /></button>
            </div>
            <div style={{ padding: '22px 24px' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 18 }}>
                <span style={{ color: '#6c757d', fontSize: 13 }}>Estado actual:</span>
                <StatusBadge estado={editP.estado} />
              </div>

              {editP.estado === 'Cancelada' ? (
                <div style={{ background: '#fdecea', color: '#c0392b', borderRadius: 9, padding: '14px 16px', fontSize: 13, fontWeight: 500 }}>
                  Esta práctica ya se encuentra cancelada.
                </div>
              ) : (
                <>
                  <div style={{ fontSize: 12, fontWeight: 700, color: '#1a2744', letterSpacing: '0.05em', marginBottom: 8 }}>CANCELAR PRÁCTICA</div>
                  <div style={{ color: '#6c757d', fontSize: 12.5, marginBottom: 12 }}>Las prácticas pueden cancelarse por motivo de conducta o por decisión del practicante. Esta acción marcará la práctica como <strong>Cancelada</strong>.</div>
                  <FormField label="Motivo de la cancelación">
                    <select style={selectStyle} value={motivo} onChange={e => setMotivo(e.target.value)}>
                      <option value="">Seleccione un motivo...</option>
                      <option>Conducta del practicante</option>
                      <option>Decisión del practicante</option>
                      <option>Otro motivo</option>
                    </select>
                  </FormField>
                  {motivo === 'Otro motivo' && (
                    <div style={{ marginTop: 14 }}>
                      <FormField label="Detalle del motivo (opcional)">
                        <textarea value={motivoDetalle} onChange={e => setMotivoDetalle(e.target.value)} placeholder="Puede redactar el motivo o dejar este campo en blanco." style={{ ...inputStyle, minHeight: 80, resize: 'vertical' }} />
                      </FormField>
                    </div>
                  )}
                </>
              )}

              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 22 }}>
                <button onClick={cerrarEdit} style={{ background: '#fff', color: '#495057', border: '1.5px solid #dee2e6', padding: '8px 18px', borderRadius: 7, fontWeight: 600, fontSize: 13, cursor: 'pointer' }}>Cerrar</button>
                {editP.estado !== 'Cancelada' && (
                  <button onClick={() => motivo && cancelarPractica(editP.id)}
                    style={{ background: motivo ? '#c0392b' : '#e0a9a3', color: '#fff', border: 'none', padding: '8px 18px', borderRadius: 7, fontWeight: 700, fontSize: 13, cursor: motivo ? 'pointer' : 'not-allowed', display: 'flex', alignItems: 'center', gap: 6 }}>
                    <Icon name="x" size={15} /> Cancelar práctica
                  </button>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

function Historial() {
  const [search, setSearch] = useState('')
  const [detalle, setDetalle] = useState<typeof historial[0] | null>(null)
  const region = useRegion()
  const deLaRegion = historial.filter(p => regionDeConsultorioNombre(p.consultorio) === region)
  const filtered = deLaRegion.filter(p => p.practicante.toLowerCase().includes(search.toLowerCase()))
  return (
    <div style={{ padding: '28px 28px 40px' }}>
      <h1 style={{ fontSize: 24, fontWeight: 700, color: '#1a2744', margin: 0 }}>Historial</h1>
      <Breadcrumb items={['Inicio', region, 'Historial de prácticas']} />
      <Card style={{ marginTop: 24 }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}><Icon name="clock" size={15} /><span style={{ fontWeight: 600, color: '#1a2744', fontSize: 15 }}>Prácticas finalizadas</span></div>
          <span style={{ color: '#6c757d', fontSize: 13 }}>{deLaRegion.length} registros</span>
        </div>
        <div style={{ display: 'flex', gap: 10, marginBottom: 16 }}>
          <div style={{ position: 'relative', flex: 1 }}>
            <span style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: '#adb5bd' }}><Icon name="search" size={15} /></span>
            <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Buscar en historial..." style={{ width: '100%', padding: '9px 12px 9px 36px', border: '1.5px solid #dee2e6', borderRadius: 8, fontSize: 13, outline: 'none', color: '#495057', background: '#f8f9fa' }} />
          </div>
          <select style={{ border: '1.5px solid #dee2e6', borderRadius: 8, padding: '0 12px', fontSize: 13, color: '#495057', background: '#f8f9fa', outline: 'none' }}>
            <option>2026</option><option>2025</option><option>2024</option>
          </select>
        </div>
        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
          <thead>
            <tr style={{ borderBottom: '2px solid #f1f3f5' }}>
              {['PRACTICANTE', 'UNIVERSIDAD', 'PERIODO', 'ABOGADO TUTOR', 'ESTADO'].map((h, i) => (
                <th key={i} style={{ textAlign: 'left', padding: '8px 12px', color: '#6c757d', fontSize: 11, fontWeight: 700, letterSpacing: '0.05em' }}>{h}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {filtered.map((p, i) => (
              <tr key={p.id} onClick={() => setDetalle(p)} title="Ver resumen del practicante"
                style={{ borderBottom: '1px solid #f1f3f5', background: i % 2 === 0 ? '#fff' : '#fafafa', cursor: 'pointer' }}
                onMouseEnter={e => { e.currentTarget.style.background = '#f1f5fb' }}
                onMouseLeave={e => { e.currentTarget.style.background = i % 2 === 0 ? '#fff' : '#fafafa' }}>
                <td style={{ padding: '13px 12px', fontWeight: 700, color: '#1a2744' }}>{p.practicante}</td>
                <td style={{ padding: '13px 12px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 7 }}><span style={{ width: 7, height: 7, borderRadius: '50%', background: p.uniColor, flexShrink: 0 }} /><span style={{ color: '#495057' }}>{p.universidad}</span></div>
                </td>
                <td style={{ padding: '13px 12px', color: '#495057' }}>{p.periodo}</td>
                <td style={{ padding: '13px 12px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}><Avatar ini={p.abogadoIni} color={p.abogadoColor} size={26} /><span style={{ color: '#495057' }}>{p.abogado}</span></div>
                </td>
                <td style={{ padding: '13px 12px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                    <StatusBadge estado={p.estado} />
                    {p.cancelacion?.antesDeIniciar && (
                      <span style={{ background: '#fdecea', color: '#c0392b', fontSize: 9.5, fontWeight: 700, letterSpacing: '0.03em', padding: '2px 7px', borderRadius: 20 }}>NO INICIÓ</span>
                    )}
                    <span style={{ marginLeft: 'auto', color: '#ced4da', display: 'flex' }}><Icon name="chevron_right" size={14} /></span>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: 16 }}>
          <span style={{ color: '#6c757d', fontSize: 12.5 }}>Mostrando 1-{filtered.length} de {deLaRegion.length}</span>
          <div style={{ display: 'flex', gap: 4 }}>
            {[1, 2, 3].map(n => (<button key={n} style={{ width: 30, height: 30, border: n === 1 ? 'none' : '1px solid #dee2e6', background: n === 1 ? '#1a2744' : '#fff', color: n === 1 ? '#fff' : '#495057', borderRadius: 6, fontWeight: n === 1 ? 700 : 400, fontSize: 13, cursor: 'pointer' }}>{n}</button>))}
            <button style={{ width: 30, height: 30, border: '1px solid #dee2e6', background: '#fff', color: '#495057', borderRadius: 6, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}><Icon name="chevron_right" size={14} /></button>
          </div>
        </div>
      </Card>

      {detalle && <ResumenHistorial registro={detalle} cerrar={() => setDetalle(null)} />}
    </div>
  )
}

function ResumenHistorial({ registro, cerrar }: { registro: typeof historial[0]; cerrar: () => void }) {
  const [tab, setTab] = useState<'resumen' | 'documentos' | 'calificaciones'>('resumen')

  const finalizada = registro.estado === 'Finalizada'
  const [desde, hasta] = registro.periodo.split('–').map(t => t.trim())
  const ini = registro.practicante.split(' ').map(n => n[0]).join('').slice(0, 2)

  const parse = (f: string) => { const [d, m, y] = f.split('/').map(Number); return new Date(y, m - 1, d) }
  const dias = Math.max(0, Math.round((parse(hasta).getTime() - parse(desde).getTime()) / 86400000))

  const documentos = archivosDePractica({ id: registro.id, practicante: registro.practicante, inicio: desde })
  const universitarios = documentos.filter(d => d.categoria === 'universitario')
  const cedula = documentos.filter(d => d.categoria === 'cedula')

  const notas = finalizada ? evaluacionHistorial(registro.id) : null
  const promedio = notas ? Object.values(notas).reduce((a, b) => a + b, 0) / Object.values(notas).length : 0
  const colorNota = (n: number) => n >= 6 ? '#27ae60' : n >= 5 ? '#2980b9' : n >= 4 ? '#e67e22' : '#c0392b'

  const tabs = [
    { id: 'resumen' as const, label: 'Resumen', icon: 'clipboard' },
    { id: 'documentos' as const, label: 'Documentos', icon: 'doc' },
    { id: 'calificaciones' as const, label: 'Calificaciones', icon: 'star' },
  ]

  const campo = (label: string, value: string, icon?: string) => (
    <div key={label}>
      <div style={{ color: '#6c757d', fontSize: 11, fontWeight: 700, letterSpacing: '0.04em', textTransform: 'uppercase', marginBottom: 3 }}>{label}</div>
      <div style={{ color: '#1a2744', fontSize: 13.5, fontWeight: 500, display: 'flex', alignItems: 'center', gap: 6 }}>
        {icon && <span style={{ display: 'flex', color: '#adb5bd', flexShrink: 0 }}><Icon name={icon} size={13} /></span>}
        {value}
      </div>
    </div>
  )

  const filaDoc = (a: ArchivoPostulacion) => (
    <div key={a.nombre} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '10px 12px', background: '#f8f9fa', border: '1px solid #e9ecef', borderRadius: 8 }}>
      <span style={{ color: a.categoria === 'cedula' ? '#8e6dbf' : '#1a2744', display: 'flex', flexShrink: 0 }}><Icon name="doc" size={17} /></span>
      <div style={{ flex: 1, minWidth: 0 }}>
        <div style={{ color: '#1a2744', fontSize: 13, fontWeight: 600, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{a.nombre}</div>
        <div style={{ color: '#6c757d', fontSize: 11.5, marginTop: 1 }}>{a.tipo} · {a.peso} · subido el {a.fecha}</div>
      </div>
      <button style={{ border: '1.5px solid #dee2e6', background: '#fff', color: '#495057', padding: '6px 12px', borderRadius: 6, fontWeight: 600, fontSize: 12, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6, flexShrink: 0 }}>
        <Icon name="eye" size={13} /> Ver
      </button>
      <button style={{ border: '1.5px solid #dee2e6', background: '#fff', color: '#495057', padding: '6px 12px', borderRadius: 6, fontWeight: 600, fontSize: 12, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6, flexShrink: 0 }}>
        <Icon name="download" size={13} /> Descargar
      </button>
    </div>
  )

  return (
    <div onClick={cerrar} style={{ position: 'fixed', inset: 0, background: 'rgba(26,39,68,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000, padding: 20 }}>
      <div onClick={e => e.stopPropagation()} style={{ background: '#fff', borderRadius: 12, width: '100%', maxWidth: 760, maxHeight: '90vh', display: 'flex', flexDirection: 'column', boxShadow: '0 20px 60px rgba(0,0,0,0.35)', overflow: 'hidden' }}>
        {/* Encabezado */}
        <div style={{ background: '#1a2744', padding: '20px 24px', flexShrink: 0 }}>
          <div style={{ display: 'flex', alignItems: 'flex-start', gap: 14 }}>
            <Avatar ini={ini} color={registro.uniColor} size={46} />
            <div style={{ flex: 1, minWidth: 0 }}>
              <div style={{ color: '#fff', fontSize: 17, fontWeight: 700 }}>{registro.practicante}</div>
              <div style={{ color: 'rgba(255,255,255,0.65)', fontSize: 12.5, marginTop: 2 }}>{registro.universidad} · {registro.consultorio}</div>
            </div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10, flexShrink: 0 }}>
              <StatusBadge estado={registro.estado} />
              <button onClick={cerrar} style={{ border: 'none', background: 'rgba(255,255,255,0.12)', color: '#fff', width: 32, height: 32, borderRadius: 8, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}><Icon name="x" size={15} /></button>
            </div>
          </div>
        </div>

        {/* Pestañas */}
        <div style={{ display: 'flex', borderBottom: '1px solid #e9ecef', flexShrink: 0, background: '#fff' }}>
          {tabs.map(t => {
            const activa = tab === t.id
            return (
              <button key={t.id} onClick={() => setTab(t.id)}
                style={{ display: 'flex', alignItems: 'center', gap: 7, padding: '13px 20px', border: 'none', borderBottom: `2.5px solid ${activa ? '#c0392b' : 'transparent'}`, background: 'transparent', color: activa ? '#1a2744' : '#6c757d', fontSize: 13, fontWeight: activa ? 700 : 500, cursor: 'pointer', fontFamily: 'Inter, sans-serif' }}>
                <Icon name={t.icon} size={14} /> {t.label}
              </button>
            )
          })}
        </div>

        {/* Contenido */}
        <div style={{ padding: '20px 24px', overflowY: 'auto', background: '#f8f9fa' }}>
          {tab === 'resumen' && (
            <>
              {registro.cancelacion && (
                <div style={{ display: 'flex', alignItems: 'flex-start', gap: 10, background: '#fdecea', border: '1px solid #f2d6d2', borderRadius: 10, padding: '12px 14px', marginBottom: 18, color: '#a5342a', fontSize: 12.5 }}>
                  <span style={{ display: 'flex', marginTop: 1, flexShrink: 0 }}><Icon name="alert" size={15} /></span>
                  <span>
                    <strong>
                      {registro.cancelacion.antesDeIniciar
                        ? 'Cancelada antes de iniciar la práctica'
                        : 'Práctica cancelada durante su desarrollo'}
                    </strong> — {registro.cancelacion.fecha}
                    <span style={{ display: 'block', marginTop: 4, lineHeight: 1.5 }}>{registro.cancelacion.motivo}</span>
                  </span>
                </div>
              )}

              <div style={{ background: '#fff', border: '1px solid #e9ecef', borderRadius: 10, padding: '16px 18px', marginBottom: 14 }}>
                <div style={{ fontSize: 12, fontWeight: 700, color: '#1a2744', letterSpacing: '0.05em', marginBottom: 14 }}>DATOS DEL PRACTICANTE</div>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 16 }}>
                  {campo('Nombre completo', registro.practicante)}
                  {campo('Correo electrónico', registro.email, 'mail')}
                  {campo('Teléfono', registro.tel, 'phone')}
                  {campo('Universidad', registro.universidad)}
                </div>
              </div>

              <div style={{ background: '#fff', border: '1px solid #e9ecef', borderRadius: 10, padding: '16px 18px', marginBottom: 14 }}>
                <div style={{ fontSize: 12, fontWeight: 700, color: '#1a2744', letterSpacing: '0.05em', marginBottom: 14 }}>PERIODO DE LA PRÁCTICA</div>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 16 }}>
                  {campo('Fecha de inicio', desde, 'calendar')}
                  {campo('Fecha de término', hasta, 'calendar')}
                  {campo('Duración', registro.cancelacion?.antesDeIniciar ? `${dias} días programados (no se cursaron)` : `${dias} días`, 'clock')}
                  {campo('Consultorio', registro.consultorio, 'building')}
                </div>
              </div>

              <div style={{ background: '#fff', border: '1px solid #e9ecef', borderRadius: 10, padding: '16px 18px' }}>
                <div style={{ fontSize: 12, fontWeight: 700, color: '#1a2744', letterSpacing: '0.05em', marginBottom: 14 }}>ABOGADO TUTOR</div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                  <Avatar ini={registro.abogadoIni} color={registro.abogadoColor} size={40} />
                  <div>
                    <div style={{ color: '#1a2744', fontSize: 14, fontWeight: 700 }}>{registro.abogado}</div>
                    <div style={{ color: '#6c757d', fontSize: 12.5, marginTop: 1 }}>
                      {abogados.find(a => a.ini === registro.abogadoIni)?.area ?? 'Abogado tutor'}
                    </div>
                  </div>
                </div>
              </div>
            </>
          )}

          {tab === 'documentos' && (
            <>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 12, fontWeight: 700, color: '#1a2744', letterSpacing: '0.05em' }}>
                  <Icon name="upload" size={14} /> DOCUMENTOS SUBIDOS POR EL PRACTICANTE
                </div>
                <span style={{ color: '#6c757d', fontSize: 12 }}>{documentos.length} archivos</span>
              </div>

              <div style={{ color: '#6c757d', fontSize: 11.5, fontWeight: 700, letterSpacing: '0.04em', marginBottom: 8 }}>DOCUMENTOS UNIVERSITARIOS</div>
              <div style={{ display: 'flex', flexDirection: 'column', gap: 8, marginBottom: 18 }}>{universitarios.map(filaDoc)}</div>

              <div style={{ color: '#6c757d', fontSize: 11.5, fontWeight: 700, letterSpacing: '0.04em', marginBottom: 8 }}>CÉDULA DE IDENTIDAD</div>
              <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>{cedula.map(filaDoc)}</div>
            </>
          )}

          {tab === 'calificaciones' && (
            notas ? (
              <>
                <div style={{ display: 'flex', alignItems: 'center', gap: 16, background: '#fff', border: '1px solid #e9ecef', borderRadius: 10, padding: '16px 18px', marginBottom: 14 }}>
                  <div style={{ width: 62, height: 62, borderRadius: 12, background: `${colorNota(promedio)}18`, color: colorNota(promedio), display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                    <span style={{ fontSize: 21, fontWeight: 700, lineHeight: 1 }}>{promedio.toFixed(1)}</span>
                    <span style={{ fontSize: 9, fontWeight: 700, letterSpacing: '0.05em', marginTop: 2 }}>NOTA</span>
                  </div>
                  <div>
                    <div style={{ color: '#1a2744', fontSize: 14.5, fontWeight: 700 }}>Calificación final: {conceptoDeNota(promedio)}</div>
                    <div style={{ color: '#6c757d', fontSize: 12.5, marginTop: 3 }}>Promedio de las {encuestaItems.length} dimensiones evaluadas por {registro.abogado}.</div>
                  </div>
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                  {encuestaItems.map(item => {
                    const n = notas[item.id]
                    return (
                      <div key={item.id} style={{ background: '#fff', border: '1px solid #e9ecef', borderRadius: 9, padding: '12px 14px' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 8 }}>
                          <div style={{ flex: 1, minWidth: 0 }}>
                            <div style={{ color: '#1a2744', fontSize: 13.5, fontWeight: 600 }}>{item.label}</div>
                            <div style={{ color: '#adb5bd', fontSize: 11.5, marginTop: 1 }}>{item.desc}</div>
                          </div>
                          <span style={{ background: `${colorNota(n)}18`, color: colorNota(n), fontSize: 11, fontWeight: 700, padding: '3px 10px', borderRadius: 20, flexShrink: 0 }}>{conceptoDeNota(n)}</span>
                          <span style={{ color: colorNota(n), fontSize: 16, fontWeight: 700, width: 34, textAlign: 'right', flexShrink: 0 }}>{n.toFixed(1)}</span>
                        </div>
                        <div style={{ background: '#f1f3f5', borderRadius: 4, height: 6, overflow: 'hidden' }}>
                          <div style={{ height: '100%', width: `${(n / 7) * 100}%`, background: colorNota(n), borderRadius: 4 }} />
                        </div>
                      </div>
                    )
                  })}
                </div>
              </>
            ) : (
              <div style={{ textAlign: 'center', padding: '40px 20px', color: '#adb5bd', fontSize: 13.5 }}>
                <div style={{ display: 'flex', justifyContent: 'center', marginBottom: 10, color: '#dee2e6' }}><Icon name="star" size={30} /></div>
                {registro.cancelacion?.antesDeIniciar
                  ? 'La práctica se canceló antes de iniciar, por lo que no hay evaluación registrada.'
                  : 'La práctica no se completó, por lo que el tutor no registró la evaluación final.'}
              </div>
            )
          )}
        </div>
      </div>
    </div>
  )
}

function Consultorios() {
  const [selected, setSelected] = useState<typeof consultorios[0] | null>(null)
  const region = useRegion()
  const consultoriosRegion = consultorios.filter(c => c.region === region)

  const practicantesDelConsultorio = (nombre: string) =>
    practicas.filter(p => p.consultorio.includes(nombre.split('—')[1]?.trim() || nombre.split(' ')[1] || nombre))

  return (
    <div style={{ padding: '28px 28px 40px' }}>
      <h1 style={{ fontSize: 24, fontWeight: 700, color: '#1a2744', margin: 0 }}>Consultorios</h1>
      <Breadcrumb items={['Inicio', region, 'Consultorios disponibles']} />

      {/* Leyenda de colores según disponibilidad de cupos */}
      <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: 16, background: '#fff', border: '1px solid #e9ecef', borderRadius: 10, padding: '12px 18px', marginTop: 20 }}>
        <span style={{ fontSize: 11.5, fontWeight: 700, color: '#495057', letterSpacing: '0.03em' }}>DISPONIBILIDAD DE CUPOS:</span>
        {[{ accent: '#27ae60', label: 'Muchos cupos' }, { accent: '#f39c12', label: 'Pocos cupos' }, { accent: '#e74c3c', label: 'Sin cupos' }].map(n => (
          <span key={n.label} style={{ display: 'inline-flex', alignItems: 'center', gap: 6, fontSize: 12.5, color: '#495057' }}>
            <span style={{ width: 11, height: 11, borderRadius: '50%', background: n.accent, flexShrink: 0 }} /> {n.label}
          </span>
        ))}
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 16, marginTop: 16 }}>
        {consultoriosRegion.map(c => {
          const pct = (c.practicantes / c.capacidad) * 100
          const nv = nivelDisponibilidad(c.practicantes, c.capacidad)
          const isSelected = selected?.id === c.id
          return (
            <div key={c.id} onClick={() => setSelected(isSelected ? null : c)}
              style={{ background: '#fff', borderRadius: 12, border: `2px solid ${isSelected ? nv.accent : '#e9ecef'}`, padding: '22px 20px', cursor: 'pointer', transition: 'all 0.15s', boxShadow: isSelected ? `0 4px 16px ${nv.accent}28` : 'none' }}>
              <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', marginBottom: 14 }}>
                <div style={{ width: 44, height: 44, borderRadius: 10, background: `${nv.accent}18`, display: 'flex', alignItems: 'center', justifyContent: 'center', color: nv.accent }}>
                  <Icon name="building" size={20} />
                </div>
                <span style={{ display: 'inline-flex', alignItems: 'center', gap: 5, background: nv.bg, color: nv.color, fontSize: 11, fontWeight: 700, padding: '3px 10px', borderRadius: 20 }}>
                  <span style={{ width: 6, height: 6, borderRadius: '50%', background: nv.accent, flexShrink: 0 }} /> {nv.label}
                </span>
              </div>
              <div style={{ fontWeight: 700, color: '#1a2744', fontSize: 14, lineHeight: 1.3, marginBottom: 6 }}>{c.nombre}</div>
              <div style={{ display: 'flex', alignItems: 'center', gap: 5, color: '#6c757d', fontSize: 12, marginBottom: 14 }}>
                <Icon name="map_pin" size={12} />
                <span>{c.direccion}</span>
              </div>
              {/* Progress bar */}
              <div style={{ background: '#f1f3f5', borderRadius: 4, height: 6, overflow: 'hidden' }}>
                <div style={{ height: '100%', width: `${pct}%`, background: nv.accent, borderRadius: 4, transition: 'width 0.3s' }} />
              </div>
              <div style={{ color: '#adb5bd', fontSize: 11, marginTop: 6 }}>{c.capacidad - c.practicantes} de {c.capacidad} cupos disponibles</div>
            </div>
          )
        })}
      </div>

      {/* Detail */}
      {selected && (
        <Card style={{ marginTop: 20 }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 20 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 14 }}>
              <div style={{ width: 52, height: 52, borderRadius: 12, background: `${selected.color}18`, display: 'flex', alignItems: 'center', justifyContent: 'center', color: selected.color }}><Icon name="building" size={24} /></div>
              <div>
                <div style={{ fontWeight: 700, fontSize: 17, color: '#1a2744' }}>{selected.nombre}</div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 5, color: '#6c757d', fontSize: 13, marginTop: 3 }}><Icon name="map_pin" size={13} />{selected.direccion}</div>
                <div style={{ display: 'flex', gap: 10, marginTop: 8 }}>
                  <span style={{ background: '#e8f4fd', color: '#1565c0', padding: '3px 10px', borderRadius: 20, fontSize: 12, fontWeight: 600 }}>{selected.practicantes} practicantes activos</span>
                  <span style={{ background: '#f1f3f5', color: '#495057', padding: '3px 10px', borderRadius: 20, fontSize: 12, fontWeight: 600 }}>{selected.capacidad - selected.practicantes} cupos disponibles</span>
                </div>
              </div>
            </div>
            <button onClick={() => setSelected(null)} style={{ border: 'none', background: '#f1f3f5', color: '#6c757d', width: 32, height: 32, borderRadius: 8, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}><Icon name="x" size={14} /></button>
          </div>
          <div style={{ borderTop: '1px solid #f1f3f5', paddingTop: 16 }}>
            <div style={{ color: '#6c757d', fontSize: 12, fontWeight: 700, letterSpacing: '0.05em', marginBottom: 12 }}>PRACTICANTES ASIGNADOS</div>
            {practicas.filter(p => p.consultorio.toLowerCase().includes(selected.nombre.toLowerCase().split('—')[1]?.trim().split(' ')[0].toLowerCase() || selected.nombre.split(' ')[1]?.toLowerCase() || '')).length === 0 ? (
              <div style={{ color: '#adb5bd', textAlign: 'center', padding: '20px 0', fontSize: 13 }}>Sin practicantes asignados actualmente</div>
            ) : (
              practicas.filter(p => {
                const keyword = selected.nombre.includes('—') ? selected.nombre.split('—')[1]?.trim() : selected.nombre.split(' ')[1]
                return p.consultorio.toLowerCase().includes((keyword || selected.nombre).toLowerCase())
              }).map((p, i) => (
                <div key={i} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '10px 14px', background: '#f8f9fa', borderRadius: 8, marginBottom: 8 }}>
                  <Avatar ini={p.practicante.split(' ').map(n => n[0]).join('').slice(0, 2)} color={p.uniColor} size={34} />
                  <div style={{ flex: 1 }}>
                    <div style={{ fontWeight: 600, color: '#1a2744', fontSize: 13.5 }}>{p.practicante}</div>
                    <div style={{ color: '#6c757d', fontSize: 12 }}>{p.universidad} · Tutor: {p.abogado}</div>
                  </div>
                  <StatusBadge estado={p.estado} />
                  <span style={{ color: '#adb5bd', fontSize: 12 }}>Termina {p.termino}</span>
                </div>
              ))
            )}
          </div>
        </Card>
      )}
    </div>
  )
}

const usuariosSistema = [
  { id: 1, nombre: 'Mauricio Decap Fernández', rol: 'Administrador', email: 'mauricio.decap@cajbiobio.cl', ini: 'MD', color: '#1a2744', acceso: 'Hoy, 09:12', activo: true },
  { id: 2, nombre: 'Paulina Herrera Núñez', rol: 'Secretaria', email: 'paulina.herrera@cajbiobio.cl', ini: 'PH', color: '#c0392b', acceso: 'Hoy, 08:40', activo: true },
  { id: 3, nombre: 'Carlos Muñoz Sepúlveda', rol: 'Abogado tutor', email: 'carlos.munoz@cajbiobio.cl', ini: 'CM', color: '#5b7fd4', acceso: 'Ayer, 17:25', activo: true },
  { id: 4, nombre: 'Andrea Rojas Fuentes', rol: 'Abogado tutor', email: 'andrea.rojas@cajbiobio.cl', ini: 'AR', color: '#27ae60', acceso: 'Ayer, 15:02', activo: true },
  { id: 5, nombre: 'Felipe Contreras Vidal', rol: 'Abogado tutor', email: 'felipe.contreras@cajbiobio.cl', ini: 'FC', color: '#e67e22', acceso: '04/08/2026', activo: false },
]

function DashboardAdmin() {
  const [usuarios, setUsuarios] = useState(usuariosSistema)
  const [filtroRol, setFiltroRol] = useState('Todos')
  const [usuarioEdit, setUsuarioEdit] = useState<typeof usuariosSistema[0] | null>(null)
  const [usuarioNuevo, setUsuarioNuevo] = useState<{ nombre: string; email: string; rol: string } | null>(null)
  const [errorNuevo, setErrorNuevo] = useState('')

  const [fichas, setFichas] = useState(practicas)
  const [buscaPracticante, setBuscaPracticante] = useState('')
  const [fichaId, setFichaId] = useState<number | null>(null)
  const [borrador, setBorrador] = useState<typeof practicas[0] | null>(null)
  const [buscaSede, setBuscaSede] = useState('')
  const [motivoCancel, setMotivoCancel] = useState('')
  const [guardado, setGuardado] = useState(false)

  const [red, setRed] = useState(consultorios)
  const [sedeId, setSedeId] = useState<number | null>(null)
  const sedeAbierta = red.find(c => c.id === sedeId) || null
  const ampliarCupos = (id: number, delta: number) =>
    setRed(prev => prev.map(c => c.id === id ? { ...c, capacidad: Math.max(c.practicantes, c.capacidad + delta) } : c))

  const roles = ['Todos', 'Administrador', 'Secretaria', 'Abogado tutor']
  const visibles = filtroRol === 'Todos' ? usuarios : usuarios.filter(u => u.rol === filtroRol)

  const coloresAvatar = ['#1a2744', '#c0392b', '#5b7fd4', '#27ae60', '#e67e22', '#8e6dbf']
  const iniciales = (nombre: string) => nombre.trim().split(/\s+/).map(n => n[0]).join('').slice(0, 2).toUpperCase()
  const abrirNuevoUsuario = () => {
    setErrorNuevo('')
    setUsuarioNuevo({ nombre: '', email: '', rol: filtroRol === 'Todos' ? 'Abogado tutor' : filtroRol })
  }
  const crearUsuario = () => {
    if (!usuarioNuevo) return
    const nombre = usuarioNuevo.nombre.trim()
    const email = usuarioNuevo.email.trim().toLowerCase()
    if (!nombre || !email) { setErrorNuevo('Complete el nombre y el correo electrónico.'); return }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { setErrorNuevo('El correo electrónico no tiene un formato válido.'); return }
    if (usuarios.some(u => u.email.toLowerCase() === email)) { setErrorNuevo('Ya existe un usuario registrado con ese correo.'); return }
    const id = Math.max(0, ...usuarios.map(u => u.id)) + 1
    setUsuarios(us => [...us, {
      id, nombre, email, rol: usuarioNuevo.rol,
      ini: iniciales(nombre), color: coloresAvatar[id % coloresAvatar.length],
      acceso: 'Sin ingresos', activo: true,
    }])
    setUsuarioNuevo(null)
    setErrorNuevo('')
    if (filtroRol !== 'Todos' && filtroRol !== usuarioNuevo.rol) setFiltroRol(usuarioNuevo.rol)
  }

  const nombreSede = (c: typeof consultorios[0]) => c.nombre.replace('Consultorio ', '')

  const qPracticante = buscaPracticante.trim().toLowerCase()
  const practicantesFiltrados = qPracticante
    ? fichas.filter(p =>
        p.practicante.toLowerCase().includes(qPracticante) ||
        p.universidad.toLowerCase().includes(qPracticante) ||
        p.consultorio.toLowerCase().includes(qPracticante) ||
        p.abogado.toLowerCase().includes(qPracticante))
    : fichas

  const abrirFicha = (p: typeof practicas[0]) => {
    setFichaId(p.id); setBorrador({ ...p }); setBuscaSede(''); setMotivoCancel(''); setGuardado(false)
  }
  const cerrarFicha = () => { setFichaId(null); setBorrador(null); setGuardado(false) }
  const editarBorrador = (campo: keyof typeof practicas[0], valor: string) =>
    setBorrador(b => b ? { ...b, [campo]: valor } : b)
  const guardarFicha = () => {
    if (!borrador) return
    setFichas(prev => prev.map(p => p.id === borrador.id ? borrador : p))
    setGuardado(true)
  }
  const cancelarPracticaFicha = () => {
    if (!borrador || !motivoCancel) return
    const cancelada = { ...borrador, estado: 'Cancelada' }
    setFichas(prev => prev.map(p => p.id === cancelada.id ? cancelada : p))
    setBorrador(cancelada)
    setGuardado(true)
  }

  const cupos = red.reduce((a, c) => a + c.capacidad, 0)
  const ocupados = red.reduce((a, c) => a + c.practicantes, 0)

  const indicadores = [
    { label: 'Prácticas activas', valor: fichas.filter(p => p.estado === 'Activa').length, icon: 'calendar', color: '#2980b9' },
    { label: 'Postulaciones pendientes', valor: postulaciones.filter(p => p.estado === 'pendiente').length, icon: 'doc', color: '#e67e22' },
    { label: 'Ocupación de cupos', valor: `${Math.round((ocupados / cupos) * 100)}%`, icon: 'building', color: '#8e6dbf' },
    { label: 'Practicantes registrados', valor: fichas.length, icon: 'user_plus', color: '#c0392b' },
  ]

  return (
    <div style={{ padding: '28px 28px 48px' }}>
      <div style={{ marginBottom: 24 }}>
        <h1 style={{ fontSize: 22, fontWeight: 700, color: '#1a2744', margin: 0 }}>Administración del sistema</h1>
        <div style={{ color: '#6c757d', fontSize: 13.5, marginTop: 3 }}>Usuarios, resoluciones y capacidad institucional</div>
        <Breadcrumb items={['Inicio', 'Vista Administrador']} />
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 16, marginBottom: 18 }}>
        {indicadores.map(ind => (
          <Card key={ind.label} style={{ padding: '18px 20px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
              <div style={{ width: 42, height: 42, borderRadius: 10, background: `${ind.color}18`, color: ind.color, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                <Icon name={ind.icon} size={19} />
              </div>
              <div>
                <div style={{ fontSize: 22, fontWeight: 700, color: '#1a2744', lineHeight: 1.1 }}>{ind.valor}</div>
                <div style={{ color: '#6c757d', fontSize: 12, marginTop: 2 }}>{ind.label}</div>
              </div>
            </div>
          </Card>
        ))}
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1.2fr 0.8fr', gap: 18 }}>
        {/* Gestión de practicantes — buscador sobre todas las prácticas */}
        <Card style={{ padding: '22px 24px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6 }}>
            <Icon name="user_plus" size={16} />
            <span style={{ fontWeight: 700, color: '#1a2744', fontSize: 15 }}>Gestión de practicantes</span>
          </div>
          <div style={{ color: '#6c757d', fontSize: 12.5, marginBottom: 14 }}>Busque cualquier practicante para abrir su ficha, corregir datos mal ingresados, cambiarlo de sede o cancelar su práctica.</div>

          <div style={{ position: 'relative', marginBottom: 14 }}>
            <span style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: '#adb5bd' }}><Icon name="search" size={15} /></span>
            <input value={buscaPracticante} onChange={e => setBuscaPracticante(e.target.value)}
              placeholder="Buscar por nombre, universidad, sede o abogado tutor..."
              style={{ ...inputStyle, paddingLeft: 36, background: '#f8f9fa' }} />
          </div>

          {practicantesFiltrados.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '28px 0', color: '#adb5bd', fontSize: 13 }}>No se encontraron practicantes con ese criterio.</div>
          ) : (
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: 10 }}>
              {practicantesFiltrados.map(p => (
                <div key={p.id} onClick={() => abrirFicha(p)}
                  style={{ display: 'flex', alignItems: 'center', gap: 11, background: '#f8f9fa', border: '1px solid #eef0f2', borderRadius: 9, padding: '11px 13px', cursor: 'pointer' }}>
                  <Avatar ini={p.practicante.split(' ').map(n => n[0]).join('').slice(0, 2)} color={p.uniColor} size={36} />
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <div style={{ fontWeight: 700, color: '#1a2744', fontSize: 13, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{p.practicante}</div>
                    <div style={{ color: '#6c757d', fontSize: 11.5, marginTop: 1, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{p.universidad} · {p.consultorio}</div>
                    <div style={{ marginTop: 5 }}><StatusBadge estado={p.estado} /></div>
                  </div>
                  <span style={{ color: '#adb5bd', flexShrink: 0 }}><Icon name="edit" size={14} /></span>
                </div>
              ))}
            </div>
          )}
        </Card>

        <div style={{ display: 'flex', flexDirection: 'column', gap: 18 }}>
          {/* Capacidad por consultorio */}
          <Card style={{ padding: '22px 24px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6 }}>
              <Icon name="building" size={16} />
              <span style={{ fontWeight: 700, color: '#1a2744', fontSize: 15 }}>Capacidad institucional</span>
            </div>
            <div style={{ color: '#6c757d', fontSize: 12.5, marginBottom: 14 }}>{ocupados} de {cupos} cupos ocupados. Haga clic en una sede para ver su detalle y ampliar cupos.</div>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
              {red.map(c => {
                const nv = nivelDisponibilidad(c.practicantes, c.capacidad)
                return (
                  <div key={c.id} onClick={() => setSedeId(c.id)} style={{ cursor: 'pointer' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline', marginBottom: 4 }}>
                      <span style={{ color: '#1a2744', fontSize: 12.5, fontWeight: 600 }}>{c.nombre.replace('Consultorio ', '')}</span>
                      <span style={{ color: nv.color, fontSize: 11.5, fontWeight: 700 }}>{c.practicantes}/{c.capacidad}</span>
                    </div>
                    <div style={{ background: '#f1f3f5', borderRadius: 4, height: 6, overflow: 'hidden' }}>
                      <div style={{ height: '100%', width: `${(c.practicantes / c.capacidad) * 100}%`, background: nv.accent, borderRadius: 4 }} />
                    </div>
                  </div>
                )
              })}
            </div>
          </Card>

          {/* Información del sistema */}
          <Card style={{ padding: '22px 24px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 14 }}>
              <Icon name="settings" size={16} />
              <span style={{ fontWeight: 700, color: '#1a2744', fontSize: 15 }}>Información del sistema</span>
            </div>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
              {[
                { label: 'Versión de la aplicación', valor: APP_VERSION },
                { label: 'Usuarios registrados', valor: String(usuarios.length) },
                { label: 'Usuarios activos', valor: String(usuarios.filter(u => u.activo).length) },
                { label: 'Consultorios en la red', valor: String(red.length) },
                { label: 'Cupos totales', valor: String(cupos) },
              ].map(fila => (
                <div key={fila.label} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', background: '#f8f9fa', borderRadius: 8, padding: '10px 13px' }}>
                  <span style={{ color: '#6c757d', fontSize: 12.5 }}>{fila.label}</span>
                  <span style={{ color: '#1a2744', fontSize: 12.5, fontWeight: 700 }}>{fila.valor}</span>
                </div>
              ))}
            </div>
          </Card>
        </div>
      </div>

      {/* Usuarios y roles */}
      <Card style={{ padding: '22px 24px', marginTop: 18 }}>
        <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 16, marginBottom: 14 }}>
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6 }}>
              <Icon name="shield" size={16} />
              <span style={{ fontWeight: 700, color: '#1a2744', fontSize: 15 }}>Usuarios y roles</span>
            </div>
            <div style={{ color: '#6c757d', fontSize: 12.5 }}>Corrija los datos de cualquier usuario, cambie su rol o suspenda su acceso sin borrar su historial.</div>
          </div>
          <button onClick={abrirNuevoUsuario} title="Agregar un nuevo usuario al sistema"
            style={{ background: '#1a2744', color: '#fff', border: 'none', padding: '9px 16px', borderRadius: 7, fontSize: 12.5, fontWeight: 700, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 7, flexShrink: 0, whiteSpace: 'nowrap' }}>
            <Icon name="user_plus" size={15} /> Nuevo usuario
          </button>
        </div>

        <div style={{ display: 'flex', gap: 6, marginBottom: 14, flexWrap: 'wrap' }}>
          {roles.map(rol => (
            <button key={rol} onClick={() => setFiltroRol(rol)}
              style={{ background: filtroRol === rol ? '#1a2744' : '#f1f3f5', color: filtroRol === rol ? '#fff' : '#495057', border: 'none', padding: '6px 13px', borderRadius: 20, fontSize: 12, fontWeight: 600, cursor: 'pointer' }}>
              {rol}
            </button>
          ))}
        </div>

        <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
          {visibles.map(u => (
            <div key={u.id} style={{ display: 'flex', alignItems: 'center', gap: 12, background: '#f8f9fa', borderRadius: 9, padding: '11px 14px', opacity: u.activo ? 1 : 0.6 }}>
              <Avatar ini={u.ini} color={u.color} size={36} />
              <div style={{ flex: 1, minWidth: 0 }}>
                <div style={{ fontWeight: 700, color: '#1a2744', fontSize: 13 }}>{u.nombre}</div>
                <div style={{ color: '#6c757d', fontSize: 11.5, marginTop: 1, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{u.email}</div>
              </div>
              <span style={{ background: '#e8f4fd', color: '#1565c0', fontSize: 11, fontWeight: 700, padding: '3px 10px', borderRadius: 20, flexShrink: 0 }}>{u.rol}</span>
              <div style={{ color: '#adb5bd', fontSize: 11, width: 92, textAlign: 'right', flexShrink: 0 }}>{u.acceso}</div>
              <button onClick={() => setUsuarioEdit({ ...u })} title="Editar datos del usuario"
                style={{ background: '#f1f3f5', color: '#495057', border: 'none', width: 30, height: 30, borderRadius: 7, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                <Icon name="edit" size={13} />
              </button>
              <button onClick={() => setUsuarios(us => us.map(x => x.id === u.id ? { ...x, activo: !x.activo } : x))}
                style={{ background: u.activo ? '#fdecea' : '#e8f5e9', color: u.activo ? '#c0392b' : '#2e7d32', border: 'none', padding: '6px 12px', borderRadius: 7, fontSize: 11.5, fontWeight: 700, cursor: 'pointer', flexShrink: 0 }}>
                {u.activo ? 'Desactivar' : 'Activar'}
              </button>
            </div>
          ))}
        </div>
      </Card>

      {/* Modal — ficha editable del practicante */}
      {borrador && fichaId !== null && (() => {
        const b = borrador
        const original = fichas.find(p => p.id === b.id)
        const cancelada = b.estado === 'Cancelada'
        const qSede = buscaSede.trim().toLowerCase()
        const sedes = qSede
          ? red.filter(c => c.nombre.toLowerCase().includes(qSede) || c.direccion.toLowerCase().includes(qSede))
          : red
        const cambioSede = original ? b.consultorio !== original.consultorio : false
        return (
          <div onClick={cerrarFicha} style={{ position: 'fixed', inset: 0, background: 'rgba(26,39,68,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000, padding: 20 }}>
            <div onClick={e => e.stopPropagation()} style={{ background: '#fff', borderRadius: 12, width: '100%', maxWidth: 760, maxHeight: '88vh', display: 'flex', flexDirection: 'column', boxShadow: '0 20px 60px rgba(0,0,0,0.35)', overflow: 'hidden' }}>
              <div style={{ background: '#1a2744', padding: '18px 24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexShrink: 0 }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                  <Avatar ini={b.practicante.split(' ').map(n => n[0]).join('').slice(0, 2)} color={b.uniColor} size={38} />
                  <div>
                    <div style={{ color: '#fff', fontSize: 16, fontWeight: 700 }}>Ficha del practicante</div>
                    <div style={{ color: 'rgba(255,255,255,0.65)', fontSize: 12.5 }}>{b.practicante}</div>
                  </div>
                </div>
                <button onClick={cerrarFicha} style={{ border: 'none', background: 'rgba(255,255,255,0.12)', color: '#fff', width: 32, height: 32, borderRadius: 8, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}><Icon name="x" size={15} /></button>
              </div>

              <div style={{ padding: '20px 24px', overflowY: 'auto' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 16 }}>
                  <span style={{ color: '#6c757d', fontSize: 12.5 }}>Estado de la práctica:</span>
                  <StatusBadge estado={b.estado} />
                </div>

                <div style={{ fontSize: 12, fontWeight: 700, color: '#1a2744', letterSpacing: '0.05em', marginBottom: 12 }}>DATOS DEL PRACTICANTE</div>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: 12, marginBottom: 22 }}>
                  <FormField label="Nombre completo"><input style={inputStyle} value={b.practicante} onChange={e => editarBorrador('practicante', e.target.value)} /></FormField>
                  <FormField label="Universidad"><input style={inputStyle} value={b.universidad} onChange={e => editarBorrador('universidad', e.target.value)} /></FormField>
                  <FormField label="Correo electrónico"><input style={inputStyle} value={b.email} onChange={e => editarBorrador('email', e.target.value)} /></FormField>
                  <FormField label="Teléfono"><input style={inputStyle} value={b.tel} onChange={e => editarBorrador('tel', e.target.value)} /></FormField>
                  <FormField label="Fecha de inicio"><input style={inputStyle} value={b.inicio} onChange={e => editarBorrador('inicio', e.target.value)} /></FormField>
                  <FormField label="Fecha de término"><input style={inputStyle} value={b.termino} onChange={e => editarBorrador('termino', e.target.value)} /></FormField>
                  <FormField label="Abogado tutor">
                    <select style={selectStyle} value={b.abogado} onChange={e => editarBorrador('abogado', e.target.value)}>
                      {abogados.map(a => <option key={a.id}>{a.nombre}</option>)}
                      {!abogados.some(a => a.nombre === b.abogado) && <option>{b.abogado}</option>}
                    </select>
                  </FormField>
                  <FormField label="Discapacidad"><input style={inputStyle} value={b.discapacidad} onChange={e => editarBorrador('discapacidad', e.target.value)} /></FormField>
                  <FormField label="Estado de la práctica">
                    <select style={selectStyle} value={b.estado} onChange={e => editarBorrador('estado', e.target.value)}>
                      {['Por iniciar', 'Activa', 'Por terminar', 'Finalizada', 'Cancelada'].map(es => (
                        <option key={es} value={es}>{estadoLabels[es] || es}</option>
                      ))}
                    </select>
                  </FormField>
                </div>

                <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 12, fontWeight: 700, color: '#1a2744', letterSpacing: '0.05em', marginBottom: 4 }}>
                  <Icon name="building" size={14} /> SEDE ASIGNADA
                </div>
                <div style={{ color: '#6c757d', fontSize: 12.5, marginBottom: 12 }}>Seleccione otra sede para trasladar al practicante. Actualmente en <strong>{b.consultorio}</strong>.</div>
                <div style={{ position: 'relative', marginBottom: 12 }}>
                  <span style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: '#adb5bd' }}><Icon name="search" size={15} /></span>
                  <input value={buscaSede} onChange={e => setBuscaSede(e.target.value)} placeholder="Buscar sede por nombre o dirección..." style={{ ...inputStyle, paddingLeft: 36, background: '#f8f9fa' }} />
                </div>

                {sedes.length === 0 ? (
                  <div style={{ textAlign: 'center', padding: '18px 0', color: '#adb5bd', fontSize: 13 }}>No se encontraron sedes con ese criterio.</div>
                ) : (
                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(240px, 1fr))', gap: 10 }}>
                    {sedes.map(c => {
                      const nv = nivelDisponibilidad(c.practicantes, c.capacidad)
                      const sel = b.consultorio === nombreSede(c)
                      return (
                        <div key={c.id} onClick={() => editarBorrador('consultorio', nombreSede(c))}
                          style={{ border: `1.5px solid ${sel ? '#1a2744' : '#e9ecef'}`, borderRadius: 9, padding: '12px 14px', cursor: 'pointer', background: '#fff', display: 'flex', alignItems: 'flex-start', gap: 10 }}>
                          <div style={{ width: 16, height: 16, borderRadius: '50%', border: `2px solid ${sel ? '#1a2744' : '#ced4da'}`, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0, marginTop: 2 }}>
                            {sel && <span style={{ width: 8, height: 8, borderRadius: '50%', background: '#1a2744' }} />}
                          </div>
                          <div style={{ flex: 1 }}>
                            <div style={{ fontWeight: 700, color: '#1a2744', fontSize: 13 }}>{c.nombre}</div>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 4, color: '#adb5bd', fontSize: 11.5, marginTop: 3 }}><Icon name="map_pin" size={11} /> {c.direccion}</div>
                            <span style={{ display: 'inline-flex', alignItems: 'center', gap: 5, background: nv.bg, color: nv.color, fontSize: 10.5, fontWeight: 700, padding: '2px 9px', borderRadius: 20, marginTop: 7 }}>
                              <span style={{ width: 6, height: 6, borderRadius: '50%', background: nv.accent, flexShrink: 0 }} /> {nv.label}
                            </span>
                          </div>
                        </div>
                      )
                    })}
                  </div>
                )}

                <div style={{ marginTop: 22, paddingTop: 18, borderTop: '1px solid #f1f3f5' }}>
                  <div style={{ fontSize: 12, fontWeight: 700, color: '#c0392b', letterSpacing: '0.05em', marginBottom: 8 }}>CANCELAR PRÁCTICA</div>
                  {cancelada ? (
                    <div style={{ background: '#fdecea', color: '#c0392b', borderRadius: 9, padding: '14px 16px', fontSize: 13, fontWeight: 500 }}>
                      Esta práctica se encuentra cancelada. Puede reactivarla cambiando el estado en los datos del practicante.
                    </div>
                  ) : (
                    <>
                      <div style={{ color: '#6c757d', fontSize: 12.5, marginBottom: 12 }}>La cancelación marca la práctica como <strong>Cancelada</strong> y queda registrada con su motivo.</div>
                      <div style={{ display: 'flex', gap: 10, alignItems: 'flex-end', flexWrap: 'wrap' }}>
                        <div style={{ flex: 1, minWidth: 220 }}>
                          <FormField label="Motivo de la cancelación">
                            <select style={selectStyle} value={motivoCancel} onChange={e => setMotivoCancel(e.target.value)}>
                              <option value="">Seleccione un motivo...</option>
                              <option>Conducta del practicante</option>
                              <option>Decisión del practicante</option>
                              <option>Error de registro</option>
                              <option>Otro motivo</option>
                            </select>
                          </FormField>
                        </div>
                        <button onClick={cancelarPracticaFicha} disabled={!motivoCancel}
                          style={{ background: motivoCancel ? '#c0392b' : '#e0a9a3', color: '#fff', border: 'none', padding: '9px 18px', borderRadius: 7, fontWeight: 700, fontSize: 13, cursor: motivoCancel ? 'pointer' : 'not-allowed', display: 'flex', alignItems: 'center', gap: 6, flexShrink: 0 }}>
                          <Icon name="x" size={15} /> Cancelar práctica
                        </button>
                      </div>
                    </>
                  )}
                </div>
              </div>

              <div style={{ borderTop: '1px solid #e9ecef', padding: '14px 24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 12, flexWrap: 'wrap', flexShrink: 0 }}>
                <span style={{ color: guardado ? '#2e7d32' : cambioSede ? '#e65100' : '#6c757d', fontSize: 12.5, fontWeight: guardado || cambioSede ? 600 : 400 }}>
                  {guardado
                    ? 'Cambios guardados correctamente.'
                    : cambioSede
                      ? `Se trasladará a la sede: ${b.consultorio}`
                      : 'Los cambios se aplican al guardar.'}
                </span>
                <div style={{ display: 'flex', gap: 8 }}>
                  <button onClick={cerrarFicha} style={{ background: '#fff', color: '#495057', border: '1.5px solid #dee2e6', padding: '8px 18px', borderRadius: 7, fontWeight: 600, fontSize: 13, cursor: 'pointer' }}>Cerrar</button>
                  <button onClick={guardarFicha} style={{ background: '#1a2744', color: '#fff', border: 'none', padding: '8px 18px', borderRadius: 7, fontWeight: 700, fontSize: 13, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6 }}>
                    <Icon name="check" size={15} /> Guardar cambios
                  </button>
                </div>
              </div>
            </div>
          </div>
        )
      })()}

      {/* Modal — detalle de la sede y ampliación de cupos */}
      {sedeAbierta && (() => {
        const c = sedeAbierta
        const nv = nivelDisponibilidad(c.practicantes, c.capacidad)
        const original = consultorios.find(x => x.id === c.id)
        const agregados = original ? c.capacidad - original.capacidad : 0
        const asignados = fichas.filter(p => p.consultorio === nombreSede(c) && p.estado !== 'Cancelada')
        return (
          <div onClick={() => setSedeId(null)} style={{ position: 'fixed', inset: 0, background: 'rgba(26,39,68,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000, padding: 20 }}>
            <div onClick={e => e.stopPropagation()} style={{ background: '#fff', borderRadius: 12, width: '100%', maxWidth: 560, maxHeight: '88vh', display: 'flex', flexDirection: 'column', boxShadow: '0 20px 60px rgba(0,0,0,0.35)', overflow: 'hidden' }}>
              <div style={{ background: '#1a2744', padding: '18px 24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexShrink: 0 }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                  <div style={{ width: 38, height: 38, borderRadius: 9, background: 'rgba(255,255,255,0.14)', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                    <Icon name="building" size={19} />
                  </div>
                  <div>
                    <div style={{ color: '#fff', fontSize: 16, fontWeight: 700 }}>{c.nombre}</div>
                    <div style={{ color: 'rgba(255,255,255,0.65)', fontSize: 12.5 }}>{c.direccion}</div>
                  </div>
                </div>
                <button onClick={() => setSedeId(null)} style={{ border: 'none', background: 'rgba(255,255,255,0.12)', color: '#fff', width: 32, height: 32, borderRadius: 8, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}><Icon name="x" size={15} /></button>
              </div>

              <div style={{ padding: '20px 24px', overflowY: 'auto' }}>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 10, marginBottom: 20 }}>
                  {[
                    { label: 'Cupos totales', valor: c.capacidad, color: '#1a2744' },
                    { label: 'Ocupados', valor: c.practicantes, color: '#2980b9' },
                    { label: 'Disponibles', valor: c.capacidad - c.practicantes, color: nv.color },
                  ].map(k => (
                    <div key={k.label} style={{ background: '#f8f9fa', borderRadius: 9, padding: '13px 14px', textAlign: 'center' }}>
                      <div style={{ fontSize: 22, fontWeight: 700, color: k.color, lineHeight: 1.1 }}>{k.valor}</div>
                      <div style={{ color: '#6c757d', fontSize: 11.5, marginTop: 3 }}>{k.label}</div>
                    </div>
                  ))}
                </div>

                <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 8 }}>
                  <span style={{ color: '#6c757d', fontSize: 12.5 }}>Disponibilidad:</span>
                  <span style={{ display: 'inline-flex', alignItems: 'center', gap: 5, background: nv.bg, color: nv.color, fontSize: 11, fontWeight: 700, padding: '3px 10px', borderRadius: 20 }}>
                    <span style={{ width: 6, height: 6, borderRadius: '50%', background: nv.accent, flexShrink: 0 }} /> {nv.label}
                  </span>
                </div>
                <div style={{ background: '#f1f3f5', borderRadius: 4, height: 8, overflow: 'hidden', marginBottom: 22 }}>
                  <div style={{ height: '100%', width: `${(c.practicantes / c.capacidad) * 100}%`, background: nv.accent, borderRadius: 4 }} />
                </div>

                <div style={{ fontSize: 12, fontWeight: 700, color: '#1a2744', letterSpacing: '0.05em', marginBottom: 8 }}>AGREGAR CUPOS</div>
                <div style={{ color: '#6c757d', fontSize: 12.5, marginBottom: 12 }}>Amplíe la capacidad de la sede para recibir más practicantes. No es posible dejar menos cupos que practicantes ya asignados.</div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12, background: '#f8f9fa', borderRadius: 9, padding: '14px 16px' }}>
                  <button onClick={() => ampliarCupos(c.id, -1)} disabled={c.capacidad <= c.practicantes}
                    style={{ width: 36, height: 36, borderRadius: 8, border: 'none', background: c.capacidad > c.practicantes ? '#e9ecef' : '#f1f3f5', color: c.capacidad > c.practicantes ? '#495057' : '#ced4da', fontSize: 20, fontWeight: 700, cursor: c.capacidad > c.practicantes ? 'pointer' : 'not-allowed', flexShrink: 0, lineHeight: 1 }}>−</button>
                  <div style={{ flex: 1, textAlign: 'center' }}>
                    <div style={{ fontSize: 26, fontWeight: 700, color: '#1a2744', lineHeight: 1.1 }}>{c.capacidad}</div>
                    <div style={{ color: '#6c757d', fontSize: 11.5, marginTop: 2 }}>cupos configurados</div>
                  </div>
                  <button onClick={() => ampliarCupos(c.id, 1)}
                    style={{ width: 36, height: 36, borderRadius: 8, border: 'none', background: '#1a2744', color: '#fff', fontSize: 20, fontWeight: 700, cursor: 'pointer', flexShrink: 0, lineHeight: 1 }}>+</button>
                </div>
                {agregados !== 0 && (
                  <div style={{ marginTop: 10, display: 'flex', alignItems: 'center', gap: 7, color: agregados > 0 ? '#2e7d32' : '#e65100', fontSize: 12, fontWeight: 600 }}>
                    <Icon name="info" size={13} />
                    {agregados > 0 ? `${agregados} cupo(s) agregados a esta sede.` : `${Math.abs(agregados)} cupo(s) retirados de esta sede.`}
                  </div>
                )}

                <div style={{ marginTop: 22, paddingTop: 18, borderTop: '1px solid #f1f3f5' }}>
                  <div style={{ fontSize: 12, fontWeight: 700, color: '#1a2744', letterSpacing: '0.05em', marginBottom: 10 }}>PRACTICANTES EN ESTA SEDE</div>
                  {asignados.length === 0 ? (
                    <div style={{ color: '#adb5bd', fontSize: 12.5 }}>No hay practicantes asignados a esta sede.</div>
                  ) : (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                      {asignados.map(p => (
                        <div key={p.id} style={{ display: 'flex', alignItems: 'center', gap: 10, background: '#f8f9fa', borderRadius: 8, padding: '9px 12px' }}>
                          <Avatar ini={p.practicante.split(' ').map(n => n[0]).join('').slice(0, 2)} color={p.uniColor} size={30} />
                          <div style={{ flex: 1, minWidth: 0 }}>
                            <div style={{ fontWeight: 600, color: '#1a2744', fontSize: 12.5 }}>{p.practicante}</div>
                            <div style={{ color: '#6c757d', fontSize: 11 }}>{p.universidad}</div>
                          </div>
                          <StatusBadge estado={p.estado} />
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </div>

              <div style={{ borderTop: '1px solid #e9ecef', padding: '14px 24px', display: 'flex', justifyContent: 'flex-end', flexShrink: 0 }}>
                <button onClick={() => setSedeId(null)} style={{ background: '#1a2744', color: '#fff', border: 'none', padding: '8px 18px', borderRadius: 7, fontWeight: 700, fontSize: 13, cursor: 'pointer' }}>Listo</button>
              </div>
            </div>
          </div>
        )
      })()}

      {/* Modal — alta de usuario */}
      {usuarioNuevo && (
        <div onClick={() => setUsuarioNuevo(null)} style={{ position: 'fixed', inset: 0, background: 'rgba(26,39,68,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000, padding: 20 }}>
          <div onClick={e => e.stopPropagation()} style={{ background: '#fff', borderRadius: 12, width: '100%', maxWidth: 480, boxShadow: '0 20px 60px rgba(0,0,0,0.35)', overflow: 'hidden' }}>
            <div style={{ background: '#1a2744', padding: '18px 24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                <Avatar ini={iniciales(usuarioNuevo.nombre) || '??'} color="#5b7fd4" size={38} />
                <div>
                  <div style={{ color: '#fff', fontSize: 16, fontWeight: 700 }}>Nuevo usuario</div>
                  <div style={{ color: 'rgba(255,255,255,0.65)', fontSize: 12.5 }}>{usuarioNuevo.nombre.trim() || 'Complete los datos de acceso'}</div>
                </div>
              </div>
              <button onClick={() => setUsuarioNuevo(null)} style={{ border: 'none', background: 'rgba(255,255,255,0.12)', color: '#fff', width: 32, height: 32, borderRadius: 8, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}><Icon name="x" size={15} /></button>
            </div>
            <div style={{ padding: '22px 24px', display: 'flex', flexDirection: 'column', gap: 14 }}>
              <FormField label="Nombre completo">
                <input style={inputStyle} autoFocus placeholder="Ej: María González Pérez"
                  value={usuarioNuevo.nombre} onChange={e => { setErrorNuevo(''); setUsuarioNuevo(u => u && { ...u, nombre: e.target.value }) }} />
              </FormField>
              <FormField label="Correo electrónico">
                <input style={inputStyle} placeholder="nombre.apellido@cajbiobio.cl"
                  value={usuarioNuevo.email} onChange={e => { setErrorNuevo(''); setUsuarioNuevo(u => u && { ...u, email: e.target.value }) }} />
              </FormField>
              <FormField label="Rol en el sistema">
                <select style={selectStyle} value={usuarioNuevo.rol} onChange={e => setUsuarioNuevo(u => u && { ...u, rol: e.target.value })}>
                  {roles.filter(r => r !== 'Todos').map(r => <option key={r}>{r}</option>)}
                </select>
              </FormField>
              {errorNuevo && (
                <div style={{ background: '#fdecea', color: '#c0392b', borderRadius: 8, padding: '9px 13px', fontSize: 12.5, fontWeight: 600 }}>{errorNuevo}</div>
              )}
              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 6 }}>
                <button onClick={() => setUsuarioNuevo(null)} style={{ background: '#fff', color: '#495057', border: '1.5px solid #dee2e6', padding: '8px 18px', borderRadius: 7, fontWeight: 600, fontSize: 13, cursor: 'pointer' }}>Cancelar</button>
                <button onClick={crearUsuario}
                  style={{ background: '#1a2744', color: '#fff', border: 'none', padding: '8px 18px', borderRadius: 7, fontWeight: 700, fontSize: 13, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6 }}>
                  <Icon name="check" size={15} /> Crear usuario
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Modal — edición de usuario */}
      {usuarioEdit && (
        <div onClick={() => setUsuarioEdit(null)} style={{ position: 'fixed', inset: 0, background: 'rgba(26,39,68,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000, padding: 20 }}>
          <div onClick={e => e.stopPropagation()} style={{ background: '#fff', borderRadius: 12, width: '100%', maxWidth: 480, boxShadow: '0 20px 60px rgba(0,0,0,0.35)', overflow: 'hidden' }}>
            <div style={{ background: '#1a2744', padding: '18px 24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                <Avatar ini={usuarioEdit.ini} color={usuarioEdit.color} size={38} />
                <div>
                  <div style={{ color: '#fff', fontSize: 16, fontWeight: 700 }}>Editar usuario</div>
                  <div style={{ color: 'rgba(255,255,255,0.65)', fontSize: 12.5 }}>{usuarioEdit.nombre}</div>
                </div>
              </div>
              <button onClick={() => setUsuarioEdit(null)} style={{ border: 'none', background: 'rgba(255,255,255,0.12)', color: '#fff', width: 32, height: 32, borderRadius: 8, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}><Icon name="x" size={15} /></button>
            </div>
            <div style={{ padding: '22px 24px', display: 'flex', flexDirection: 'column', gap: 14 }}>
              <FormField label="Nombre completo">
                <input style={inputStyle} value={usuarioEdit.nombre} onChange={e => setUsuarioEdit(u => u && { ...u, nombre: e.target.value })} />
              </FormField>
              <FormField label="Correo electrónico">
                <input style={inputStyle} value={usuarioEdit.email} onChange={e => setUsuarioEdit(u => u && { ...u, email: e.target.value })} />
              </FormField>
              <FormField label="Rol en el sistema">
                <select style={selectStyle} value={usuarioEdit.rol} onChange={e => setUsuarioEdit(u => u && { ...u, rol: e.target.value })}>
                  {roles.filter(r => r !== 'Todos').map(r => <option key={r}>{r}</option>)}
                </select>
              </FormField>
              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 6 }}>
                <button onClick={() => setUsuarioEdit(null)} style={{ background: '#fff', color: '#495057', border: '1.5px solid #dee2e6', padding: '8px 18px', borderRadius: 7, fontWeight: 600, fontSize: 13, cursor: 'pointer' }}>Cerrar</button>
                <button onClick={() => { setUsuarios(us => us.map(x => x.id === usuarioEdit.id ? usuarioEdit : x)); setUsuarioEdit(null) }}
                  style={{ background: '#1a2744', color: '#fff', border: 'none', padding: '8px 18px', borderRadius: 7, fontWeight: 700, fontSize: 13, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6 }}>
                  <Icon name="check" size={15} /> Guardar cambios
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

function DashboardPracticante() {
  const notasInstancias = practicanteData.calificaciones.filter(c => c.nota !== null).map(c => c.nota as number)
  const promedio = notasInstancias.length ? notasInstancias.reduce((a, b) => a + b, 0) / notasInstancias.length : null

  const finalizada = practicanteData.estado === 'Finalizada'

  const femenino = practicanteData.genero === 'F'
  const tratamiento = femenino ? 'la postulante, doña' : 'el postulante, don'
  const tituloProfesional = femenino ? 'Abogada' : 'Abogado'

  const hoyDate = new Date()
  const dia = hoyDate.getDate()
  const mes = hoyDate.toLocaleDateString('es-CL', { month: 'long' })
  const anio = hoyDate.getFullYear()
  const fechaCertificado = `${dia} de ${mes.charAt(0).toUpperCase()}${mes.slice(1)} de ${anio}`
  const fechaResolucion = `${dia} de ${mes} ${anio}`
  const fechaArchivo = [String(dia).padStart(2, '0'), String(hoyDate.getMonth() + 1).padStart(2, '0'), anio].join('-')

  const nombreArchivo = practicanteData.nombre.split(' ').slice(0, 2).join(' ')
  const nombreLegalMayus = practicanteData.nombreLegal.toUpperCase()
  const resNumero = String(practicanteData.resolucion.numero).padStart(4, '0')

  const descargarCertificado = () => {
    if (!finalizada) return
    const win = window.open('', '_blank', 'width=920,height=720')
    if (!win) return
    const html = `<!doctype html>
<html lang="es"><head><meta charset="utf-8"><title>Certificado Corte Suprema-${nombreArchivo}-${fechaArchivo}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  @page { size: A4; margin: 0; }
  body { font-family: 'Times New Roman', Times, serif; color: #000; background: #fff; font-size: 12.5pt; }
  .page { width: 210mm; min-height: 297mm; padding: 25mm 25mm 20mm; margin: 0 auto; display: flex; flex-direction: column; }
  .logo { width: 42mm; margin-bottom: 18mm; }
  .title { text-align: center; font-weight: 700; font-size: 15pt; letter-spacing: 6px; margin-bottom: 14mm; }
  p { text-align: justify; line-height: 2; text-indent: 12mm; margin-bottom: 7mm; }
  .sign { margin-top: 26mm; text-align: center; font-weight: 700; line-height: 1.45; padding-left: 55mm; }
  .cc { margin-top: auto; padding-top: 20mm; font-size: 11pt; line-height: 1.4; }
</style></head>
<body onload="setTimeout(function(){window.print()},250)">
  <div class="page">
    <img class="logo" src="${certLogo}" alt="Corporación de Asistencia Judicial — Biobío">

    <div class="title">C E R T I F I C A D O</div>

    <p><b>MAURICIO DECAP FERNÁNDEZ</b>, Director General de la Corporación de Asistencia Judicial de la Región del Biobío, CERTIFICA que ${tratamiento} <b>${nombreLegalMayus}, RUT N.º ${practicanteData.rut}, HA APROBADO,</b> la práctica profesional reglamentaria para optar al Título de ${tituloProfesional}.</p>

    <p>Este certificado se otorga para el único efecto de ser presentado ante la Excelentísima Corte Suprema de Justicia al momento de apertura de expediente de titulación, según Instructivo sobre Juramento de Abogados.</p>

    <p style="text-indent:0; margin-top:12mm;">Concepción, ${fechaCertificado}.</p>

    <div class="sign">
      MAURICIO DECAP FERNÁNDEZ<br>
      DIRECTOR GENERAL<br>
      CAJ REGIÓN DEL BIOBÍO
    </div>

    <div class="cc">c.c.-<br>-Dirección de Acceso a la Justicia.</div>
  </div>
</body></html>`
    win.document.write(html)
    win.document.close()
  }

  const descargarResolucion = () => {
    if (!finalizada) return
    const win = window.open('', '_blank', 'width=920,height=720')
    if (!win) return
    const conceptos = encuestaItems
      .map((item, i) => `
      <div class="concepto">
        <span class="letra">${String.fromCharCode(97 + i)})</span>
        <span class="criterio">${item.label.toUpperCase()}</span>
        <span class="valor">: ${conceptoDeNota(practicanteData.evaluacion[item.id])}</span>
      </div>`)
      .join('')
    const html = `<!doctype html>
<html lang="es"><head><meta charset="utf-8"><title>Resolución N°${resNumero}-${anio}-${nombreArchivo}-${fechaArchivo}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  @page { size: A4; margin: 0; }
  body { font-family: 'Times New Roman', Times, serif; color: #000; background: #fff; font-size: 11.5pt; }
  .page { width: 210mm; min-height: 297mm; padding: 18mm 25mm 14mm; margin: 0 auto; display: flex; flex-direction: column; }
  .page + .page { page-break-before: always; }
  .logo { width: 36mm; margin-bottom: 5mm; }
  .encabezado { margin-left: 78mm; font-weight: 700; line-height: 1.8; }
  .encabezado .num { margin-bottom: 5mm; }
  .encabezado .materia { text-align: justify; }
  .lugar { margin: 6mm 0 6mm 78mm; font-weight: 700; }
  .rotulo { font-weight: 700; margin-left: 18mm; margin-bottom: 3mm; }
  p { text-align: justify; line-height: 1.7; text-indent: 22mm; margin-bottom: 4mm; }
  .concepto { display: flex; line-height: 2.1; }
  .concepto .letra { width: 14mm; flex-shrink: 0; }
  .concepto .criterio { width: 86mm; flex-shrink: 0; }
  .conceptos { margin: 8mm 0 10mm; }
  .cierre { text-align: center; font-weight: 700; margin: 10mm 0; }
  .sign { margin-top: 22mm; text-align: center; font-weight: 700; line-height: 1.45; padding-left: 55mm; }
  .dist { margin-top: auto; padding-top: 18mm; font-size: 11pt; line-height: 1.5; }
  .dist b { text-decoration: underline; }
</style></head>
<body onload="setTimeout(function(){window.print()},250)">
  <div class="page">
    <img class="logo" src="${certLogo}" alt="Corporación de Asistencia Judicial — Biobío">

    <div class="encabezado">
      <div class="num">RESOLUCIÓN N.° ${practicanteData.resolucion.numero}/${practicanteData.resolucion.anio}</div>
      <div class="materia">APRUEBA PRÁCTICA PROFESIONAL DE POSTULANTE AL TÍTULO DE ${tituloProfesional.toUpperCase()} DE ${femenino ? 'DOÑA' : 'DON'} ${nombreLegalMayus}.</div>
    </div>

    <div class="lugar">Concepción, ${fechaResolucion}.</div>

    <div class="rotulo">VISTOS:</div>
    <p>Lo dispuesto en la Ley N°19.880, que establece las Bases de los Procedimientos Administrativos que rigen los actos de los órganos de la Administración del Estado; en el numeral 5° del artículo 523 del Código Orgánico de Tribunales; en el artículo 2 de la Ley 17.995, que Concede Personalidad Jurídica a los Servicios de Asistencia Jurídica que se indican en las Regiones que se señalan; en la letra i) del artículo 19 de los Estatutos de la Corporación de Asistencia Judicial de la Región del Biobío, aprobados en el Decreto con fuerza de Ley N° 994 del año 1981; en los artículos 24 al 26 del Reglamento de Práctica Profesional de Postulantes al Título de Abogado contenido en el Decreto Supremo N° 265, del Ministerio de Justicia, de 1985; en el Protocolo que regula las condiciones generales para la realización de la Práctica Profesional de los Postulantes al título de Abogado suscrito entre el Ministerio de Justicia y las Corporaciones de Asistencia Judicial el 10 de agosto de 2009; en el Instructivo para la tramitación de expedientes de Juramento de Abogadas y Abogados contenido en el Auto Acordado de la Corte Suprema N°192-2015; el certificado de licenciatura en Ciencias Jurídicas de ${femenino ? 'doña' : 'don'} <b>${nombreLegalMayus},</b> emitido por la ${practicanteData.universidad}.</p>

    <div class="rotulo">CONSIDERANDO:</div>
    <p>Oficio N.º ${practicanteData.resolucion.oficio} del Director de Acceso a la Justicia de la Institución, de fecha ${fechaResolucion}, mediante el cual informa que ${femenino ? 'doña' : 'don'} <b>${nombreLegalMayus},</b> ${femenino ? 'licenciada' : 'licenciado'} en Ciencias Jurídicas de la ${practicanteData.universidad}, ha cumplido satisfactoriamente con la práctica profesional dispuesta en el numeral 5° del artículo 523 del Código Orgánico de Tribunales, realizada en la Oficina Jurídica de Atención de la comuna de ${practicanteData.resolucion.comuna}, entre los días ${practicanteData.inicio} al ${practicanteData.termino}.</p>
  </div>

  <div class="page">
    <div class="rotulo">RESUELVO:</div>
    <p><b>1° APRUÉBASE</b> práctica profesional de ${femenino ? 'doña' : 'don'} <b>${nombreLegalMayus},</b> cédula de identidad <b>RUT N.º ${practicanteData.rut},</b> con los siguientes conceptos:</p>

    <div class="conceptos">${conceptos}</div>

    <p><b>2° EXPÍDASE</b> el informe de calificación de la práctica profesional aludida y el certificado de aprobación de la misma, en conformidad a las disposiciones legales y administrativas vigentes.</p>

    <div class="cierre">ANÓTESE, NOTIFÍQUESE Y ARCHÍVESE.</div>

    <div class="sign">
      MAURICIO DECAP FERNÁNDEZ<br>
      DIRECTOR GENERAL<br>
      CAJ REGIÓN DEL BIOBÍO
    </div>

    <div class="dist">
      <b>DISTRIBUCIÓN:</b><br>
      - Interesado/a.<br>
      - Director acceso a la Justicia.<br>
      - Encargado de sistemas informáticos.<br>
      - Encargada de transparencia activa.<br>
      - Archivo Dirección General.
    </div>
  </div>
</body></html>`
    win.document.write(html)
    win.document.close()
  }

  const documentos = [
    {
      id: 'certificado',
      archivo: `Certificado Corte Suprema-${nombreArchivo}-${fechaArchivo}.pdf`,
      detalle: 'Certificado de aprobación para la Corte Suprema',
      descargar: descargarCertificado,
    },
    {
      id: 'resolucion',
      archivo: `Resolución N°${resNumero}-${anio}-${nombreArchivo}-${fechaArchivo}.pdf`,
      detalle: 'Resolución que aprueba la práctica, con los conceptos de calificación',
      descargar: descargarResolucion,
    },
  ]

  return (
    <div style={{ padding: '28px 28px 48px' }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 24 }}>
        <div>
          <h1 style={{ fontSize: 22, fontWeight: 700, color: '#1a2744', margin: 0 }}>{practicanteData.nombre}</h1>
          <div style={{ color: '#6c757d', fontSize: 13.5, marginTop: 3 }}>{practicanteData.universidad} · Practicante</div>
          <Breadcrumb items={['Inicio', 'Vista Practicante', practicanteData.nombre]} />
        </div>
        <div style={{ background: '#fff', border: '1.5px solid #e9ecef', borderRadius: 10, padding: '10px 14px', color: '#495057', fontWeight: 600, fontSize: 13 }}>
          <StatusBadge estado={practicanteData.estado} />
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1.1fr 0.9fr', gap: 18 }}>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
          <Card style={{ padding: '22px 24px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 16 }}>
              <Icon name="calendar" size={16} />
              <span style={{ fontWeight: 700, color: '#1a2744', fontSize: 15 }}>Detalle de la práctica</span>
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 }}>
              {[
                { icon: 'building', label: 'Consultorio asignado', value: practicanteData.consultorio },
                { icon: 'users', label: 'Abogado tutor', value: practicanteData.abogado },
                { icon: 'mail', label: 'Correo', value: practicanteData.abogadoEmail },
                { icon: 'phone', label: 'Teléfono', value: practicanteData.abogadoTelefono },
              ].map((item, i) => (
                <div key={i} style={{ background: '#f8f9fa', borderRadius: 8, padding: '12px 14px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 6, color: '#6c757d', fontSize: 11.5, fontWeight: 600, marginBottom: 4, letterSpacing: '0.04em' }}>
                    <Icon name={item.icon} size={12} />
                    {item.label.toUpperCase()}
                  </div>
                  <div style={{ color: '#1a2744', fontSize: 13.5, fontWeight: 500 }}>{item.value}</div>
                </div>
              ))}
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10, marginTop: 12 }}>
              <div style={{ background: '#e8f4fd', borderRadius: 8, padding: '12px 14px' }}>
                <div style={{ color: '#2980b9', fontSize: 11.5, fontWeight: 700, marginBottom: 4 }}>FECHA DE INICIO</div>
                <div style={{ color: '#1a2744', fontSize: 13.5, fontWeight: 600 }}>{practicanteData.inicio}</div>
              </div>
              <div style={{ background: '#fff3e0', borderRadius: 8, padding: '12px 14px' }}>
                <div style={{ color: '#e67e22', fontSize: 11.5, fontWeight: 700, marginBottom: 4 }}>FECHA DE TÉRMINO</div>
                <div style={{ color: '#1a2744', fontSize: 13.5, fontWeight: 600 }}>{practicanteData.termino}</div>
              </div>
            </div>
          </Card>

          <Card style={{ padding: '22px 24px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6 }}>
              <Icon name="clipboard" size={16} />
              <span style={{ fontWeight: 700, color: '#1a2744', fontSize: 15 }}>Calificaciones</span>
            </div>
            <div style={{ color: '#6c757d', fontSize: 12.5, marginBottom: 16 }}>La primera instancia se califica al término de la práctica. La segunda y tercera instancia son opcionales, según corresponda a cada práctica. Todas las notas se registran en escala de 1,0 a 7,0.</div>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
              {practicanteData.calificaciones.map(item => {
                const opcional = item.tipo === 'Opcional'
                const badge = opcional ? { bg: '#f1f3f5', color: '#6c757d' } : { bg: '#e8f4fd', color: '#1565c0' }
                return (
                  <div key={item.instancia} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 12, padding: '12px 14px', background: '#f8f9fa', borderRadius: 8, opacity: opcional && item.nota === null ? 0.85 : 1 }}>
                    <div>
                      <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap' }}>
                        <span style={{ fontWeight: 700, color: '#1a2744', fontSize: 13 }}>{item.instancia}</span>
                        <span style={{ background: badge.bg, color: badge.color, fontSize: 10, fontWeight: 700, letterSpacing: '0.03em', padding: '1px 8px', borderRadius: 20 }}>{item.tipo.toUpperCase()}</span>
                      </div>
                      <div style={{ color: '#6c757d', fontSize: 12, marginTop: 2 }}>{item.detalle}</div>
                    </div>
                    {item.nota !== null
                      ? <div style={{ background: '#1a2744', color: '#fff', fontWeight: 700, padding: '7px 12px', borderRadius: 8, minWidth: 54, textAlign: 'center' }}>{item.nota.toFixed(1)}</div>
                      : <div style={{ background: '#fff', color: '#adb5bd', border: '1.5px solid #e9ecef', fontWeight: 600, fontSize: 12, padding: '7px 12px', borderRadius: 8, minWidth: 54, textAlign: 'center' }}>Sin nota</div>}
                  </div>
                )
              })}
            </div>

            {/* Notas finales (separadas) */}
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10, marginTop: 12, paddingTop: 16, borderTop: '1px solid #f1f3f5' }}>
              <div style={{ background: '#eef7f0', border: '1.5px solid #cde9d5', borderRadius: 8, padding: '12px 14px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 10 }}>
                <div>
                  <div style={{ fontWeight: 700, color: '#1a2744', fontSize: 13 }}>Nota de la práctica</div>
                  <div style={{ color: '#6c757d', fontSize: 11.5, marginTop: 2 }}>Evaluación global del desempeño</div>
                </div>
                <div style={{ background: '#27ae60', color: '#fff', fontWeight: 700, padding: '7px 12px', borderRadius: 8, minWidth: 54, textAlign: 'center' }}>{practicanteData.notaPractica.toFixed(1)}</div>
              </div>
              <div style={{ background: '#eef4fb', border: '1.5px solid #cfe0f2', borderRadius: 8, padding: '12px 14px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 10 }}>
                <div>
                  <div style={{ fontWeight: 700, color: '#1a2744', fontSize: 13 }}>Nota promedio</div>
                  <div style={{ color: '#6c757d', fontSize: 11.5, marginTop: 2 }}>Promedio de las instancias calificadas</div>
                </div>
                {promedio !== null
                  ? <div style={{ background: '#2980b9', color: '#fff', fontWeight: 700, padding: '7px 12px', borderRadius: 8, minWidth: 54, textAlign: 'center' }}>{promedio.toFixed(1)}</div>
                  : <div style={{ background: '#fff', color: '#adb5bd', border: '1.5px solid #e9ecef', fontWeight: 600, fontSize: 12, padding: '7px 12px', borderRadius: 8, minWidth: 54, textAlign: 'center' }}>—</div>}
              </div>
            </div>
          </Card>
        </div>

        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
          <Card style={{ padding: '22px 24px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6 }}>
              <Icon name="doc" size={16} />
              <span style={{ fontWeight: 700, color: '#1a2744', fontSize: 15 }}>Documentación</span>
            </div>
            <div style={{ color: '#6c757d', fontSize: 12.5, marginBottom: 16 }}>Los documentos oficiales de la práctica se generan automáticamente con el nombre de cada practicante.</div>

            {!finalizada && (
              <div style={{ marginBottom: 14, display: 'flex', alignItems: 'center', gap: 7, color: '#e65100', fontSize: 12, fontWeight: 600 }}>
                <Icon name="info" size={13} /> La práctica aún se encuentra en estado "{practicanteData.estado}".
              </div>
            )}

            <div>
              <div style={{ fontWeight: 700, color: '#1a2744', fontSize: 13, marginBottom: 4 }}>Documentos generados</div>
              <div style={{ color: '#6c757d', fontSize: 12, marginBottom: 12 }}>Estos documentos se desbloquean automáticamente tras haber recibido la calificación final de la práctica realizada.</div>
              <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                {documentos.map(docItem => (
                  <div key={docItem.id} style={{ display: 'flex', alignItems: 'center', gap: 10, background: '#f8f9fa', border: '1px solid #eef0f2', borderRadius: 8, padding: '10px 12px' }}>
                    <div style={{ width: 34, height: 34, borderRadius: 7, background: finalizada ? '#e8f5e9' : '#f1f3f5', color: finalizada ? '#2e7d32' : '#adb5bd', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                      <Icon name="doc" size={16} />
                    </div>
                    <div style={{ flex: 1, minWidth: 0 }}>
                      <div style={{ fontWeight: 600, color: '#1a2744', fontSize: 12.5, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{docItem.archivo}</div>
                      <div style={{ color: '#6c757d', fontSize: 11, marginTop: 1 }}>{docItem.detalle}</div>
                      <div style={{ color: finalizada ? '#2e7d32' : '#adb5bd', fontSize: 11, marginTop: 1, fontWeight: 600 }}>{finalizada ? 'Disponible' : 'Pendiente de calificación final'}</div>
                    </div>
                    <button onClick={docItem.descargar} disabled={!finalizada}
                      style={{ background: finalizada ? '#1a2744' : '#e9ecef', color: finalizada ? '#fff' : '#adb5bd', border: 'none', padding: '7px 12px', borderRadius: 7, fontWeight: 700, fontSize: 12, cursor: finalizada ? 'pointer' : 'not-allowed', display: 'flex', alignItems: 'center', gap: 6, flexShrink: 0 }}>
                      <Icon name={finalizada ? 'download' : 'lock'} size={13} /> {finalizada ? 'Descargar' : 'Bloqueado'}
                    </button>
                  </div>
                ))}
              </div>
            </div>
          </Card>
        </div>
      </div>
    </div>
  )
}

const encuestaItems = [
  { id: 'conocimiento', label: 'Conocimiento y criterio jurídico', desc: 'Aplica correctamente los conceptos legales' },
  { id: 'responsabilidad', label: 'Responsabilidad', desc: 'Entrega trabajos a tiempo y de forma completa' },
  { id: 'iniciativa', label: 'Iniciativa', desc: 'Propone soluciones y actúa sin esperar instrucciones' },
  { id: 'sentidoSocial', label: 'Sentido social y de colaboración', desc: 'Muestra compromiso con los usuarios y el equipo' },
  { id: 'conducta', label: 'Conducta', desc: 'Mantiene un trato profesional y respetuoso' },
  { id: 'honorabilidad', label: 'Honorabilidad', desc: 'Mantiene confidencialidad y actúa con integridad' },
  { id: 'asistencia', label: 'Asistencia y puntualidad', desc: 'Cumple con horarios y asiste regularmente' },
]

function DashboardAbogado() {
  const abogado = abogados[0]
  const misPracticantes = practicas.filter(p => p.abogadoIni === abogado.ini)
  const [selectedPracticante, setSelectedPracticante] = useState(misPracticantes[0])

  const [buscaPracticante, setBuscaPracticante] = useState('')
  const q = buscaPracticante.trim().toLowerCase()
  const practicantesFiltrados = q
    ? misPracticantes.filter(p => [p.practicante, p.universidad, p.estado, p.consultorio].some(c => c.toLowerCase().includes(q)))
    : misPracticantes
  const [notifOpen, setNotifOpen] = useState(false)
  const [ratings, setRatings] = useState<Record<string, number>>({})
  const [comentario, setComentario] = useState('')
  const [encuestaEnviada, setEncuestaEnviada] = useState(false)
  const [notifRead, setNotifRead] = useState<number[]>([])

  const notificaciones = [
    { id: 1, tipo: 'urgente', titulo: 'Práctica por finalizar', msg: `La práctica de Javiera Monsalve Reyes termina el 30/07/2026. Queda menos de 1 mes.`, fecha: 'Hoy, 09:14', leida: false },
    { id: 2, tipo: 'aviso', titulo: 'Encuesta de desempeño pendiente', msg: `Debe completar la encuesta de desempeño del 3° mes de Catalina Vera Muñoz.`, fecha: 'Ayer, 16:30', leida: false },
    { id: 3, tipo: 'info', titulo: 'Nuevo practicante asignado', msg: `Se ha asignado a Diego Fuentes Alarcón a su cargo para el período 01/07/2026 – 31/12/2026.`, fecha: '28/07/2026', leida: true },
  ]
  const unread = notificaciones.filter(n => !n.leida && !notifRead.includes(n.id)).length

  return (
    <div style={{ padding: '28px 28px 48px' }}>
      {/* Header del abogado */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 28 }}>
        <div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 14 }}>
            <Avatar ini={abogado.ini} color={abogado.color} size={52} />
            <div>
              <h1 style={{ fontSize: 22, fontWeight: 700, color: '#1a2744', margin: 0 }}>{abogado.nombre}</h1>
              <div style={{ color: '#6c757d', fontSize: 13.5, marginTop: 3 }}>{abogado.area} · Abogado Tutor</div>
              <div style={{ display: 'flex', gap: 8, marginTop: 8 }}>
                <span style={{ background: '#e8f5e9', color: '#2e7d32', fontSize: 12, fontWeight: 600, padding: '3px 10px', borderRadius: 20 }}>● {abogado.activos} practicantes activos</span>
                <span style={{ background: '#f1f3f5', color: '#495057', fontSize: 12, fontWeight: 600, padding: '3px 10px', borderRadius: 20 }}>Consultorio Centro — Concepción</span>
              </div>
            </div>
          </div>
          <Breadcrumb items={['Inicio', 'Vista Abogado', abogado.nombre]} />
        </div>

        {/* Bell / notificaciones */}
        <div style={{ position: 'relative' }}>
          <button onClick={() => setNotifOpen(o => !o)} style={{ position: 'relative', background: '#fff', border: '1.5px solid #e9ecef', borderRadius: 10, padding: '10px 14px', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 8, color: '#495057', fontWeight: 600, fontSize: 13 }}>
            <Icon name="bell" size={17} />
            Notificaciones
            {unread > 0 && <span style={{ background: '#c0392b', color: '#fff', fontSize: 10, fontWeight: 700, padding: '1px 6px', borderRadius: 10 }}>{unread}</span>}
          </button>

          {notifOpen && (
            <div style={{ position: 'absolute', right: 0, top: '110%', width: 360, background: '#fff', borderRadius: 12, border: '1px solid #e9ecef', boxShadow: '0 8px 32px rgba(0,0,0,0.12)', zIndex: 100 }}>
              <div style={{ padding: '14px 18px', borderBottom: '1px solid #f1f3f5', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <span style={{ fontWeight: 700, color: '#1a2744', fontSize: 14 }}>Notificaciones</span>
                <button onClick={() => { setNotifRead([1, 2, 3]); }} style={{ border: 'none', background: 'none', color: '#6c757d', fontSize: 12, cursor: 'pointer' }}>Marcar todas como leídas</button>
              </div>
              {notificaciones.map(n => {
                const isRead = n.leida || notifRead.includes(n.id)
                const tipoCfg = n.tipo === 'urgente' ? { bg: '#fff3e0', border: '#e67e22', icon: 'bell', iconColor: '#e67e22' }
                  : n.tipo === 'aviso' ? { bg: '#fff8e1', border: '#f39c12', icon: 'clipboard', iconColor: '#f39c12' }
                  : { bg: '#e8f4fd', border: '#2980b9', icon: 'info', iconColor: '#2980b9' }
                return (
                  <div key={n.id} onClick={() => setNotifRead(prev => [...prev, n.id])} style={{ padding: '14px 18px', borderBottom: '1px solid #f8f9fa', background: isRead ? '#fff' : tipoCfg.bg, cursor: 'pointer', transition: 'background 0.15s' }}>
                    <div style={{ display: 'flex', gap: 12, alignItems: 'flex-start' }}>
                      <div style={{ width: 32, height: 32, borderRadius: 8, border: `1.5px solid ${tipoCfg.border}`, display: 'flex', alignItems: 'center', justifyContent: 'center', color: tipoCfg.iconColor, flexShrink: 0, background: '#fff' }}>
                        <Icon name={tipoCfg.icon} size={15} />
                      </div>
                      <div style={{ flex: 1 }}>
                        <div style={{ fontWeight: isRead ? 500 : 700, color: '#1a2744', fontSize: 13 }}>{n.titulo}</div>
                        <div style={{ color: '#6c757d', fontSize: 12, marginTop: 3, lineHeight: 1.5 }}>{n.msg}</div>
                        <div style={{ color: '#adb5bd', fontSize: 11, marginTop: 5 }}>{n.fecha}</div>
                      </div>
                      {!isRead && <span style={{ width: 8, height: 8, borderRadius: '50%', background: '#c0392b', flexShrink: 0, marginTop: 4 }} />}
                    </div>
                  </div>
                )
              })}
              <div style={{ padding: '10px 18px', textAlign: 'center' }}>
                <button onClick={() => setNotifOpen(false)} style={{ border: 'none', background: 'none', color: '#6c757d', fontSize: 12.5, cursor: 'pointer' }}>Cerrar</button>
              </div>
            </div>
          )}
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '300px 1fr', gap: 18 }}>
        {/* Lista de practicantes */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '0 2px' }}>
            <span style={{ fontWeight: 700, color: '#1a2744', fontSize: 13, letterSpacing: '0.04em' }}>MIS PRACTICANTES</span>
            <span style={{ color: '#adb5bd', fontSize: 11.5 }}>{practicantesFiltrados.length} de {misPracticantes.length}</span>
          </div>

          <div style={{ position: 'relative' }}>
            <span style={{ position: 'absolute', left: 11, top: '50%', transform: 'translateY(-50%)', color: '#adb5bd', display: 'flex', pointerEvents: 'none' }}><Icon name="search" size={14} /></span>
            <input
              value={buscaPracticante}
              onChange={e => setBuscaPracticante(e.target.value)}
              placeholder="Buscar por nombre, universidad o estado"
              style={{ width: '100%', padding: '9px 32px 9px 34px', border: '1.5px solid #e9ecef', borderRadius: 9, fontSize: 12.5, outline: 'none', color: '#343a40', background: '#fff', fontFamily: 'Inter, sans-serif' }}
            />
            {buscaPracticante && (
              <button onClick={() => setBuscaPracticante('')} title="Limpiar búsqueda"
                style={{ position: 'absolute', right: 9, top: '50%', transform: 'translateY(-50%)', border: 'none', background: 'transparent', color: '#adb5bd', cursor: 'pointer', display: 'flex' }}>
                <Icon name="x" size={13} />
              </button>
            )}
          </div>

          {practicantesFiltrados.length === 0 && (
            <div style={{ background: '#fff', border: '1.5px dashed #e9ecef', borderRadius: 10, padding: '24px 16px', textAlign: 'center', color: '#adb5bd', fontSize: 12.5 }}>
              Ningún practicante coincide con "{buscaPracticante}"
            </div>
          )}

          {practicantesFiltrados.map(p => (
            <div key={p.id} onClick={() => setSelectedPracticante(p)}
              style={{ background: selectedPracticante.id === p.id ? '#1a2744' : '#fff', border: `1.5px solid ${selectedPracticante.id === p.id ? '#1a2744' : '#e9ecef'}`, borderRadius: 10, padding: '14px 16px', cursor: 'pointer', transition: 'all 0.15s' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 8 }}>
                <Avatar ini={p.practicante.split(' ').map(n => n[0]).join('').slice(0, 2)} color={p.uniColor} size={34} />
                <div>
                  <div style={{ fontWeight: 700, color: selectedPracticante.id === p.id ? '#fff' : '#1a2744', fontSize: 13 }}>{p.practicante}</div>
                  <div style={{ color: selectedPracticante.id === p.id ? 'rgba(255,255,255,0.6)' : '#6c757d', fontSize: 11.5 }}>{p.universidad}</div>
                </div>
              </div>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <StatusBadge estado={p.estado} />
                <span style={{ color: selectedPracticante.id === p.id ? 'rgba(255,255,255,0.5)' : '#adb5bd', fontSize: 11 }}>Termina {p.termino}</span>
              </div>
            </div>
          ))}
        </div>

        {/* Panel derecho */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
          {/* Ficha practicante */}
          <Card style={{ padding: '22px 24px' }}>
            <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', marginBottom: 18 }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 14 }}>
                <Avatar ini={selectedPracticante.practicante.split(' ').map(n => n[0]).join('').slice(0, 2)} color={selectedPracticante.uniColor} size={48} />
                <div>
                  <div style={{ fontWeight: 700, fontSize: 17, color: '#1a2744' }}>{selectedPracticante.practicante}</div>
                  <div style={{ color: '#6c757d', fontSize: 13, marginTop: 2 }}>{selectedPracticante.universidad}</div>
                  <div style={{ marginTop: 6 }}><StatusBadge estado={selectedPracticante.estado} /></div>
                </div>
              </div>
              <div style={{ textAlign: 'right' }}>
                <div style={{ color: '#6c757d', fontSize: 12 }}>Período de práctica</div>
                <div style={{ fontWeight: 700, color: '#1a2744', fontSize: 13, marginTop: 2 }}>{selectedPracticante.inicio} – {selectedPracticante.termino}</div>
              </div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 }}>
              {[
                { icon: 'phone', label: 'Teléfono', value: selectedPracticante.tel },
                { icon: 'mail', label: 'Correo electrónico', value: selectedPracticante.email },
                { icon: 'building', label: 'Consultorio asignado', value: selectedPracticante.consultorio },
                { icon: 'heart', label: 'Discapacidad', value: selectedPracticante.discapacidad },
              ].map((item, i) => (
                <div key={i} style={{ background: '#f8f9fa', borderRadius: 8, padding: '12px 14px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 6, color: '#6c757d', fontSize: 11.5, fontWeight: 600, marginBottom: 4, letterSpacing: '0.04em' }}>
                    <Icon name={item.icon} size={12} />
                    {item.label.toUpperCase()}
                  </div>
                  <div style={{ color: '#1a2744', fontSize: 13.5, fontWeight: 500 }}>{item.value}</div>
                </div>
              ))}
            </div>

            {/* Alerta si queda menos de 1 mes */}
            {selectedPracticante.estado === 'Por terminar' && (
              <div style={{ marginTop: 14, background: '#fff3e0', border: '1.5px solid #e67e22', borderRadius: 8, padding: '12px 16px', display: 'flex', alignItems: 'flex-start', gap: 10 }}>
                <span style={{ color: '#e67e22', flexShrink: 0 }}><Icon name="bell" size={17} /></span>
                <div>
                  <div style={{ fontWeight: 700, color: '#c0392b', fontSize: 13 }}>Proximidad al cierre de práctica</div>
                  <div style={{ color: '#6c757d', fontSize: 12.5, marginTop: 2 }}>La práctica de <strong>{selectedPracticante.practicante}</strong> finaliza el <strong>{selectedPracticante.termino}</strong>. Recuerde completar el informe de cierre y la encuesta final de desempeño.</div>
                </div>
              </div>
            )}
          </Card>

          {/* Encuesta de desempeño */}
          <Card style={{ padding: '22px 24px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6 }}>
              <Icon name="clipboard" size={16} />
              <span style={{ fontWeight: 700, color: '#1a2744', fontSize: 15 }}>Encuesta de desempeño</span>
            </div>
            <div style={{ color: '#6c757d', fontSize: 12.5, marginBottom: 18 }}>Encuesta final de la práctica. Califique cada criterio con nota de 1,0 a 7,0.</div>

            {encuestaEnviada ? (() => {
              const valores = Object.values(ratings)
              const promedioEncuesta = valores.length ? valores.reduce((a, b) => a + b, 0) / valores.length : 0
              const aprobado = promedioEncuesta >= 4
              return (
                <div style={{ textAlign: 'center', padding: '30px 20px' }}>
                  <div style={{ width: 56, height: 56, borderRadius: '50%', background: aprobado ? '#e8f5e9' : '#fdecea', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 14px', color: aprobado ? '#27ae60' : '#c0392b' }}><Icon name="check" size={28} /></div>
                  <div style={{ fontWeight: 700, color: '#1a2744', fontSize: 16, marginBottom: 6 }}>Encuesta enviada</div>
                  <div style={{ color: '#6c757d', fontSize: 13.5, marginBottom: 14 }}>La evaluación de desempeño fue registrada correctamente.</div>
                  <div style={{ display: 'inline-block', background: aprobado ? '#27ae60' : '#c0392b', color: '#fff', fontWeight: 700, fontSize: 12.5, letterSpacing: '0.03em', padding: '6px 16px', borderRadius: 20 }}>
                    {aprobado ? 'HA APROBADO' : 'NO HA APROBADO'}
                  </div>
                  <button onClick={() => { setEncuestaEnviada(false); setRatings({}); setComentario('') }} style={{ display: 'block', margin: '18px auto 0', border: '1.5px solid #dee2e6', background: '#fff', color: '#495057', padding: '8px 20px', borderRadius: 8, fontWeight: 600, fontSize: 13, cursor: 'pointer' }}>Nueva encuesta</button>
                </div>
              )
            })() : (
              <>
                <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
                  {encuestaItems.map(item => (
                    <div key={item.id} style={{ display: 'flex', alignItems: 'center', gap: 14, padding: '12px 14px', background: '#f8f9fa', borderRadius: 9 }}>
                      <div style={{ flex: 1 }}>
                        <div style={{ fontWeight: 600, color: '#1a2744', fontSize: 13 }}>{item.label}</div>
                        <div style={{ color: '#adb5bd', fontSize: 12, marginTop: 2 }}>{item.desc}</div>
                      </div>
                      <input
                        type="number"
                        min={1}
                        max={7}
                        step={0.1}
                        value={ratings[item.id] ?? ''}
                        onChange={e => {
                          const raw = e.target.value
                          if (raw === '') { setRatings(r => { const { [item.id]: _, ...rest } = r; return rest }); return }
                          const v = Math.min(7, Math.max(1, parseFloat(raw)))
                          setRatings(r => ({ ...r, [item.id]: v }))
                        }}
                        placeholder="1,0 – 7,0"
                        style={{ width: 108, padding: '10px 12px', border: '1.5px solid #dee2e6', borderRadius: 8, fontSize: 17, fontWeight: 700, color: '#1a2744', textAlign: 'center', outline: 'none', fontFamily: 'Inter, sans-serif', flexShrink: 0 }}
                      />
                    </div>
                  ))}
                </div>
                <div style={{ marginTop: 16 }}>
                  <div style={{ fontWeight: 600, color: '#495057', fontSize: 13, marginBottom: 8 }}>Comentarios generales</div>
                  <textarea
                    value={comentario}
                    onChange={e => setComentario(e.target.value)}
                    placeholder="Agregue observaciones, logros destacados o áreas de mejora del practicante..."
                    style={{ width: '100%', padding: '10px 13px', border: '1.5px solid #dee2e6', borderRadius: 8, fontSize: 13.5, outline: 'none', color: '#343a40', background: '#fff', fontFamily: 'Inter, sans-serif', minHeight: 90, resize: 'vertical', boxSizing: 'border-box' }}
                  />
                </div>
                <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: 16 }}>
                  <button
                    onClick={() => Object.keys(ratings).length === encuestaItems.length && setEncuestaEnviada(true)}
                    style={{ background: Object.keys(ratings).length === encuestaItems.length ? '#1a2744' : '#ced4da', color: '#fff', border: 'none', padding: '11px 24px', borderRadius: 8, fontWeight: 700, fontSize: 14, cursor: Object.keys(ratings).length === encuestaItems.length ? 'pointer' : 'not-allowed', display: 'flex', alignItems: 'center', gap: 8 }}
                  >
                    <Icon name="send" size={15} />
                    Enviar evaluación
                  </button>
                </div>
              </>
            )}
          </Card>
        </div>
      </div>
    </div>
  )
}

const regiones = ['Región del Biobío', 'Región Metropolitana', 'Región de La Araucanía', 'Región del Maule', 'Región de Los Lagos', 'Región de Valparaíso', 'Otra región']
const comunas: Record<string, string[]> = {
  'Región del Biobío': ['Concepción', 'Talcahuano', 'San Pedro de la Paz', 'Chillán', 'Los Ángeles', 'Penco', 'Coronel'],
  'Región Metropolitana': ['Santiago', 'Providencia', 'Las Condes', 'Maipú', 'Puente Alto'],
  'Región de La Araucanía': ['Temuco', 'Villarrica', 'Pucón', 'Angol'],
  'Región del Maule': ['Talca', 'Curicó', 'Linares'],
  'Región de Los Lagos': ['Puerto Montt', 'Osorno', 'Castro'],
  'Región de Valparaíso': ['Valparaíso', 'Viña del Mar', 'Quilpué'],
  'Otra región': ['Otra comuna'],
}

function FormField({ label, required, children }: { label: string; required?: boolean; children: React.ReactNode }) {
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 5 }}>
      <label style={{ fontSize: 12.5, fontWeight: 600, color: '#495057', letterSpacing: '0.02em' }}>
        {label} {required && <span style={{ color: '#c0392b' }}>*</span>}
      </label>
      {children}
    </div>
  )
}

function RequiredNote() {
  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: 6, color: '#6c757d', fontSize: 12.5, marginBottom: 20 }}>
      <span style={{ color: '#c0392b', fontWeight: 700 }}>*</span>
      <span>Los campos marcados con asterisco son obligatorios.</span>
    </div>
  )
}

const inputStyle: React.CSSProperties = { padding: '9px 12px', border: '1.5px solid #dee2e6', borderRadius: 8, fontSize: 13.5, outline: 'none', color: '#343a40', background: '#fff', width: '100%', fontFamily: 'Inter, sans-serif', transition: 'border-color 0.15s' }
const selectStyle: React.CSSProperties = { ...inputStyle, appearance: 'none', backgroundImage: `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236c757d' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E")`, backgroundRepeat: 'no-repeat', backgroundPosition: 'right 12px center', paddingRight: 36 }

const consultoriosPostulacion = [
  { comuna: 'Concepción', consultorio: 'Consultorio Centro — Concepción', direccion: "O'Higgins 440, piso 3" },
  { comuna: 'Talcahuano', consultorio: 'Consultorio Talcahuano', direccion: 'Colón 1262' },
  { comuna: 'San Pedro de la Paz', consultorio: 'Consultorio San Pedro de la Paz', direccion: 'Av. Gran Bretaña 3035' },
  { comuna: 'Coronel', consultorio: 'Consultorio Coronel', direccion: 'Manuel Rodríguez 481' },
  { comuna: 'Los Ángeles', consultorio: 'Consultorio Los Ángeles', direccion: 'Caupolicán 380' },
]

const comunaCoords: Record<string, [number, number]> = {
  'Concepción': [-36.827, -73.050],
  'Talcahuano': [-36.724, -73.117],
  'San Pedro de la Paz': [-36.842, -73.107],
  'Coronel': [-37.028, -73.158],
  'Los Ángeles': [-37.469, -72.353],
  'Chillán': [-36.606, -72.103],
  'Penco': [-36.740, -72.995],
}

function distanciaComuna(a: string, b: string) {
  const ca = comunaCoords[a]; const cb = comunaCoords[b]
  if (!ca || !cb) return Infinity
  const dLat = ca[0] - cb[0]; const dLng = ca[1] - cb[1]
  return dLat * dLat + dLng * dLng
}

const feriadosCL = new Set([
  '2026-01-01', '2026-04-03', '2026-04-04', '2026-05-01', '2026-05-21', '2026-06-21',
  '2026-06-29', '2026-07-16', '2026-08-15', '2026-09-18', '2026-09-19', '2026-10-12',
  '2026-10-31', '2026-11-01', '2026-12-08', '2026-12-25',
  '2027-01-01', '2027-03-26', '2027-03-27', '2027-05-01', '2027-05-21', '2027-06-21',
  '2027-06-29', '2027-07-16', '2027-08-15', '2027-09-18', '2027-09-19', '2027-10-12',
  '2027-10-31', '2027-11-01', '2027-12-08', '2027-12-25',
])

function ymd(d: Date) {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

const nombresMes = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
const diasSemana = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do']

function DatePicker({ value, onChange }: { value: string; onChange: (v: string) => void }) {
  const today = new Date(); today.setHours(0, 0, 0, 0)
  const base = value ? new Date(value + 'T00:00:00') : today
  const [view, setView] = useState({ year: base.getFullYear(), month: base.getMonth() })

  const startWeekday = (new Date(view.year, view.month, 1).getDay() + 6) % 7
  const daysInMonth = new Date(view.year, view.month + 1, 0).getDate()

  const cells: (Date | null)[] = []
  for (let i = 0; i < startWeekday; i++) cells.push(null)
  for (let d = 1; d <= daysInMonth; d++) cells.push(new Date(view.year, view.month, d))

  const dayState = (d: Date) => {
    const wd = d.getDay()
    if (wd === 0 || wd === 6) return 'weekend'
    if (feriadosCL.has(ymd(d))) return 'holiday'
    if (d < today) return 'past'
    return 'ok'
  }

  const prevDisabled = view.year < today.getFullYear() || (view.year === today.getFullYear() && view.month <= today.getMonth())
  const changeMonth = (delta: number) => setView(v => {
    const m = v.month + delta
    if (m < 0) return { year: v.year - 1, month: 11 }
    if (m > 11) return { year: v.year + 1, month: 0 }
    return { year: v.year, month: m }
  })

  const navBtn = (disabled: boolean): React.CSSProperties => ({ border: '1px solid #dee2e6', background: '#fff', color: disabled ? '#ced4da' : '#495057', width: 30, height: 30, borderRadius: 7, cursor: disabled ? 'not-allowed' : 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' })

  return (
    <div style={{ border: '1.5px solid #dee2e6', borderRadius: 10, padding: 14, background: '#fff', maxWidth: 320 }}>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 }}>
        <button type="button" disabled={prevDisabled} onClick={() => !prevDisabled && changeMonth(-1)} style={navBtn(prevDisabled)}><Icon name="chevron_left" size={15} /></button>
        <span style={{ fontWeight: 700, color: '#1a2744', fontSize: 14 }}>{nombresMes[view.month]} {view.year}</span>
        <button type="button" onClick={() => changeMonth(1)} style={navBtn(false)}><Icon name="chevron_right" size={15} /></button>
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(7, 1fr)', gap: 3, marginBottom: 4 }}>
        {diasSemana.map((d, i) => (
          <div key={d} style={{ textAlign: 'center', fontSize: 11, fontWeight: 700, color: i >= 5 ? '#c0392b' : '#adb5bd', padding: '2px 0' }}>{d}</div>
        ))}
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(7, 1fr)', gap: 3 }}>
        {cells.map((d, i) => {
          if (!d) return <div key={i} />
          const state = dayState(d)
          const disabled = state !== 'ok'
          const selected = value === ymd(d)
          return (
            <button key={i} type="button" disabled={disabled} onClick={() => onChange(ymd(d))}
              title={state === 'holiday' ? 'Feriado' : state === 'weekend' ? 'Fin de semana' : undefined}
              style={{ height: 34, border: 'none', borderRadius: 7, fontSize: 12.5, fontWeight: selected ? 700 : 500, cursor: disabled ? 'not-allowed' : 'pointer', background: selected ? '#1a2744' : disabled ? 'transparent' : '#f1f3f5', color: selected ? '#fff' : state === 'holiday' ? '#c0392b' : disabled ? '#ced4da' : '#343a40', textDecoration: state === 'holiday' ? 'line-through' : 'none' }}>
              {d.getDate()}
            </button>
          )
        })}
      </div>
      <div style={{ display: 'flex', gap: 14, marginTop: 12, fontSize: 11, color: '#6c757d' }}>
        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 5 }}><span style={{ width: 10, height: 10, borderRadius: 3, background: '#1a2744' }} /> Seleccionada</span>
        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 5 }}><span style={{ color: '#c0392b', fontWeight: 700, textDecoration: 'line-through' }}>15</span> Feriado / fin de semana</span>
      </div>
    </div>
  )
}

const MAX_ARCHIVO_MB = 5

const formatoTamaño = (bytes: number) =>
  bytes < 1024 * 1024 ? `${Math.max(1, Math.round(bytes / 1024))} KB` : `${(bytes / (1024 * 1024)).toFixed(1)} MB`

type LadoCarnet = { id: 'frontal' | 'reverso'; label: string; hint: string }

const ladosCarnet: LadoCarnet[] = [
  { id: 'frontal', label: 'Cédula — lado frontal', hint: 'Cara con la fotografía, nombre y RUN.' },
  { id: 'reverso', label: 'Cédula — lado reverso', hint: 'Cara con el número de documento y código de barras.' },
]

function CamaraModal({ titulo, onCapture, onClose }: {
  titulo: string
  onCapture: (file: File) => void
  onClose: () => void
}) {
  const videoRef = useRef<HTMLVideoElement>(null)
  const streamRef = useRef<MediaStream | null>(null)
  const [error, setError] = useState('')

  useEffect(() => {
    let cancelado = false
    navigator.mediaDevices?.getUserMedia({ video: { facingMode: 'environment' } })
      .then(stream => {
        if (cancelado) { stream.getTracks().forEach(t => t.stop()); return }
        streamRef.current = stream
        if (videoRef.current) videoRef.current.srcObject = stream
      })
      .catch(() => setError('No se pudo acceder a la cámara. Revise los permisos del navegador o suba una imagen.'))
    return () => {
      cancelado = true
      streamRef.current?.getTracks().forEach(t => t.stop())
    }
  }, [])

  const capturar = () => {
    const video = videoRef.current
    if (!video || !video.videoWidth) return
    const canvas = document.createElement('canvas')
    canvas.width = video.videoWidth
    canvas.height = video.videoHeight
    canvas.getContext('2d')?.drawImage(video, 0, 0)
    canvas.toBlob(blob => {
      if (!blob) return
      onCapture(new File([blob], `cedula-${Date.now()}.jpg`, { type: 'image/jpeg' }))
    }, 'image/jpeg', 0.92)
  }

  return (
    <div onClick={onClose} style={{ position: 'fixed', inset: 0, background: 'rgba(15,20,35,0.72)', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20, zIndex: 100 }}>
      <div onClick={e => e.stopPropagation()} style={{ background: '#fff', borderRadius: 12, overflow: 'hidden', width: '100%', maxWidth: 560, boxShadow: '0 20px 50px rgba(0,0,0,0.3)' }}>
        <div style={{ background: '#1a2744', padding: '14px 20px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <span style={{ color: '#fff', fontSize: 14.5, fontWeight: 700 }}>{titulo}</span>
          <button type="button" onClick={onClose} style={{ border: 'none', background: 'transparent', color: 'rgba(255,255,255,0.7)', cursor: 'pointer', display: 'flex' }}><Icon name="x" size={16} /></button>
        </div>
        <div style={{ padding: 20 }}>
          {error ? (
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, color: '#c0392b', fontSize: 13 }}>
              <Icon name="alert" size={15} /> {error}
            </div>
          ) : (
            <>
              <div style={{ position: 'relative', background: '#000', borderRadius: 10, overflow: 'hidden' }}>
                <video ref={videoRef} autoPlay playsInline muted style={{ width: '100%', display: 'block' }} />
                {/* Guía de encuadre con proporción aproximada de la cédula */}
                <div style={{ position: 'absolute', inset: '12%', border: '2px dashed rgba(255,255,255,0.75)', borderRadius: 10, pointerEvents: 'none' }} />
              </div>
              <div style={{ color: '#6c757d', fontSize: 12.5, marginTop: 10 }}>
                Encuadre la cédula dentro del recuadro, evite reflejos y asegúrese de que el texto se lea con nitidez.
              </div>
            </>
          )}
          <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 10, marginTop: 16 }}>
            <button type="button" onClick={onClose} style={{ background: '#fff', color: '#495057', border: '1.5px solid #dee2e6', padding: '9px 18px', borderRadius: 8, fontWeight: 600, fontSize: 13.5, cursor: 'pointer' }}>Cancelar</button>
            {!error && (
              <button type="button" onClick={capturar} style={{ background: '#1a2744', color: '#fff', border: 'none', padding: '9px 20px', borderRadius: 8, fontWeight: 700, fontSize: 13.5, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 8 }}>
                <Icon name="check" size={15} /> Capturar
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}

function EscaneoCarnet({ lado, archivo, onSet, onClear }: {
  lado: LadoCarnet
  archivo: File | null
  onSet: (file: File) => void
  onClear: () => void
}) {
  const inputRef = useRef<HTMLInputElement>(null)
  const [camara, setCamara] = useState(false)
  const [error, setError] = useState('')
  const [preview, setPreview] = useState('')

  useEffect(() => {
    if (!archivo) { setPreview(''); return }
    const url = URL.createObjectURL(archivo)
    setPreview(url)
    return () => URL.revokeObjectURL(url)
  }, [archivo])

  const recibir = (file: File | undefined) => {
    if (!file) return
    if (!file.type.startsWith('image/')) { setError('La cédula debe adjuntarse como imagen (JPG o PNG).'); return }
    if (file.size > MAX_ARCHIVO_MB * 1024 * 1024) { setError(`La imagen pesa ${formatoTamaño(file.size)}; el máximo es ${MAX_ARCHIVO_MB} MB.`); return }
    setError('')
    onSet(file)
  }

  const botón: React.CSSProperties = { border: '1.5px solid #dee2e6', background: '#fff', color: '#495057', padding: '8px 14px', borderRadius: 8, fontWeight: 600, fontSize: 12.5, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 7 }

  return (
    <div style={{ border: `1.5px solid ${error ? '#e9b0a8' : archivo ? '#bfe3cd' : '#e9ecef'}`, borderRadius: 10, padding: '14px 16px', background: archivo ? '#f6fbf8' : '#fff' }}>
      <div style={{ display: 'flex', alignItems: 'baseline', gap: 6, marginBottom: 2 }}>
        <span style={{ fontSize: 13.5, fontWeight: 700, color: '#1a2744' }}>{lado.label}</span>
        <span style={{ color: '#c0392b', fontWeight: 700 }}>*</span>
        {archivo && <span style={{ marginLeft: 'auto', display: 'inline-flex', alignItems: 'center', gap: 4, color: '#27ae60', fontSize: 11.5, fontWeight: 700 }}><Icon name="check" size={12} /> Capturado</span>}
      </div>
      <div style={{ color: '#6c757d', fontSize: 12, marginBottom: 10 }}>{lado.hint}</div>

      {preview ? (
        <div style={{ position: 'relative', borderRadius: 8, overflow: 'hidden', border: '1px solid #e9ecef', background: '#f8f9fa' }}>
          <img src={preview} alt={lado.label} style={{ width: '100%', display: 'block', maxHeight: 220, objectFit: 'contain' }} />
        </div>
      ) : (
        <div
          onClick={() => inputRef.current?.click()}
          style={{ border: '1.5px dashed #dee2e6', borderRadius: 8, padding: '18px 12px', textAlign: 'center', cursor: 'pointer', background: '#f8f9fa' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8, color: '#495057', fontSize: 12.5, fontWeight: 600 }}>
            <Icon name="doc" size={15} /> Sin imagen del {lado.id === 'frontal' ? 'frente' : 'reverso'}
          </div>
          <div style={{ color: '#adb5bd', fontSize: 11, marginTop: 4 }}>JPG o PNG · máximo {MAX_ARCHIVO_MB} MB</div>
        </div>
      )}

      <input ref={inputRef} type="file" accept="image/*" capture="environment" onChange={e => { recibir(e.target.files?.[0]); if (inputRef.current) inputRef.current.value = '' }} style={{ display: 'none' }} />

      <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8, marginTop: 10 }}>
        <button type="button" onClick={() => setCamara(true)} style={{ ...botón, background: '#2980b9', color: '#fff', border: 'none' }}>
          <Icon name="eye" size={14} /> {archivo ? 'Volver a escanear' : 'Escanear con cámara'}
        </button>
        <button type="button" onClick={() => inputRef.current?.click()} style={botón}>
          <Icon name="upload" size={14} /> Subir imagen
        </button>
        {archivo && (
          <button type="button" onClick={() => { setError(''); onClear() }} style={{ ...botón, color: '#c0392b', borderColor: '#f2d6d2' }}>
            <Icon name="x" size={14} /> Quitar
          </button>
        )}
      </div>

      {error && (
        <div style={{ display: 'flex', alignItems: 'center', gap: 6, color: '#c0392b', fontSize: 12, marginTop: 8 }}>
          <Icon name="alert" size={13} /> {error}
        </div>
      )}

      {camara && (
        <CamaraModal
          titulo={lado.label}
          onCapture={file => { setError(''); onSet(file); setCamara(false) }}
          onClose={() => setCamara(false)}
        />
      )}
    </div>
  )
}

function PanelValidacionCedula({ frontal, reverso }: { frontal: File | null; reverso: File | null }) {
  const [estado, setEstado] = useState<'inicial' | 'analizando' | 'listo' | 'error'>('inicial')
  const [resultado, setResultado] = useState<ResultadoValidacion | null>(null)
  const [error, setError] = useState('')

  const completo = !!frontal && !!reverso

  useEffect(() => {
    setEstado('inicial')
    setResultado(null)
    setError('')
  }, [frontal, reverso])

  const analizar = async () => {
    if (!frontal || !reverso) return
    setEstado('analizando')
    setError('')
    try {
      setResultado(await validarCedula(frontal, reverso))
      setEstado('listo')
    } catch (e) {
      setError(e instanceof Error ? e.message : 'No se pudo completar la validación.')
      setEstado('error')
    }
  }

  const estilos: Record<Severidad, { color: string; bg: string; borde: string; icon: string }> = {
    ok: { color: '#1e7a49', bg: '#f1f8f4', borde: '#cfe8d9', icon: 'check' },
    advertencia: { color: '#8a5a12', bg: '#fff8ec', borde: '#f5dbb0', icon: 'alert' },
    error: { color: '#a5342a', bg: '#fdecea', borde: '#f2d6d2', icon: 'x' },
  }

  const veredictos = {
    aprobada: { label: 'Cédula validada', color: '#1e7a49', bg: '#f1f8f4', borde: '#cfe8d9', icon: 'check' },
    con_reparos: { label: 'Validada con reparos', color: '#8a5a12', bg: '#fff8ec', borde: '#f5dbb0', icon: 'alert' },
    rechazada: { label: 'No se pudo validar', color: '#a5342a', bg: '#fdecea', borde: '#f2d6d2', icon: 'x' },
  }

  return (
    <div style={{ marginTop: 16, border: '1.5px solid #e9ecef', borderRadius: 10, padding: '16px 18px', background: '#fff' }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 4 }}>
        <span style={{ display: 'flex', color: '#8e6dbf' }}><Icon name="activity" size={15} /></span>
        <span style={{ fontSize: 13, fontWeight: 700, color: '#1a2744', letterSpacing: '0.04em' }}>VALIDACIÓN AUTOMÁTICA</span>
        <span style={{ background: '#f1f3f5', color: '#6c757d', fontSize: 10, fontWeight: 700, letterSpacing: '0.03em', padding: '2px 8px', borderRadius: 20 }}>
          {MODELO_GEMINI.toUpperCase()}
        </span>
      </div>
      <div style={{ color: '#6c757d', fontSize: 12.5, marginBottom: 14 }}>
        Revisa la nitidez de las imágenes, que ambas caras sean de la misma cédula, el dígito verificador del RUN y la vigencia del documento.
      </div>

      {!geminiConfigurado && (
        <div style={{ display: 'flex', alignItems: 'flex-start', gap: 8, background: '#fff8ec', border: '1px solid #f5dbb0', borderRadius: 8, padding: '10px 12px', marginBottom: 12, color: '#8a5a12', fontSize: 12.5 }}>
          <span style={{ display: 'flex', marginTop: 1, flexShrink: 0 }}><Icon name="alert" size={14} /></span>
          <span>Falta configurar <code style={{ background: '#fff', padding: '1px 5px', borderRadius: 4 }}>VITE_GEMINI_API_KEY</code>. Copie <code style={{ background: '#fff', padding: '1px 5px', borderRadius: 4 }}>.env.example</code> como <code style={{ background: '#fff', padding: '1px 5px', borderRadius: 4 }}>.env.local</code>, agregue su clave y reinicie el servidor.</span>
        </div>
      )}

      {estado !== 'listo' && (
        <button
          type="button"
          onClick={analizar}
          disabled={!completo || estado === 'analizando' || !geminiConfigurado}
          title={!completo ? 'Capture ambas caras de la cédula primero' : undefined}
          style={{
            border: 'none',
            background: completo && geminiConfigurado && estado !== 'analizando' ? '#8e6dbf' : '#e9ecef',
            color: completo && geminiConfigurado && estado !== 'analizando' ? '#fff' : '#adb5bd',
            padding: '10px 18px', borderRadius: 8, fontWeight: 700, fontSize: 13,
            cursor: completo && geminiConfigurado && estado !== 'analizando' ? 'pointer' : 'not-allowed',
            display: 'flex', alignItems: 'center', gap: 8,
          }}>
          <Icon name={estado === 'analizando' ? 'clock' : 'shield'} size={15} />
          {estado === 'analizando' ? 'Analizando la cédula…' : 'Validar cédula con IA'}
        </button>
      )}

      {estado === 'analizando' && (
        <div style={{ background: '#f8f9fa', border: '1px solid #e9ecef', borderRadius: 8, height: 6, overflow: 'hidden', marginTop: 12 }}>
          <div style={{ height: '100%', width: '40%', background: '#8e6dbf', borderRadius: 8, animation: 'validacionProgreso 1.1s ease-in-out infinite' }} />
        </div>
      )}

      {estado === 'error' && (
        <div style={{ display: 'flex', alignItems: 'flex-start', gap: 8, background: '#fdecea', border: '1px solid #f2d6d2', borderRadius: 8, padding: '10px 12px', marginTop: 12, color: '#a5342a', fontSize: 12.5 }}>
          <span style={{ display: 'flex', marginTop: 1, flexShrink: 0 }}><Icon name="alert" size={14} /></span>
          <span>{error}</span>
        </div>
      )}

      {estado === 'listo' && resultado && (() => {
        const v = veredictos[resultado.veredicto]
        return (
          <>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10, background: v.bg, border: `1px solid ${v.borde}`, borderRadius: 9, padding: '12px 14px', marginBottom: 12 }}>
              <span style={{ display: 'flex', color: v.color, flexShrink: 0 }}><Icon name={v.icon} size={18} /></span>
              <div style={{ flex: 1, minWidth: 0 }}>
                <div style={{ color: v.color, fontSize: 14, fontWeight: 700 }}>{v.label}</div>
                <div style={{ color: '#6c757d', fontSize: 12.5, marginTop: 2 }}>{resultado.observaciones}</div>
              </div>
              <button type="button" onClick={analizar} title="Volver a analizar"
                style={{ border: '1.5px solid #dee2e6', background: '#fff', color: '#495057', padding: '6px 12px', borderRadius: 7, fontWeight: 600, fontSize: 12, cursor: 'pointer', flexShrink: 0 }}>
                Reintentar
              </button>
            </div>

            {resultado.run && (
              <div style={{ display: 'flex', gap: 20, flexWrap: 'wrap', background: '#f8f9fa', border: '1px solid #e9ecef', borderRadius: 8, padding: '10px 14px', marginBottom: 12 }}>
                <div>
                  <div style={{ color: '#6c757d', fontSize: 10.5, fontWeight: 700, letterSpacing: '0.05em' }}>RUN LEÍDO</div>
                  <div style={{ color: '#1a2744', fontSize: 13.5, fontWeight: 600, marginTop: 2 }}>{resultado.run}</div>
                </div>
                {resultado.fechaVencimiento && (
                  <div>
                    <div style={{ color: '#6c757d', fontSize: 10.5, fontWeight: 700, letterSpacing: '0.05em' }}>VENCIMIENTO</div>
                    <div style={{ color: '#1a2744', fontSize: 13.5, fontWeight: 600, marginTop: 2 }}>{resultado.fechaVencimiento}</div>
                  </div>
                )}
              </div>
            )}

            <div style={{ display: 'flex', flexDirection: 'column', gap: 7 }}>
              {resultado.comprobaciones.map(comp => {
                const s = estilos[comp.severidad]
                return (
                  <div key={comp.id} style={{ display: 'flex', alignItems: 'flex-start', gap: 10, background: s.bg, border: `1px solid ${s.borde}`, borderRadius: 8, padding: '9px 12px' }}>
                    <span style={{ display: 'flex', color: s.color, marginTop: 1, flexShrink: 0 }}><Icon name={s.icon} size={14} /></span>
                    <div style={{ flex: 1, minWidth: 0 }}>
                      <div style={{ color: '#1a2744', fontSize: 12.5, fontWeight: 700 }}>{comp.titulo}</div>
                      <div style={{ color: '#495057', fontSize: 12, marginTop: 2, lineHeight: 1.5 }}>{comp.detalle}</div>
                    </div>
                  </div>
                )
              })}
            </div>

            <div style={{ display: 'flex', alignItems: 'flex-start', gap: 7, marginTop: 12, color: '#adb5bd', fontSize: 11.5, lineHeight: 1.5 }}>
              <span style={{ display: 'flex', marginTop: 1, flexShrink: 0 }}><Icon name="info" size={12} /></span>
              <span>Esta revisión no consulta al Registro Civil. Confirma que la cédula sea legible y coherente, no que sea auténtica.</span>
            </div>
          </>
        )
      })()}
    </div>
  )
}

function Formulario() {
  const [step, setStep] = useState(0)
  const [accepted, setAccepted] = useState(false)
  const [consultoriosSel, setConsultoriosSel] = useState<string[]>([])
  const [consultorioBusqueda, setConsultorioBusqueda] = useState('')

  const [form, setForm] = useState({
    nombre: '', apellido: '', correo: '', telefono: '', genero: '', universidad: '',
    region: '', direccion: '', comuna: '', nacionalidad: '', contactoNombre: '',
    vinculo: '', contactoTelefono: '', contactoCorreo: '', salud: '', rut: '', fechaNac: '',
    discapacidad: '', tieneDiscapacidad: '',
    universidad2: '', añoCarrera: '', especialidad: '',
    fechaInicio: '',
  })
  const set = (k: string, v: string) => setForm(f => ({ ...f, [k]: v }))

  const [carnet, setCarnet] = useState<{ frontal: File | null; reverso: File | null }>({ frontal: null, reverso: null })
  const carnetCompleto = !!carnet.frontal && !!carnet.reverso
  const toggleConsultorio = (comuna: string) =>
    setConsultoriosSel(prev => prev.includes(comuna) ? prev.filter(c => c !== comuna) : [...prev, comuna])

  const steps = ['Carta de presentación', 'Datos personales', 'Antecedentes educativos', 'Consultorio y fecha', 'Resumen']

  return (
    <div style={{ minHeight: '100vh', background: '#f1f3f5', display: 'flex', flexDirection: 'column', alignItems: 'center', padding: '40px 20px 60px' }}>
      <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', marginBottom: 32 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 14, marginBottom: 8 }}>
          <div style={{ width: 56, height: 56, background: '#1a2744', borderRadius: 10, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="1.5"><path d="M12 2L3 7v10l9 5 9-5V7L12 2z"/><path d="M12 12v7"/><path d="M12 12l7-4"/><path d="M12 12L5 8"/></svg>
          </div>
          <div>
            <div style={{ fontFamily: "'Roboto Slab', serif", fontSize: 17, fontWeight: 700, color: '#1a2744', lineHeight: 1.2 }}>Corporación de Asistencia Judicial</div>
            <div style={{ color: '#c0392b', fontSize: 12, fontWeight: 700, letterSpacing: '0.12em' }}>REGIÓN DEL BIOBÍO</div>
          </div>
        </div>
        <div style={{ color: '#6c757d', fontSize: 13.5 }}>Sistema de Gestión de Practicantes — Formulario de Postulación</div>
      </div>

      {/* Progress */}
      <div style={{ width: '100%', maxWidth: 720, marginBottom: 28 }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
          {steps.map((s, i) => {
            const n = i + 1; const done = step > n; const active = step === n
            return (
              <div key={i} style={{ display: 'flex', alignItems: 'center' }}>
                <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 6 }}>
                  <div style={{ width: 36, height: 36, borderRadius: '50%', background: done ? '#27ae60' : active ? '#1a2744' : '#dee2e6', color: done || active ? '#fff' : '#adb5bd', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 700, fontSize: 14, transition: 'all 0.2s' }}>
                    {done ? <Icon name="check" size={16} /> : n}
                  </div>
                  <span style={{ fontSize: 11.5, fontWeight: active ? 700 : 500, color: active ? '#1a2744' : done ? '#27ae60' : '#adb5bd', whiteSpace: 'nowrap' }}>{s}</span>
                </div>
                {i < steps.length - 1 && <div style={{ width: 80, height: 2, background: step > n ? '#27ae60' : '#dee2e6', margin: '0 8px', marginBottom: 24, transition: 'background 0.2s' }} />}
              </div>
            )
          })}
        </div>
      </div>

      <div style={{ width: '100%', maxWidth: 720 }}>
        {step === 0 && (
          <div style={{ background: '#fff', borderRadius: 12, border: '1px solid #e9ecef', overflow: 'hidden', boxShadow: '0 2px 12px rgba(0,0,0,0.06)' }}>
            <div style={{ background: '#1a2744', padding: '20px 28px' }}>
              <h2 style={{ color: '#fff', fontSize: 18, fontWeight: 700, margin: 0 }}>Ingresar postulación</h2>
              <p style={{ color: 'rgba(255,255,255,0.65)', fontSize: 13, marginTop: 4, marginBottom: 0 }}>Pulse el botón para avanzar al siguiente paso de la postulación.</p>
            </div>
            <div style={{ padding: '28px' }}>
              <div style={{ display: 'flex', justifyContent: 'center' }}>
                <button onClick={() => setStep(1)} style={{ background: '#2980b9', color: '#fff', border: 'none', padding: '16px 28px', borderRadius: 12, fontWeight: 700, fontSize: 15, cursor: 'pointer', boxShadow: '0 12px 24px rgba(41, 128, 185, 0.18)' }}>
                  Ingresar postulación
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Step 1 */}
        {step === 1 && (
          <div style={{ background: '#fff', borderRadius: 12, border: '1px solid #e9ecef', overflow: 'hidden', boxShadow: '0 2px 12px rgba(0,0,0,0.06)' }}>
            <div style={{ background: '#1a2744', padding: '20px 28px' }}>
              <h2 style={{ color: '#fff', fontSize: 18, fontWeight: 700, margin: 0 }}>Carta de Presentación y Términos de Postulación</h2>
              <p style={{ color: 'rgba(255,255,255,0.65)', fontSize: 13, marginTop: 4, marginBottom: 0 }}>Lea detenidamente las condiciones antes de continuar</p>
            </div>
            <div style={{ padding: '28px' }}>
              <div style={{ background: '#f8f9fa', borderRadius: 10, border: '1px solid #e9ecef', padding: '24px 28px', maxHeight: 360, overflowY: 'auto', lineHeight: 1.8, color: '#495057', fontSize: 13.5 }}>
                <p style={{ fontFamily: "'Roboto Slab', serif", fontWeight: 700, color: '#1a2744', fontSize: 16, marginTop: 0 }}>Estimado/a Postulante:</p>
                <p>La <strong>Corporación de Asistencia Judicial de la Región del Biobío</strong>, en cumplimiento de su misión institucional de facilitar el acceso a la justicia a toda la ciudadanía, pone a disposición de los estudiantes de Derecho el presente Programa de Práctica Profesional, el cual constituye una oportunidad de formación de excelencia en el ejercicio del Derecho.</p>
                <p>Este programa tiene como propósito contribuir a la formación práctica de futuros abogados, permitiéndoles aplicar los conocimientos adquiridos durante su formación académica en situaciones reales y de alto impacto social. Los practicantes colaborarán directamente con abogados tutores de esta Corporación en la atención de usuarios, elaboración de escritos jurídicos y seguimiento de causas.</p>
                <p style={{ fontFamily: "'Roboto Slab', serif", fontWeight: 700, color: '#1a2744', marginTop: 24 }}>Términos y Condiciones de Participación</p>
                <p><strong>1. Requisitos de Postulación:</strong> Ser estudiante regular de la carrera de Derecho en alguna universidad reconocida por el Estado de Chile, haber cursado al menos el cuarto año de la carrera, contar con certificado de alumno regular vigente y no tener reprobaciones pendientes en ramos obligatorios del plan de estudios.</p>
                <p><strong>2. Duración y Modalidad:</strong> La práctica profesional tiene una duración mínima de seis meses y máxima de doce meses, en modalidad presencial en las dependencias de la Corporación. El horario será de lunes a viernes, con jornada completa o parcial según se acuerde con el abogado tutor asignado.</p>
                <p><strong>3. Compromisos del Practicante:</strong> El practicante se compromete a mantener absoluta confidencialidad respecto de los casos atendidos, asistir con puntualidad y regularidad, respetar el reglamento interno de la institución, actuar conforme a los principios éticos de la profesión jurídica y participar activamente en las jornadas de capacitación que la Corporación organice.</p>
                <p><strong>4. Tratamiento de Datos Personales:</strong> Los datos personales proporcionados en este formulario serán tratados con estricta confidencialidad, conforme a la Ley N° 19.628 sobre Protección de la Vida Privada. Serán utilizados exclusivamente para los fines del proceso de selección y gestión de la práctica. No serán cedidos a terceros sin su consentimiento expreso.</p>
                <p><strong>5. Proceso de Selección:</strong> La Corporación se reserva el derecho de aceptar o rechazar postulaciones según sus necesidades institucionales. La notificación del resultado se realizará vía correo electrónico en un plazo máximo de 15 días hábiles.</p>
                <p><strong>6. Beneficios:</strong> Abogado tutor designado, acceso a recursos institucionales, certificado de práctica al término del período y posibilidad de vinculación continua según desempeño.</p>
                <p style={{ marginBottom: 0 }}><em>Concepción, {new Date().toLocaleDateString('es-CL', { year: 'numeric', month: 'long', day: 'numeric' })}</em></p>
              </div>
              <div onClick={() => setAccepted(a => !a)} style={{ display: 'flex', alignItems: 'flex-start', gap: 12, marginTop: 20, cursor: 'pointer', userSelect: 'none' }}>
                <div style={{ width: 20, height: 20, borderRadius: 5, border: `2px solid ${accepted ? '#1a2744' : '#ced4da'}`, background: accepted ? '#1a2744' : '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0, marginTop: 1, transition: 'all 0.15s' }}>
                  {accepted && <Icon name="check" size={12} />}
                </div>
                <span style={{ fontSize: 13.5, color: '#495057', lineHeight: 1.5 }}>He leído y acepto los términos y condiciones del programa de práctica profesional de la Corporación de Asistencia Judicial del Biobío.</span>
              </div>
              <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: 24 }}>
                <button onClick={() => accepted && setStep(2)} style={{ background: accepted ? '#1a2744' : '#ced4da', color: '#fff', border: 'none', padding: '11px 28px', borderRadius: 8, fontWeight: 700, fontSize: 14, cursor: accepted ? 'pointer' : 'not-allowed', display: 'flex', alignItems: 'center', gap: 8 }}>
                  Continuar <Icon name="arrow_right" size={16} />
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Step 2 */}
        {step === 2 && (
          <div style={{ background: '#fff', borderRadius: 12, border: '1px solid #e9ecef', overflow: 'hidden', boxShadow: '0 2px 12px rgba(0,0,0,0.06)' }}>
            <div style={{ background: '#1a2744', padding: '20px 28px' }}>
              <h2 style={{ color: '#fff', fontSize: 18, fontWeight: 700, margin: 0 }}>Datos Personales</h2>
              <p style={{ color: 'rgba(255,255,255,0.65)', fontSize: 13, marginTop: 4, marginBottom: 0 }}>Complete todos los campos requeridos con su información personal</p>
            </div>
            <div style={{ padding: '28px' }}>
              <RequiredNote />
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                <FormField label="Nombre" required><input style={inputStyle} value={form.nombre} onChange={e => set('nombre', e.target.value)} placeholder="Ej: María José" /></FormField>
                <FormField label="Apellido" required><input style={inputStyle} value={form.apellido} onChange={e => set('apellido', e.target.value)} placeholder="Ej: González Martínez" /></FormField>
                <FormField label="RUT" required><input style={inputStyle} value={form.rut} onChange={e => set('rut', e.target.value)} placeholder="Ej: 20.123.456-7" /></FormField>
                <FormField label="Fecha de nacimiento" required><input type="date" style={inputStyle} value={form.fechaNac} onChange={e => set('fechaNac', e.target.value)} /></FormField>
                <FormField label="Correo electrónico" required><input type="email" style={inputStyle} value={form.correo} onChange={e => set('correo', e.target.value)} placeholder="correo@universidad.cl" /></FormField>
                <FormField label="Número de teléfono" required><input style={inputStyle} value={form.telefono} onChange={e => set('telefono', e.target.value)} placeholder="+56 9 XXXX XXXX" /></FormField>
                <FormField label="Género" required>
                  <select style={selectStyle} value={form.genero} onChange={e => set('genero', e.target.value)}>
                    <option value="">Seleccione...</option>
                    <option>Femenino</option><option>Masculino</option><option>No binario</option><option>Prefiero no indicar</option>
                  </select>
                </FormField>
                <FormField label="Nacionalidad" required><input style={inputStyle} value={form.nacionalidad} onChange={e => set('nacionalidad', e.target.value)} placeholder="Ej: Chilena" /></FormField>
                <FormField label="Universidad" required>
                  <select style={selectStyle} value={form.universidad} onChange={e => set('universidad', e.target.value)}>
                    <option value="">Seleccione universidad...</option>
                    <option>Universidad de Concepción</option><option>Universidad del Bío-Bío</option>
                    <option>Universidad Católica de la Santísima Concepción (UCSC)</option>
                    <option>Universidad San Sebastián</option><option>Universidad de Chile</option><option>Otra universidad</option>
                  </select>
                </FormField>
              </div>

              {/* Discapacidad */}
              <div style={{ marginTop: 20, paddingTop: 20, borderTop: '1px solid #f1f3f5' }}>
                <div style={{ fontSize: 13, fontWeight: 700, color: '#1a2744', letterSpacing: '0.04em', marginBottom: 14, display: 'flex', alignItems: 'center', gap: 8 }}>
                  <Icon name="heart" size={14} /> INFORMACIÓN DE ACCESIBILIDAD
                </div>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 2fr', gap: 16 }}>
                  <FormField label="¿Presenta alguna discapacidad?" required>
                    <select style={selectStyle} value={form.tieneDiscapacidad} onChange={e => set('tieneDiscapacidad', e.target.value)}>
                      <option value="">Seleccione...</option>
                      <option>No</option><option>Sí, física</option><option>Sí, sensorial (visual)</option>
                      <option>Sí, sensorial (auditiva)</option><option>Sí, intelectual</option><option>Sí, psíquica</option><option>Sí, otra</option>
                    </select>
                  </FormField>
                  {form.tieneDiscapacidad && form.tieneDiscapacidad !== 'No' && (
                    <FormField label="Descripción de la discapacidad y necesidades de apoyo">
                      <input style={inputStyle} value={form.discapacidad} onChange={e => set('discapacidad', e.target.value)} placeholder="Indique tipo y grado de discapacidad, y si requiere adaptaciones especiales" />
                    </FormField>
                  )}
                </div>
              </div>

              {/* Domicilio */}
              <div style={{ marginTop: 20, paddingTop: 20, borderTop: '1px solid #f1f3f5' }}>
                <div style={{ fontSize: 13, fontWeight: 700, color: '#1a2744', letterSpacing: '0.04em', marginBottom: 14, display: 'flex', alignItems: 'center', gap: 8 }}>
                  <Icon name="building" size={14} /> DOMICILIO
                </div>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 2fr 1fr', gap: 16 }}>
                  <FormField label="Región" required>
                    <select style={selectStyle} value={form.region} onChange={e => { set('region', e.target.value); set('comuna', '') }}>
                      <option value="">Seleccione...</option>
                      {regiones.map(r => <option key={r}>{r}</option>)}
                    </select>
                  </FormField>
                  <FormField label="Dirección" required><input style={inputStyle} value={form.direccion} onChange={e => set('direccion', e.target.value)} placeholder="Calle, número, depto." /></FormField>
                  <FormField label="Comuna" required>
                    <select style={selectStyle} value={form.comuna} onChange={e => set('comuna', e.target.value)} disabled={!form.region}>
                      <option value="">Seleccione...</option>
                      {(comunas[form.region] || []).map(c => <option key={c}>{c}</option>)}
                    </select>
                  </FormField>
                </div>
              </div>

              {/* Contacto emergencia */}
              <div style={{ marginTop: 20, paddingTop: 20, borderTop: '1px solid #f1f3f5' }}>
                <div style={{ fontSize: 13, fontWeight: 700, color: '#1a2744', letterSpacing: '0.04em', marginBottom: 14, display: 'flex', alignItems: 'center', gap: 8 }}>
                  <Icon name="phone" size={14} /> CONTACTO DE EMERGENCIA
                </div>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                  <FormField label="Nombre completo" required><input style={inputStyle} value={form.contactoNombre} onChange={e => set('contactoNombre', e.target.value)} placeholder="Nombre y apellido" /></FormField>
                  <FormField label="Vínculo con la persona" required>
                    <select style={selectStyle} value={form.vinculo} onChange={e => set('vinculo', e.target.value)}>
                      <option value="">Seleccione...</option>
                      <option>Padre / Madre</option><option>Hermano/a</option><option>Cónyuge / Pareja</option><option>Amigo/a</option><option>Otro</option>
                    </select>
                  </FormField>
                  <FormField label="Número de teléfono" required><input style={inputStyle} value={form.contactoTelefono} onChange={e => set('contactoTelefono', e.target.value)} placeholder="+56 9 XXXX XXXX" /></FormField>
                  <FormField label="Correo de emergencia" required><input type="email" style={inputStyle} value={form.contactoCorreo} onChange={e => set('contactoCorreo', e.target.value)} placeholder="correo@ejemplo.cl" /></FormField>
                </div>
              </div>

              {/* Salud */}
              <div style={{ marginTop: 20, paddingTop: 20, borderTop: '1px solid #f1f3f5' }}>
                <FormField label="Antecedentes de salud relevantes">
                  <textarea value={form.salud} onChange={e => set('salud', e.target.value)} placeholder="Indique alergias, condiciones médicas u otras consideraciones relevantes. Puede escribir 'Ninguno' si no aplica." style={{ ...inputStyle, minHeight: 80, resize: 'vertical' }} />
                </FormField>
              </div>

              <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 24 }}>
                <button onClick={() => setStep(0)} style={{ background: '#fff', color: '#495057', border: '1.5px solid #dee2e6', padding: '10px 22px', borderRadius: 8, fontWeight: 600, fontSize: 14, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 8 }}>
                  <Icon name="chevron_left" size={16} /> Volver
                </button>
                <button onClick={() => setStep(3)} style={{ background: '#1a2744', color: '#fff', border: 'none', padding: '11px 28px', borderRadius: 8, fontWeight: 700, fontSize: 14, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 8 }}>
                  Continuar <Icon name="arrow_right" size={16} />
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Step 3 */}
        {step === 3 && (
          <div style={{ background: '#fff', borderRadius: 12, border: '1px solid #e9ecef', overflow: 'hidden', boxShadow: '0 2px 12px rgba(0,0,0,0.06)' }}>
            <div style={{ background: '#1a2744', padding: '20px 28px' }}>
              <h2 style={{ color: '#fff', fontSize: 18, fontWeight: 700, margin: 0 }}>Antecedentes Educativos</h2>
              <p style={{ color: 'rgba(255,255,255,0.65)', fontSize: 13, marginTop: 4, marginBottom: 0 }}>Información sobre su trayectoria académica</p>
            </div>
            <div style={{ padding: '28px' }}>
              <RequiredNote />
              <div>
                <div style={{ fontSize: 13, fontWeight: 700, color: '#1a2744', letterSpacing: '0.04em', marginBottom: 14 }}>FORMACIÓN UNIVERSITARIA</div>
                <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr', gap: 16 }}>
                  <FormField label="Universidad en que estudia o estudió" required>
                    <select style={selectStyle} value={form.universidad2} onChange={e => set('universidad2', e.target.value)}>
                      <option value="">Seleccione...</option>
                      <option>Universidad de Concepción</option><option>Universidad del Bío-Bío</option>
                      <option>Universidad Católica de la Santísima Concepción (UCSC)</option>
                      <option>Universidad San Sebastián</option><option>Universidad de Chile</option>
                      <option>Pontificia Universidad Católica de Chile</option><option>Otra universidad</option>
                    </select>
                  </FormField>
                  <FormField label="Año de carrera actual">
                    <select style={selectStyle} value={form.añoCarrera} onChange={e => set('añoCarrera', e.target.value)}>
                      <option value="">Seleccione...</option>
                      <option>5° año</option><option>Egresado</option>
                    </select>
                  </FormField>
                </div>
                <div style={{ marginTop: 16 }}>
                  <FormField label="Especialidad o mención en Derecho">
                    <select style={selectStyle} value={form.especialidad} onChange={e => set('especialidad', e.target.value)}>
                      <option value="">Seleccione especialidad...</option>
                      <option>Derecho Civil</option><option>Derecho Penal</option><option>Derecho Laboral</option>
                      <option>Derecho de Familia</option><option>Derecho Comercial</option>
                      <option>Derecho Administrativo</option><option>Derecho Constitucional</option><option>Sin mención específica</option>
                    </select>
                  </FormField>
                </div>
              </div>

              {/* Cédula de identidad */}
              <div style={{ marginTop: 28, paddingTop: 24, borderTop: '1px solid #e9ecef' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 13, fontWeight: 700, color: '#1a2744', letterSpacing: '0.04em', marginBottom: 6 }}>
                  <Icon name="shield" size={14} /> CÉDULA DE IDENTIDAD <span style={{ color: '#c0392b' }}>*</span>
                </div>
                <div style={{ color: '#6c757d', fontSize: 12.5, marginBottom: 16 }}>
                  Escanee su cédula por ambos lados con la cámara o suba una fotografía de cada cara. Las imágenes se usarán para validar su identidad.
                </div>

                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(260px, 1fr))', gap: 14 }}>
                  {ladosCarnet.map(lado => (
                    <EscaneoCarnet
                      key={lado.id}
                      lado={lado}
                      archivo={carnet[lado.id]}
                      onSet={file => setCarnet(c => ({ ...c, [lado.id]: file }))}
                      onClear={() => setCarnet(c => ({ ...c, [lado.id]: null }))}
                    />
                  ))}
                </div>

                {!carnetCompleto && (
                  <div style={{ display: 'flex', alignItems: 'flex-start', gap: 8, background: '#fdf3f2', border: '1px solid #f2d6d2', borderRadius: 8, padding: '10px 12px', marginTop: 14, color: '#a5342a', fontSize: 12.5 }}>
                    <span style={{ display: 'flex', marginTop: 1 }}><Icon name="alert" size={14} /></span>
                    <span>Falta capturar el lado {!carnet.frontal ? 'frontal' : 'reverso'} de la cédula.</span>
                  </div>
                )}

                <PanelValidacionCedula frontal={carnet.frontal} reverso={carnet.reverso} />
              </div>

              <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 28 }}>
                <button onClick={() => setStep(2)} style={{ background: '#fff', color: '#495057', border: '1.5px solid #dee2e6', padding: '10px 22px', borderRadius: 8, fontWeight: 600, fontSize: 14, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 8 }}>
                  <Icon name="chevron_left" size={16} /> Volver
                </button>
                <button onClick={() => setStep(4)} style={{ background: '#1a2744', color: '#fff', border: 'none', padding: '11px 28px', borderRadius: 8, fontWeight: 700, fontSize: 14, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 8 }}>
                  Continuar <Icon name="arrow_right" size={16} />
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Step 4 — Consultorio y fecha de inicio */}
        {step === 4 && (
          <div style={{ background: '#fff', borderRadius: 12, border: '1px solid #e9ecef', overflow: 'hidden', boxShadow: '0 2px 12px rgba(0,0,0,0.06)' }}>
            <div style={{ background: '#1a2744', padding: '20px 28px' }}>
              <h2 style={{ color: '#fff', fontSize: 18, fontWeight: 700, margin: 0 }}>Consultorio y Fecha de Inicio</h2>
              <p style={{ color: 'rgba(255,255,255,0.65)', fontSize: 13, marginTop: 4, marginBottom: 0 }}>Indique en qué consultorio desea realizar su práctica y cuándo podría comenzar</p>
            </div>
            <div style={{ padding: '28px' }}>
              <RequiredNote />

              {/* Consultorio */}
              <div style={{ fontSize: 13, fontWeight: 700, color: '#1a2744', letterSpacing: '0.04em', marginBottom: 6, display: 'flex', alignItems: 'center', gap: 8 }}>
                <Icon name="building" size={14} /> CONSULTORIOS A LOS QUE POSTULA <span style={{ color: '#c0392b' }}>*</span>
              </div>
              <div style={{ color: '#6c757d', fontSize: 12.5, marginBottom: 14 }}>
                Comunas de la Región del Biobío con consultorios. Puede seleccionar uno o más; el orden en que los seleccione define su <strong>orden de preferencia</strong>.
                {form.comuna && comunaCoords[form.comuna] && <> Se ordenan según la cercanía a su domicilio en {form.comuna}.</>}
              </div>

              {consultoriosSel.length > 0 && (
                <div style={{ background: '#f8f9fa', border: '1px solid #e9ecef', borderRadius: 8, padding: '12px 14px', marginBottom: 14 }}>
                  <div style={{ fontSize: 11.5, fontWeight: 700, color: '#495057', letterSpacing: '0.03em', marginBottom: 8 }}>ORDEN DE PREFERENCIA</div>
                  <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                    {consultoriosSel.map((comuna, i) => (
                      <div key={comuna} style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                        <span style={{ width: 20, height: 20, borderRadius: '50%', background: '#1a2744', color: '#fff', fontSize: 11, fontWeight: 700, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>{i + 1}</span>
                        <span style={{ color: '#1a2744', fontSize: 13, fontWeight: 600 }}>{comuna}</span>
                        <button onClick={() => toggleConsultorio(comuna)} title="Quitar" style={{ marginLeft: 'auto', border: 'none', background: 'transparent', color: '#adb5bd', cursor: 'pointer', display: 'flex', alignItems: 'center' }}><Icon name="x" size={13} /></button>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              <div style={{ position: 'relative', marginBottom: 14 }}>
                <span style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: '#adb5bd' }}><Icon name="search" size={15} /></span>
                <input value={consultorioBusqueda} onChange={e => setConsultorioBusqueda(e.target.value)} placeholder="Buscar consultorio por comuna o nombre..." style={{ ...inputStyle, paddingLeft: 36, background: '#f8f9fa' }} />
              </div>
              {(() => {
                const ordenados = [...consultoriosPostulacion].sort((a, b) => distanciaComuna(a.comuna, form.comuna) - distanciaComuna(b.comuna, form.comuna))
                const q = consultorioBusqueda.trim().toLowerCase()
                const filtrados = q ? ordenados.filter(c => c.comuna.toLowerCase().includes(q) || c.consultorio.toLowerCase().includes(q)) : ordenados
                const cercano = form.comuna && comunaCoords[form.comuna] ? ordenados[0].comuna : null
                if (filtrados.length === 0) return <div style={{ textAlign: 'center', padding: '24px 0', color: '#adb5bd', fontSize: 13 }}>No se encontraron consultorios con ese criterio.</div>
                return (
                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(210px, 1fr))', gap: 12 }}>
                    {filtrados.map(c => {
                      const pref = consultoriosSel.indexOf(c.comuna)
                      const selected = pref >= 0
                      return (
                        <div key={c.comuna} onClick={() => toggleConsultorio(c.comuna)}
                          style={{ border: `1.5px solid ${selected ? '#1a2744' : '#e9ecef'}`, borderRadius: 10, padding: '14px 16px', cursor: 'pointer', transition: 'all 0.15s', background: '#fff' }}>
                          <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 8 }}>
                            <div>
                              <div style={{ display: 'flex', alignItems: 'center', gap: 6, fontWeight: 700, color: '#1a2744', fontSize: 14 }}>
                                <Icon name="map_pin" size={13} /> {c.comuna}
                              </div>
                              <div style={{ color: '#6c757d', fontSize: 12, marginTop: 4 }}>{c.consultorio}</div>
                              <div style={{ color: '#adb5bd', fontSize: 11.5, marginTop: 2 }}>{c.direccion}</div>
                              {cercano === c.comuna && <div style={{ display: 'inline-block', marginTop: 8, background: '#f1f3f5', color: '#495057', fontSize: 10.5, fontWeight: 700, letterSpacing: '0.03em', padding: '2px 8px', borderRadius: 20 }}>MÁS CERCANO A SU DOMICILIO</div>}
                            </div>
                            <div title={selected ? `Preferencia ${pref + 1}` : undefined} style={{ width: 22, height: 22, borderRadius: '50%', border: `2px solid ${selected ? '#1a2744' : '#ced4da'}`, background: selected ? '#1a2744' : '#fff', color: selected ? '#fff' : '#adb5bd', fontSize: 12, fontWeight: 700, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                              {selected ? pref + 1 : ''}
                            </div>
                          </div>
                        </div>
                      )
                    })}
                  </div>
                )
              })()}

              {/* Fecha de inicio */}
              <div style={{ marginTop: 24, paddingTop: 20, borderTop: '1px solid #f1f3f5' }}>
                <div style={{ fontSize: 13, fontWeight: 700, color: '#1a2744', letterSpacing: '0.04em', marginBottom: 6, display: 'flex', alignItems: 'center', gap: 8 }}>
                  <Icon name="calendar" size={14} /> FECHA EN QUE PUEDE COMENZAR <span style={{ color: '#c0392b' }}>*</span>
                </div>
                <div style={{ color: '#6c757d', fontSize: 12.5, marginBottom: 14 }}>No es posible seleccionar fines de semana ni feriados legales.</div>
                <DatePicker value={form.fechaInicio} onChange={v => set('fechaInicio', v)} />
                {form.fechaInicio && (
                  <div style={{ marginTop: 12, display: 'inline-flex', alignItems: 'center', gap: 8, background: '#e8f4fd', color: '#1565c0', padding: '8px 14px', borderRadius: 8, fontSize: 13, fontWeight: 600 }}>
                    <Icon name="check" size={14} /> Fecha seleccionada: {new Date(form.fechaInicio + 'T00:00:00').toLocaleDateString('es-CL', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
                  </div>
                )}
              </div>

              <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 28 }}>
                <button onClick={() => setStep(3)} style={{ background: '#fff', color: '#495057', border: '1.5px solid #dee2e6', padding: '10px 22px', borderRadius: 8, fontWeight: 600, fontSize: 14, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 8 }}>
                  <Icon name="chevron_left" size={16} /> Volver
                </button>
                <button onClick={() => (consultoriosSel.length > 0 && form.fechaInicio) && setStep(5)}
                  style={{ background: (consultoriosSel.length > 0 && form.fechaInicio) ? '#1a2744' : '#ced4da', color: '#fff', border: 'none', padding: '11px 28px', borderRadius: 8, fontWeight: 700, fontSize: 14, cursor: (consultoriosSel.length > 0 && form.fechaInicio) ? 'pointer' : 'not-allowed', display: 'flex', alignItems: 'center', gap: 8 }}>
                  Revisar postulación <Icon name="arrow_right" size={16} />
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Step 5 — Resumen / revisión */}
        {step === 5 && (() => {
          const val = (x: string) => (x && String(x).trim()) ? x : '—'
          const fmt = (iso: string) => iso ? new Date(iso + 'T00:00:00').toLocaleDateString('es-CL', { year: 'numeric', month: 'long', day: 'numeric' }) : '—'
          const secciones: { titulo: string; icon: string; editStep: number; campos: [string, string][] }[] = [
            { titulo: 'Datos personales', icon: 'user_plus', editStep: 2, campos: [
              ['Nombre completo', val(`${form.nombre} ${form.apellido}`.trim())],
              ['RUT', val(form.rut)], ['Fecha de nacimiento', fmt(form.fechaNac)],
              ['Correo electrónico', val(form.correo)], ['Teléfono', val(form.telefono)],
              ['Género', val(form.genero)], ['Nacionalidad', val(form.nacionalidad)],
              ['Universidad', val(form.universidad)],
            ] },
            { titulo: 'Información de accesibilidad', icon: 'heart', editStep: 2, campos: [
              ['¿Presenta discapacidad?', val(form.tieneDiscapacidad)],
              ...(form.tieneDiscapacidad && form.tieneDiscapacidad !== 'No' ? [['Descripción', val(form.discapacidad)]] as [string, string][] : []),
            ] },
            { titulo: 'Domicilio', icon: 'building', editStep: 2, campos: [
              ['Región', val(form.region)], ['Dirección', val(form.direccion)], ['Comuna', val(form.comuna)],
            ] },
            { titulo: 'Contacto de emergencia', icon: 'phone', editStep: 2, campos: [
              ['Nombre completo', val(form.contactoNombre)], ['Vínculo', val(form.vinculo)],
              ['Teléfono', val(form.contactoTelefono)], ['Correo', val(form.contactoCorreo)],
            ] },
            { titulo: 'Salud', icon: 'heart', editStep: 2, campos: [['Antecedentes de salud', val(form.salud)]] },
            { titulo: 'Antecedentes educativos', icon: 'clipboard', editStep: 3, campos: [
              ['Universidad', val(form.universidad2)], ['Año de carrera', val(form.añoCarrera)], ['Especialidad', val(form.especialidad)],
            ] },
            { titulo: 'Cédula de identidad', icon: 'shield', editStep: 3, campos: [
              ['Lado frontal', carnet.frontal ? carnet.frontal.name : '—'],
              ['Lado reverso', carnet.reverso ? carnet.reverso.name : '—'],
              ['Validación', carnetCompleto ? 'Pendiente de validación automática' : 'Incompleta'],
            ] },
          ]
          return (
            <div style={{ background: '#fff', borderRadius: 12, border: '1px solid #e9ecef', overflow: 'hidden', boxShadow: '0 2px 12px rgba(0,0,0,0.06)' }}>
              <div style={{ background: '#1a2744', padding: '20px 28px' }}>
                <h2 style={{ color: '#fff', fontSize: 18, fontWeight: 700, margin: 0 }}>Resumen de la Postulación</h2>
                <p style={{ color: 'rgba(255,255,255,0.65)', fontSize: 13, marginTop: 4, marginBottom: 0 }}>Revise que todos los datos estén correctos antes de enviar</p>
              </div>
              <div style={{ padding: '28px' }}>
                {secciones.map(s => (
                  <div key={s.titulo} style={{ marginBottom: 22 }}>
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 13, fontWeight: 700, color: '#1a2744', letterSpacing: '0.04em' }}>
                        <Icon name={s.icon} size={14} /> {s.titulo.toUpperCase()}
                      </div>
                      <button onClick={() => setStep(s.editStep)} style={{ border: 'none', background: '#f1f3f5', color: '#495057', fontSize: 12, fontWeight: 600, padding: '4px 12px', borderRadius: 6, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 5 }}><Icon name="edit" size={12} /> Editar</button>
                    </div>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 10, background: '#f8f9fa', borderRadius: 8, padding: '14px 16px' }}>
                      {s.campos.map(([label, value]) => (
                        <div key={label}>
                          <div style={{ color: '#6c757d', fontSize: 11, fontWeight: 600, marginBottom: 2 }}>{label}</div>
                          <div style={{ color: '#1a2744', fontSize: 13, fontWeight: 500 }}>{value}</div>
                        </div>
                      ))}
                    </div>
                  </div>
                ))}

                {/* Consultorio y fecha */}
                <div style={{ marginBottom: 8 }}>
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 13, fontWeight: 700, color: '#1a2744', letterSpacing: '0.04em' }}>
                      <Icon name="building" size={14} /> CONSULTORIOS Y FECHA DE INICIO
                    </div>
                    <button onClick={() => setStep(4)} style={{ border: 'none', background: '#f1f3f5', color: '#495057', fontSize: 12, fontWeight: 600, padding: '4px 12px', borderRadius: 6, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 5 }}><Icon name="edit" size={12} /> Editar</button>
                  </div>
                  <div style={{ background: '#f8f9fa', borderRadius: 8, padding: '14px 16px' }}>
                    <div style={{ color: '#6c757d', fontSize: 11, fontWeight: 600, marginBottom: 6 }}>Consultorios por orden de preferencia</div>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 6, marginBottom: 12 }}>
                      {consultoriosSel.map((comuna, i) => (
                        <div key={comuna} style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                          <span style={{ width: 20, height: 20, borderRadius: '50%', background: '#1a2744', color: '#fff', fontSize: 11, fontWeight: 700, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>{i + 1}</span>
                          <span style={{ color: '#1a2744', fontSize: 13, fontWeight: 600 }}>{comuna}</span>
                        </div>
                      ))}
                    </div>
                    <div style={{ color: '#6c757d', fontSize: 11, fontWeight: 600, marginBottom: 2 }}>Fecha en que puede comenzar</div>
                    <div style={{ color: '#1a2744', fontSize: 13, fontWeight: 500 }}>{fmt(form.fechaInicio)}</div>
                  </div>
                </div>

                <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 24 }}>
                  <button onClick={() => setStep(4)} style={{ background: '#fff', color: '#495057', border: '1.5px solid #dee2e6', padding: '10px 22px', borderRadius: 8, fontWeight: 600, fontSize: 14, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 8 }}>
                    <Icon name="chevron_left" size={16} /> Volver
                  </button>
                  <button onClick={() => setStep(6)} style={{ background: '#c0392b', color: '#fff', border: 'none', padding: '11px 28px', borderRadius: 8, fontWeight: 700, fontSize: 14, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 8 }}>
                    Confirmar y enviar <Icon name="check" size={16} />
                  </button>
                </div>
              </div>
            </div>
          )
        })()}

        {step === 6 && (
          <div style={{ background: '#fff', borderRadius: 12, border: '1px solid #e9ecef', padding: '60px 40px', textAlign: 'center', boxShadow: '0 2px 12px rgba(0,0,0,0.06)' }}>
            <div style={{ width: 72, height: 72, borderRadius: '50%', background: '#e8f5e9', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 20px', color: '#27ae60' }}><Icon name="check" size={36} /></div>
            <h2 style={{ fontFamily: "'Roboto Slab', serif", fontSize: 24, color: '#1a2744', margin: '0 0 12px' }}>¡Postulación enviada correctamente!</h2>
            <p style={{ color: '#6c757d', fontSize: 14.5, maxWidth: 440, margin: '0 auto 28px', lineHeight: 1.7 }}>Su postulación ha sido recibida con éxito. Le notificaremos el resultado del proceso a su correo en un plazo de 15 días hábiles.</p>
            <button onClick={() => { setStep(0); setAccepted(false); setConsultoriosSel([]); setConsultorioBusqueda(''); set('fechaInicio', '') }} style={{ background: '#1a2744', color: '#fff', border: 'none', padding: '11px 28px', borderRadius: 8, fontWeight: 700, fontSize: 14, cursor: 'pointer' }}>
              Nueva postulación
            </button>
          </div>
        )}
      </div>
    </div>
  )
}

function EstadisticaPractica({ practica, volver }: { practica: typeof practicas[0] | null; volver: () => void }) {
  if (!practica) {
    return (
      <div style={{ padding: '28px 28px 40px' }}>
        <button onClick={volver} style={{ background: '#fff', color: '#495057', border: '1.5px solid #dee2e6', padding: '9px 18px', borderRadius: 8, fontWeight: 600, fontSize: 13.5, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 8 }}>
          <Icon name="chevron_left" size={16} /> Volver a Prácticas Activas
        </button>
        <div style={{ textAlign: 'center', padding: '60px 0', color: '#adb5bd', fontSize: 14 }}>No hay una práctica seleccionada.</div>
      </div>
    )
  }

  const parse = (s: string) => { const [d, m, y] = s.split('/').map(Number); return new Date(y, m - 1, d) }
  const ini = parse(practica.inicio); const fin = parse(practica.termino)
  const hoy = new Date(2026, 7, 4)
  const totalDias = Math.max(1, Math.round((fin.getTime() - ini.getTime()) / 86400000))
  const transcurridos = Math.min(totalDias, Math.max(0, Math.round((hoy.getTime() - ini.getTime()) / 86400000)))
  const pct = Math.round((transcurridos / totalDias) * 100)
  const ini2 = practica.practicante.split(' ').map(n => n[0]).join('').slice(0, 2)

  const stats = [
    { label: 'DURACIÓN TOTAL', value: totalDias, sub: 'días de práctica', icon: 'calendar', color: '#2980b9', bg: '#e8f4fd' },
    { label: 'DÍAS TRANSCURRIDOS', value: transcurridos, sub: `de ${totalDias} días`, icon: 'clock', color: '#27ae60', bg: '#e8f5e9' },
    { label: 'PROGRESO', value: `${pct}%`, sub: 'de avance', icon: 'chart', color: '#e67e22', bg: '#fff3e0' },
  ]

  const documentos = archivosDePractica(practica)
  const docsUniversitarios = documentos.filter(d => d.categoria === 'universitario')
  const docsCedula = documentos.filter(d => d.categoria === 'cedula')

  const filaDocumento = (a: ArchivoPostulacion) => (
    <div key={a.nombre} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '10px 12px', background: '#f8f9fa', border: '1px solid #e9ecef', borderRadius: 8 }}>
      <span style={{ color: a.categoria === 'cedula' ? '#8e6dbf' : '#1a2744', display: 'flex', flexShrink: 0 }}><Icon name="doc" size={17} /></span>
      <div style={{ flex: 1, minWidth: 0 }}>
        <div style={{ color: '#1a2744', fontSize: 13, fontWeight: 600, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{a.nombre}</div>
        <div style={{ color: '#6c757d', fontSize: 11.5, marginTop: 1 }}>{a.tipo} · {a.peso} · subido el {a.fecha}</div>
      </div>
      <button style={{ border: '1.5px solid #dee2e6', background: '#fff', color: '#495057', padding: '6px 12px', borderRadius: 6, fontWeight: 600, fontSize: 12, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6, flexShrink: 0 }}>
        <Icon name="eye" size={13} /> Ver
      </button>
      <button style={{ border: '1.5px solid #dee2e6', background: '#fff', color: '#495057', padding: '6px 12px', borderRadius: 6, fontWeight: 600, fontSize: 12, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6, flexShrink: 0 }}>
        <Icon name="download" size={13} /> Descargar
      </button>
    </div>
  )

  return (
    <div style={{ padding: '28px 28px 40px' }}>
      <button onClick={volver} style={{ background: '#fff', color: '#495057', border: '1.5px solid #dee2e6', padding: '9px 18px', borderRadius: 8, fontWeight: 600, fontSize: 13.5, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 8, marginBottom: 16 }}>
        <Icon name="chevron_left" size={16} /> Volver a Prácticas Activas
      </button>
      <h1 style={{ fontSize: 24, fontWeight: 700, color: '#1a2744', margin: 0 }}>Estadísticas de la Práctica</h1>
      <Breadcrumb items={['Inicio', 'Prácticas Activas', 'Estadísticas']} />

      {/* Ficha principal */}
      <Card style={{ marginTop: 24 }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 12 }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 14 }}>
            <Avatar ini={ini2} color={practica.uniColor} size={48} />
            <div>
              <div style={{ fontWeight: 700, color: '#1a2744', fontSize: 17 }}>{practica.practicante}</div>
              <div style={{ color: '#6c757d', fontSize: 13, marginTop: 2 }}>{practica.universidad}</div>
            </div>
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
            <span style={{ background: '#f1f3f5', color: '#495057', fontSize: 12, fontWeight: 700, padding: '5px 12px', borderRadius: 20 }}>ID práctica: #{String(practica.id).padStart(4, '0')}</span>
            <StatusBadge estado={practica.estado} />
          </div>
        </div>
      </Card>

      {/* Stat tiles */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 16, marginTop: 16 }}>
        {stats.map((s, i) => (
          <Card key={i} style={{ display: 'flex', alignItems: 'center', gap: 16, padding: '20px 24px' }}>
            <div style={{ width: 48, height: 48, borderRadius: 10, background: s.bg, display: 'flex', alignItems: 'center', justifyContent: 'center', color: s.color, flexShrink: 0 }}><Icon name={s.icon} size={22} /></div>
            <div>
              <div style={{ color: '#6c757d', fontSize: 10.5, fontWeight: 700, letterSpacing: '0.07em' }}>{s.label}</div>
              <div style={{ color: '#1a2744', fontSize: 28, fontWeight: 700, lineHeight: 1.1 }}>{s.value}</div>
              <div style={{ color: '#adb5bd', fontSize: 12 }}>{s.sub}</div>
            </div>
          </Card>
        ))}
      </div>

      {/* Barra de progreso */}
      <Card style={{ marginTop: 16 }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 }}>
          <span style={{ fontWeight: 600, color: '#1a2744', fontSize: 14 }}>Avance de la práctica</span>
          <span style={{ color: '#6c757d', fontSize: 13 }}>{practica.inicio} → {practica.termino}</span>
        </div>
        <div style={{ background: '#f1f3f5', borderRadius: 5, height: 8, overflow: 'hidden' }}>
          <div style={{ height: '100%', width: `${pct}%`, background: '#27ae60', borderRadius: 5, transition: 'width 0.3s' }} />
        </div>
        <div style={{ color: '#adb5bd', fontSize: 11.5, marginTop: 6 }}>{pct}% completado ({transcurridos} de {totalDias} días)</div>
      </Card>

      {/* Detalle */}
      <Card style={{ marginTop: 16 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 16 }}>
          <Icon name="clipboard" size={16} /><span style={{ fontWeight: 600, color: '#1a2744', fontSize: 15 }}>Detalle de la práctica</span>
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 16 }}>
          {[
            { label: 'ID de la práctica', value: `#${String(practica.id).padStart(4, '0')}` },
            { label: 'Practicante', value: practica.practicante },
            { label: 'Fecha de inicio', value: practica.inicio },
            { label: 'Fecha de término', value: practica.termino },
            { label: 'Consultorio', value: practica.consultorio },
          ].map(f => (
            <div key={f.label}>
              <div style={{ color: '#6c757d', fontSize: 11.5, fontWeight: 600, marginBottom: 3 }}>{f.label}</div>
              <div style={{ color: '#1a2744', fontSize: 14, fontWeight: 500 }}>{f.value}</div>
            </div>
          ))}
          <div>
            <div style={{ color: '#6c757d', fontSize: 11.5, fontWeight: 600, marginBottom: 3 }}>Abogado tutor</div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
              <Avatar ini={practica.abogadoIni} color={practica.abogadoColor} size={26} />
              <span style={{ color: '#1a2744', fontSize: 14, fontWeight: 500 }}>{practica.abogado}</span>
            </div>
          </div>
        </div>
      </Card>

      {/* Documentos adjuntados por el practicante en su postulación */}
      <Card style={{ marginTop: 16 }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 16 }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <Icon name="upload" size={16} /><span style={{ fontWeight: 600, color: '#1a2744', fontSize: 15 }}>Documentos subidos por el practicante</span>
          </div>
          <span style={{ color: '#6c757d', fontSize: 13 }}>{documentos.length} archivo{documentos.length === 1 ? '' : 's'}</span>
        </div>

        {documentos.length === 0 && (
          <div style={{ textAlign: 'center', padding: '30px 0', color: '#adb5bd', fontSize: 13.5 }}>El practicante no adjuntó archivos</div>
        )}

        {docsUniversitarios.length > 0 && (
          <>
            <div style={{ color: '#6c757d', fontSize: 11.5, fontWeight: 700, letterSpacing: '0.04em', marginBottom: 8 }}>DOCUMENTOS UNIVERSITARIOS</div>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 8, marginBottom: 18 }}>{docsUniversitarios.map(filaDocumento)}</div>
          </>
        )}

        {docsCedula.length > 0 && (
          <>
            <div style={{ color: '#6c757d', fontSize: 11.5, fontWeight: 700, letterSpacing: '0.04em', marginBottom: 8 }}>CÉDULA DE IDENTIDAD</div>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>{docsCedula.map(filaDocumento)}</div>
          </>
        )}
      </Card>
    </div>
  )
}

export default function App() {
  const [page, setPage] = useState<Page>('panel')
  const [practicaSel, setPracticaSel] = useState<typeof practicas[0] | null>(null)
  const [region, setRegion] = useState(regionesOperativas[0])

  if (page === 'formulario') {
    return (
      <div>
        <div style={{ background: '#1a2744', borderBottom: '3px solid #c0392b', padding: '10px 24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <button onClick={() => setPage('panel')} style={{ background: 'rgba(255,255,255,0.12)', border: 'none', color: '#fff', padding: '7px 14px', borderRadius: 7, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 8, fontSize: 13, fontWeight: 500 }}>
            <Icon name="chevron_left" size={14} /> Volver al panel
          </button>
          <span style={{ color: '#fff', fontSize: 14, fontWeight: 600 }}>Sistema de Gestión de Practicantes</span>
          <div style={{ width: 34, height: 34, borderRadius: '50%', background: '#c0392b', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#fff', fontWeight: 700, fontSize: 13 }}>SE</div>
        </div>
        <Formulario />
      </div>
    )
  }

  const titles: Record<Page, string> = {
    panel: 'Sistema de Gestión de Practicantes',
    postulaciones: 'Sistema de Gestión de Practicantes',
    practicas: 'Sistema de Gestión de Practicantes',
    historial: 'Sistema de Gestión de Practicantes',
    consultorio: 'Sistema de Gestión de Practicantes',
    formulario: 'Formulario de Postulación',
    adminDash: 'Portal del Administrador',
    abogadoDash: 'Portal del Abogado Tutor',
    practicanteDash: 'Portal del Practicante',
    estadisticaPractica: 'Sistema de Gestión de Practicantes',
  }

  const vistasConRegion: Page[] = ['panel', 'postulaciones', 'practicas', 'historial', 'consultorio', 'estadisticaPractica']
  const mostrarRegion = vistasConRegion.includes(page)
  const secretaria = secretariaPorRegion[region]

  return (
    <RegionContext.Provider value={region}>
    <div style={{ display: 'flex', minHeight: '100vh', background: '#f1f3f5' }}>
      <Sidebar page={page} setPage={setPage} />
      <div style={{ flex: 1, display: 'flex', flexDirection: 'column', minWidth: 0 }}>
        <Header
          title={titles[page]}
          notifCount={page === 'abogadoDash' ? 2 : 0}
          usuario={usuarioPorVista[page]?.nombre ?? secretaria?.nombre ?? 'Secretaria'}
          usuarioIni={usuarioPorVista[page]?.ini ?? secretaria?.ini ?? 'SE'}
          region={mostrarRegion ? region : undefined}
          setRegion={mostrarRegion ? setRegion : undefined}
        />
        <main style={{ flex: 1, background: '#f1f3f5', minHeight: 0 }}>
          {page === 'panel' && <PanelGeneral setPage={setPage} />}
          {page === 'adminDash' && <DashboardAdmin />}
          {page === 'postulaciones' && <Postulaciones />}
          {page === 'practicas' && <PracticasActivas onVerEstadistica={p => { setPracticaSel(p); setPage('estadisticaPractica') }} />}
          {page === 'estadisticaPractica' && <EstadisticaPractica practica={practicaSel} volver={() => setPage('practicas')} />}
          {page === 'historial' && <Historial />}
          {page === 'consultorio' && <Consultorios />}
          {page === 'abogadoDash' && <DashboardAbogado />}
          {page === 'practicanteDash' && <DashboardPracticante />}
        </main>
        {page === 'panel' && (
          <div style={{ position: 'fixed', bottom: 28, right: 28 }}>
            <button onClick={() => setPage('formulario')} style={{ background: '#c0392b', color: '#fff', border: 'none', padding: '13px 22px', borderRadius: 10, fontWeight: 700, fontSize: 14, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 10, boxShadow: '0 4px 16px rgba(192,57,43,0.4)' }}>
              <Icon name="user_plus" size={18} />
              Nueva postulación
            </button>
          </div>
        )}
      </div>
    </div>
    </RegionContext.Provider>
  )
}
