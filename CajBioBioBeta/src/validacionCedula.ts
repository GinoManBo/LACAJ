
import { GoogleGenAI, Type } from '@google/genai'

export const MODELO_GEMINI = 'gemini-3.6-flash'

const apiKey = import.meta.env.VITE_GEMINI_API_KEY as string | undefined

export const geminiConfigurado = Boolean(apiKey)

type LecturaGemini = {
  frontal: { esCedulaChilena: boolean; esLadoFrontal: boolean; legible: boolean; problemas: string[] }
  reverso: { esCedulaChilena: boolean; esLadoReverso: boolean; legible: boolean; problemas: string[] }
  run: string | null
  numeroDocumentoFrontal: string | null
  numeroDocumentoReverso: string | null
  fechaVencimiento: string | null
  observaciones: string
}

export type Severidad = 'ok' | 'advertencia' | 'error'

export type Comprobacion = {
  id: string
  titulo: string
  severidad: Severidad
  detalle: string
}

export type ResultadoValidacion = {
  veredicto: 'aprobada' | 'con_reparos' | 'rechazada'
  comprobaciones: Comprobacion[]
  run: string | null
  fechaVencimiento: string | null
  observaciones: string
}

export function digitoVerificadorValido(run: string): boolean {
  const limpio = run.replace(/[.\-\s]/g, '').toUpperCase()
  const cuerpo = limpio.slice(0, -1)
  const dv = limpio.slice(-1)
  if (!/^\d{7,8}$/.test(cuerpo) || !/^[\dK]$/.test(dv)) return false

  let suma = 0
  let factor = 2
  for (let i = cuerpo.length - 1; i >= 0; i--) {
    suma += Number(cuerpo[i]) * factor
    factor = factor === 7 ? 2 : factor + 1
  }
  const resto = 11 - (suma % 11)
  const esperado = resto === 11 ? '0' : resto === 10 ? 'K' : String(resto)
  return dv === esperado
}

export function formatearRun(run: string): string {
  const limpio = run.replace(/[.\-\s]/g, '').toUpperCase()
  const cuerpo = limpio.slice(0, -1)
  const dv = limpio.slice(-1)
  return `${cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, '.')}-${dv}`
}

function fechaDesdeISO(iso: string): Date | null {
  const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso.trim())
  if (!m) return null
  const [, y, mes, d] = m
  const fecha = new Date(Number(y), Number(mes) - 1, Number(d))
  return Number.isNaN(fecha.getTime()) ? null : fecha
}

const formatoFecha = (iso: string) => {
  const f = fechaDesdeISO(iso)
  return f ? f.toLocaleDateString('es-CL') : iso
}

const INSTRUCCIONES = `Eres un verificador de documentos de identidad chilenos.

Recibes dos imágenes: la primera debe ser el LADO FRONTAL de una cédula de identidad chilena, la segunda el LADO REVERSO.

Referencias de la cédula de identidad chilena vigente:
- Lado frontal: fotografía del titular, nombres, apellidos, nacionalidad, sexo, fecha de nacimiento, número de RUN, fecha de emisión y fecha de vencimiento, firma.
- Lado reverso: número de documento, código de barras PDF417, huella dactilar, datos de emisión.

Reporta ÚNICAMENTE lo que puedas observar en las imágenes. No inventes datos ni completes campos que no logres leer con claridad: en ese caso devuelve null.

Para cada imagen indica:
- esCedulaChilena: si el documento es una cédula de identidad chilena (false si es licencia de conducir, pasaporte, TNE, tarjeta bancaria u otro).
- esLadoFrontal / esLadoReverso: si la imagen corresponde a la cara esperada para esa posición.
- legible: si el texto principal se lee con nitidez suficiente para transcribirlo.
- problemas: lista breve en español de defectos concretos observados (por ejemplo: "imagen borrosa", "reflejo sobre el RUN", "documento cortado en el borde inferior", "fotografía de una pantalla", "iluminación insuficiente"). Lista vacía si no hay defectos.

Además extrae:
- run: el RUN del titular tal como aparece, con guion y dígito verificador. null si no se lee.
- numeroDocumentoFrontal y numeroDocumentoReverso: el número de documento visible en cada cara. null si no se lee.
- fechaVencimiento: en formato YYYY-MM-DD. null si no se lee.
- observaciones: una o dos frases en español resumiendo el estado general de las imágenes.`

const esquema = {
  type: Type.OBJECT,
  properties: {
    frontal: {
      type: Type.OBJECT,
      properties: {
        esCedulaChilena: { type: Type.BOOLEAN },
        esLadoFrontal: { type: Type.BOOLEAN },
        legible: { type: Type.BOOLEAN },
        problemas: { type: Type.ARRAY, items: { type: Type.STRING } },
      },
      required: ['esCedulaChilena', 'esLadoFrontal', 'legible', 'problemas'],
    },
    reverso: {
      type: Type.OBJECT,
      properties: {
        esCedulaChilena: { type: Type.BOOLEAN },
        esLadoReverso: { type: Type.BOOLEAN },
        legible: { type: Type.BOOLEAN },
        problemas: { type: Type.ARRAY, items: { type: Type.STRING } },
      },
      required: ['esCedulaChilena', 'esLadoReverso', 'legible', 'problemas'],
    },
    run: { type: Type.STRING, nullable: true },
    numeroDocumentoFrontal: { type: Type.STRING, nullable: true },
    numeroDocumentoReverso: { type: Type.STRING, nullable: true },
    fechaVencimiento: { type: Type.STRING, nullable: true },
    observaciones: { type: Type.STRING },
  },
  required: ['frontal', 'reverso', 'run', 'numeroDocumentoFrontal', 'numeroDocumentoReverso', 'fechaVencimiento', 'observaciones'],
}

function archivoABase64(file: File): Promise<{ data: string; mimeType: string }> {
  return new Promise((resolve, reject) => {
    const lector = new FileReader()
    lector.onerror = () => reject(new Error('No se pudo leer la imagen.'))
    lector.onload = () => {
      const resultado = String(lector.result)
      const coma = resultado.indexOf(',')
      resolve({ data: resultado.slice(coma + 1), mimeType: file.type || 'image/jpeg' })
    }
    lector.readAsDataURL(file)
  })
}

export async function validarCedula(frontal: File, reverso: File): Promise<ResultadoValidacion> {
  if (!apiKey) {
    throw new Error('Falta VITE_GEMINI_API_KEY. Cree un archivo .env.local con su clave de Gemini y reinicie el servidor.')
  }

  const ai = new GoogleGenAI({ apiKey })
  const [imgFrontal, imgReverso] = await Promise.all([archivoABase64(frontal), archivoABase64(reverso)])

  const respuesta = await ai.models.generateContent({
    model: MODELO_GEMINI,
    contents: [{
      role: 'user',
      parts: [
        { text: 'Primera imagen: lado frontal declarado.' },
        { inlineData: imgFrontal },
        { text: 'Segunda imagen: lado reverso declarado.' },
        { inlineData: imgReverso },
      ],
    }],
    config: {
      systemInstruction: INSTRUCCIONES,
      responseMimeType: 'application/json',
      responseSchema: esquema,
      thinkingConfig: { thinkingBudget: 0 },
    },
  })

  const texto = respuesta.text
  if (!texto) throw new Error('Gemini no devolvió una respuesta legible. Intente nuevamente.')

  let lectura: LecturaGemini
  try {
    lectura = JSON.parse(texto)
  } catch {
    throw new Error('La respuesta de Gemini no tuvo el formato esperado. Intente nuevamente.')
  }

  return construirResultado(lectura)
}

function construirResultado(l: LecturaGemini): ResultadoValidacion {
  const c: Comprobacion[] = []

  const problemas = [
    ...l.frontal.problemas.map(p => `Frontal: ${p}`),
    ...l.reverso.problemas.map(p => `Reverso: ${p}`),
  ]
  const ambasLegibles = l.frontal.legible && l.reverso.legible
  c.push({
    id: 'calidad',
    titulo: 'Calidad de las imágenes',
    severidad: !ambasLegibles ? 'error' : problemas.length > 0 ? 'advertencia' : 'ok',
    detalle: !ambasLegibles
      ? `El texto no se lee con nitidez suficiente. ${problemas.join('. ') || 'Vuelva a capturar ambas caras.'}`
      : problemas.length > 0
        ? problemas.join('. ')
        : 'Ambas caras se leen con nitidez.',
  })

  const ambasCedula = l.frontal.esCedulaChilena && l.reverso.esCedulaChilena
  c.push({
    id: 'tipo',
    titulo: 'Tipo de documento',
    severidad: ambasCedula ? 'ok' : 'error',
    detalle: ambasCedula
      ? 'Ambas imágenes corresponden a una cédula de identidad chilena.'
      : 'Alguna de las imágenes no corresponde a una cédula de identidad chilena.',
  })

  const ladosOk = l.frontal.esLadoFrontal && l.reverso.esLadoReverso
  c.push({
    id: 'lados',
    titulo: 'Cara correcta en cada posición',
    severidad: ladosOk ? 'ok' : 'error',
    detalle: ladosOk
      ? 'El frente y el reverso están en la posición que corresponde.'
      : !l.frontal.esLadoFrontal && !l.reverso.esLadoReverso
        ? 'Ambas imágenes parecen estar invertidas. Intercámbielas.'
        : !l.frontal.esLadoFrontal
          ? 'La imagen cargada como frontal no muestra el frente de la cédula.'
          : 'La imagen cargada como reverso no muestra el reverso de la cédula.',
  })

  const nf = l.numeroDocumentoFrontal?.replace(/\s/g, '')
  const nr = l.numeroDocumentoReverso?.replace(/\s/g, '')
  c.push({
    id: 'coincidencia',
    titulo: 'Frente y reverso de la misma cédula',
    severidad: !nf || !nr ? 'advertencia' : nf === nr ? 'ok' : 'error',
    detalle: !nf || !nr
      ? 'No se pudo leer el número de documento en ambas caras, por lo que no fue posible confirmar que correspondan a la misma cédula.'
      : nf === nr
        ? `Ambas caras comparten el número de documento ${nf}.`
        : `Los números de documento no coinciden: ${nf} en el frente y ${nr} en el reverso.`,
  })

  const runValido = l.run ? digitoVerificadorValido(l.run) : false
  c.push({
    id: 'run',
    titulo: 'RUN y dígito verificador',
    severidad: !l.run ? 'advertencia' : runValido ? 'ok' : 'error',
    detalle: !l.run
      ? 'No se pudo leer el RUN en la imagen.'
      : runValido
        ? `RUN ${formatearRun(l.run)}: el dígito verificador es correcto.`
        : `El dígito verificador de ${l.run} no corresponde. La lectura puede ser errónea o el RUN no es válido.`,
  })

  const vence = l.fechaVencimiento ? fechaDesdeISO(l.fechaVencimiento) : null
  const hoy = new Date()
  hoy.setHours(0, 0, 0, 0)
  const vencida = vence ? vence < hoy : false
  const porVencer = vence && !vencida && (vence.getTime() - hoy.getTime()) / 86400000 <= 90
  c.push({
    id: 'vigencia',
    titulo: 'Vigencia del documento',
    severidad: !vence ? 'advertencia' : vencida ? 'error' : porVencer ? 'advertencia' : 'ok',
    detalle: !vence
      ? 'No se pudo leer la fecha de vencimiento.'
      : vencida
        ? `La cédula venció el ${formatoFecha(l.fechaVencimiento!)}.`
        : porVencer
          ? `La cédula vence el ${formatoFecha(l.fechaVencimiento!)}, dentro de los próximos 90 días.`
          : `Vigente hasta el ${formatoFecha(l.fechaVencimiento!)}.`,
  })

  const veredicto = c.some(x => x.severidad === 'error')
    ? 'rechazada'
    : c.some(x => x.severidad === 'advertencia')
      ? 'con_reparos'
      : 'aprobada'

  return {
    veredicto,
    comprobaciones: c,
    run: l.run && runValido ? formatearRun(l.run) : l.run,
    fechaVencimiento: l.fechaVencimiento,
    observaciones: l.observaciones,
  }
}
