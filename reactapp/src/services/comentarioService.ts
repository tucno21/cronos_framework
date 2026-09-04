import { api } from '../lib/api'

export interface Comentario {
  id: number
  publicacion_id: number
  usuario_id: number
  contenido: string
  created_at: string
  updated_at?: string
  usuario?: {
    id: number
    nombre: string
    correo: string
    avatar?: string
  }
  publicacion?: {
    id: number
    titulo: string
    slug: string
  }
}

export const getComentarios = async (): Promise<Comentario[]> => {
  const { data } = await api.get('comentarios')
  return data.comentarios ?? []
}

export const deleteComentario = async (id: number) => {
  const { data } = await api.delete(`comentarios/${id}`)
  return data
}
