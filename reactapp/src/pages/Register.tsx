import { useState, type FormEvent } from 'react'
import { Link, useNavigate } from 'react-router'
import { useAuthStore } from '../store/authStore'
import AuthLayout from '../layouts/AuthLayout'

const inputClass =
  'w-full px-3 py-2 border rounded-md shadow-sm text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent border-gray-300'

const Register = () => {
  const navigate = useNavigate()
  const register = useAuthStore((state) => state.register)
  const loading = useAuthStore((state) => state.loading)
  const error = useAuthStore((state) => state.error)

  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [confirmPassword, setConfirmPassword] = useState('')

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault()
    const ok = await register(name, email, password, confirmPassword)
    if (ok) {
      navigate('/dashboard')
    }
  }

  return (
    <AuthLayout title="Crear Cuenta">
      {error && (
        <div className="mb-6 p-3 rounded-md bg-red-50 text-red-600 text-sm border border-red-200">
          {Array.isArray(error) ? error.join(', ') : error}
        </div>
      )}

      <form onSubmit={handleSubmit}>
        <div className="mb-4">
          <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
            Nombre<span className="text-red-500 ml-0.5">*</span>
          </label>
          <input
            type="text"
            id="name"
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Ingresa tu nombre"
            required
            className={inputClass}
          />
        </div>

        <div className="mb-4">
          <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
            Email<span className="text-red-500 ml-0.5">*</span>
          </label>
          <input
            type="email"
            id="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="Ingresa tu correo electrónico"
            required
            className={inputClass}
          />
        </div>

        <div className="mb-4">
          <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-1">
            Contraseña<span className="text-red-500 ml-0.5">*</span>
          </label>
          <input
            type="password"
            id="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="Crea una contraseña"
            required
            className={inputClass}
          />
        </div>

        <div className="mb-4">
          <label htmlFor="confirm_password" className="block text-sm font-medium text-gray-700 mb-1">
            Confirmar Contraseña<span className="text-red-500 ml-0.5">*</span>
          </label>
          <input
            type="password"
            id="confirm_password"
            value={confirmPassword}
            onChange={(e) => setConfirmPassword(e.target.value)}
            placeholder="Confirma tu contraseña"
            required
            className={inputClass}
          />
        </div>

        <button
          type="submit"
          disabled={loading}
          className="inline-flex items-center justify-center gap-2 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed bg-blue-600 hover:bg-blue-700 text-white border-transparent px-6 py-3 text-base w-full"
        >
          {loading ? 'Creando cuenta...' : 'Crear Cuenta'}
        </button>
      </form>

      <p className="mt-8 text-center text-gray-600">
        ¿Ya tienes una cuenta?{' '}
        <Link to="/login" className="auth-link">
          Inicia sesión aquí
        </Link>
      </p>
    </AuthLayout>
  )
}

export default Register
