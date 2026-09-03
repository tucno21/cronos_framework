import { api } from '../lib/api'

export interface Publicacion {
  id: number
  usuario_id: number
  titulo: string
  slug: string
  contenido: string
  nombre_autor?: string
  estado?: string
  vistas?: number
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
