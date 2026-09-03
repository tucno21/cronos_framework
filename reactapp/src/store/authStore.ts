import { create } from 'zustand'
import { api } from '../lib/api'

interface Usuario {
  id: number
  nombre: string
  correo: string
  rol: string
}

interface AuthState {
  usuario: Usuario | null
  token: string | null
  loading: boolean
  error: string | null
  login: (correo: string, contrasena: string) => Promise<boolean>
  register: (nombre: string, correo: string, contrasena: string, confirmar_contrasena: string) => Promise<boolean>
  fetchMe: () => Promise<boolean>
  logout: () => Promise<void>
}

export const useAuthStore = create<AuthState>((set) => ({
  usuario: JSON.parse(localStorage.getItem('usuario') ?? 'null'),
  token: localStorage.getItem('token'),
  loading: false,
  error: null,

  login: async (correo, contrasena) => {
    set({ loading: true, error: null })
    try {
      const { data } = await api.post('login', { correo, contrasena })
      if (data.status === 'success') {
        localStorage.setItem('token', data.token)
        localStorage.setItem('usuario', JSON.stringify(data.usuario))
        set({ usuario: data.usuario, token: data.token, loading: false })
        return true
      }
      set({ error: data.message, loading: false })
      return false
    } catch (error: any) {
      const message = error.response?.data?.message ?? 'Error al iniciar sesión'
      set({ error: message, loading: false })
      return false
    }
  },

  register: async (nombre, correo, contrasena, confirmar_contrasena) => {
    set({ loading: true, error: null })
    try {
      const { data } = await api.post('register', {
        nombre,
        correo,
        contrasena,
        confirmar_contrasena,
      })
      if (data.status === 'success') {
        localStorage.setItem('token', data.token)
        localStorage.setItem('usuario', JSON.stringify(data.usuario))
        set({ usuario: data.usuario, token: data.token, loading: false })
        return true
      }
      set({ error: data.message, loading: false })
      return false
    } catch (error: any) {
      const message = error.response?.data?.message ?? 'Error al registrarse'
      set({ error: message, loading: false })
      return false
    }
  },

  fetchMe: async () => {
    try {
      const { data } = await api.get('me')
      if (data.status === 'success') {
        localStorage.setItem('usuario', JSON.stringify(data.usuario))
        set({ usuario: data.usuario, token: localStorage.getItem('token') })
        return true
      }
      return false
    } catch {
      localStorage.removeItem('token')
      localStorage.removeItem('usuario')
      set({ usuario: null, token: null })
      return false
    }
  },

  logout: async () => {
    try {
      await api.post('logout')
    } catch {
    }
    localStorage.removeItem('token')
    localStorage.removeItem('usuario')
    set({ usuario: null, token: null })
  },
}))
