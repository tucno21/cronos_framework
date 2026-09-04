import { api } from '../lib/api'

export interface OrmQueryDemo {
  id: string
  seccion: string
  titulo: string
  descripcion: string
  php: string
  sql: string
  resultado: unknown
  filas: number
  duracion_ms: number
}

export interface OrmLabResponse {
  status: string
  total_consultas: number
  consultas: OrmQueryDemo[]
}

export const getOrmLabQueries = async (): Promise<OrmLabResponse> => {
  const { data } = await api.get('orm-lab')
  return data
}
