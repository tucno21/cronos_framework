import { api } from '../lib/api'

export interface Categoria {
  id: number
  nombre: string
  slug: string
  descripcion?: string
  categoria_padre_id?: number | null
  categoria_padre?: Categoria | null
  categoriaPadre?: Categoria | null
  subcategorias?: Categoria[]
  publicaciones_count?: number
  created_at?: string
  updated_at?: string
}

export const getCategorias = async (soloRaices = false): Promise<Categoria[]> => {
  const { data } = await api.get('categorias', {
    params: soloRaices ? { solo_raices: 'true' } : undefined,
  })
  return data.categorias ?? []
}

export const getCategoriasArbol = async (): Promise<Categoria[]> => {
  const { data } = await api.get('categorias/arbol')
  return data.arbol ?? []
}
