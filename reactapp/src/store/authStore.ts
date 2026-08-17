import { create } from 'zustand'
import { api } from '../lib/api'

interface User {
  id: number
  name: string
  email: string
  role: string
}

interface AuthState {
  user: User | null
  token: string | null
  loading: boolean
  error: string | null
  login: (email: string, password: string) => Promise<boolean>
  register: (name: string, email: string, password: string, confirm_password: string) => Promise<boolean>
  fetchMe: () => Promise<boolean>
  logout: () => Promise<void>
}

export const useAuthStore = create<AuthState>((set) => ({
  user: JSON.parse(localStorage.getItem('user') ?? 'null'),
  token: localStorage.getItem('token'),
  loading: false,
  error: null,

  login: async (email, password) => {
    set({ loading: true, error: null })
    try {
      const { data } = await api.post('login', { email, password })
      if (data.status === 'success') {
        localStorage.setItem('token', data.token)
        localStorage.setItem('user', JSON.stringify(data.user))
        set({ user: data.user, token: data.token, loading: false })
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

  register: async (name, email, password, confirm_password) => {
    set({ loading: true, error: null })
    try {
      const { data } = await api.post('register', {
        name,
        email,
        password,
        confirm_password,
      })
      if (data.status === 'success') {
        localStorage.setItem('token', data.token)
        localStorage.setItem('user', JSON.stringify(data.user))
        set({ user: data.user, token: data.token, loading: false })
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
        localStorage.setItem('user', JSON.stringify(data.user))
        set({ user: data.user, token: localStorage.getItem('token') })
        return true
      }
      return false
    } catch {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      set({ user: null, token: null })
      return false
    }
  },

  logout: async () => {
    try {
      await api.post('logout')
    } catch {
    }
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    set({ user: null, token: null })
  },
}))
