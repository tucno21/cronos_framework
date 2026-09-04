import { api } from '../lib/api'
import type { Publicacion } from './blogService'

export interface DashboardMetricas {
  usuarios: number
  publicaciones: number
  categorias: number
  etiquetas: number
  comentarios: number
  roles: number
  vistas: {
    total: number
    promedio: number
    max: number
    min: number
  }
  estados: {
    publicado: number
    borrador: number
    archivado: number
  }
}

export interface CategoriaConteo {
  id: number
  nombre: string
  slug: string
  publicaciones_count: number
}

export interface RolConteo {
  id: number
  nombre: string
  slug: string
  usuarios_count: number
}

export interface ComentarioReciente {
  id: number
  contenido: string
  created_at: string
  usuario?: {
    id: number
    nombre: string
  }
  publicacion?: {
    id: number
    titulo: string
    slug: string
  }
}

export interface DashboardData {
  metricas: DashboardMetricas
  top_publicaciones: Publicacion[]
  ultimas_publicaciones: Publicacion[]
  ultimos_comentarios: ComentarioReciente[]
  categorias: CategoriaConteo[]
  roles: RolConteo[]
}

export const getDashboardStats = async (): Promise<DashboardData> => {
  const { data } = await api.get('stats')
  return data.data
}
