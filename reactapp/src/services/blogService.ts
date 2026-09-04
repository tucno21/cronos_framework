import { api } from '../lib/api'

export interface CategoriaRel {
  id: number
  nombre: string
  slug: string
  descripcion?: string
}

export interface EtiquetaRel {
  id: number
  nombre: string
  slug: string
}

export interface ComentarioRel {
  id: number
  contenido: string
  created_at: string
  usuario?: {
    id: number
    nombre: string
    avatar?: string
  }
}

export interface Publicacion {
  id: number
  usuario_id: number
  titulo: string
  slug: string
  resumen?: string
  contenido: string
  nombre_autor?: string
  usuario?: {
    id: number
    nombre: string
    correo: string
    avatar?: string
  }
  categoria_id?: number
  categoria?: CategoriaRel
  etiquetas?: EtiquetaRel[]
  comentarios?: ComentarioRel[]
  comentarios_count?: number
  estado?: 'publicado' | 'borrador' | 'archivado' | string
  vistas?: number
  publicado_en?: string
  created_at?: string
  updated_at?: string
}

export const getPublicaciones = async (): Promise<Publicacion[]> => {
  const { data } = await api.get('blogs')
  return data.publicaciones ?? []
}

export const getPublicacion = async (slug: string): Promise<Publicacion | null> => {
  const { data } = await api.get(`blogs/${slug}`)
  return data.publicacion ?? null
}

export const createPublicacion = async (payload: Pick<Publicacion, 'titulo' | 'slug' | 'contenido'>) => {
  const { data } = await api.post('blogs', payload)
  return data
}

export const updatePublicacion = async (id: number, payload: Pick<Publicacion, 'titulo' | 'slug' | 'contenido'>) => {
  const { data } = await api.put(`blogs/${id}`, payload)
  return data
}

export const deletePublicacion = async (id: number) => {
  const { data } = await api.delete(`blogs/${id}`)
  return data
}
