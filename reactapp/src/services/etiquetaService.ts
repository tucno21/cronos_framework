import { api } from '../lib/api'
import type { Publicacion } from './blogService'

export interface Etiqueta {
  id: number
  nombre: string
  slug: string
  publicaciones_count?: number
  publicaciones?: Publicacion[]
}

export const getEtiquetas = async (): Promise<Etiqueta[]> => {
  const { data } = await api.get('etiquetas')
  return data.etiquetas ?? []
}
