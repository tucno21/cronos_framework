import { api } from '../lib/api'

export interface Perfil {
  id: number
  usuario_id: number
  biografia?: string
  telefono?: string
  fecha_nacimiento?: string
  sitio_web?: string
}

export interface Rol {
  id: number
  nombre: string
  slug: string
  descripcion?: string
}

export interface Usuario {
  id: number
  nombre: string
  correo: string
  rol: 'admin' | 'editor' | 'usuario'
  avatar?: string
  correo_verificado_en?: string
  invitado_por?: number
  invitado_por_usuario?: {
    id: number
    nombre: string
    correo: string
  }
  invitadoPor?: {
    id: number
    nombre: string
    correo: string
  }
  perfil?: Perfil | null
  roles?: Rol[]
  invitados?: Usuario[]
  publicaciones_count?: number
  comentarios_count?: number
  created_at?: string
  updated_at?: string
}

export interface UsuarioFilterParams {
  rol?: string
  con_perfil?: string
  con_invitador?: string
}

export const getUsuarios = async (params?: UsuarioFilterParams): Promise<Usuario[]> => {
  const { data } = await api.get('usuarios', { params })
  if (Array.isArray(data.usuarios)) {
    return data.usuarios
  }
  if (Array.isArray(data.usuarios?.data)) {
    return data.usuarios.data
  }
  return []
}

export const getUsuario = async (id: number): Promise<Usuario | null> => {
  const { data } = await api.get(`usuarios/${id}`)
  return data.usuario ?? null
}
