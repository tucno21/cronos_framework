import { Link } from 'react-router'
import { BookOpen } from 'lucide-react'
import { useAuthStore } from '../../store/authStore'

const Dashboard = () => {
  const user = useAuthStore((state) => state.user)

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-800">
        Bienvenido, {user?.name ?? 'Usuario'}
      </h1>
      <p className="mt-1 text-gray-600">Este es el panel de administración.</p>

      <div className="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6 max-w-3xl">
        <Link
          to="/dashboard/blogs"
          className="card p-6 border border-gray-200 rounded-xl bg-white shadow-sm hover:shadow-md transition-shadow"
        >
          <div className="flex items-center gap-3">
            <div className="w-11 h-11 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
              <BookOpen size={22} />
            </div>
            <div>
              <h3 className="font-semibold text-gray-800">Blogs</h3>
              <p className="text-sm text-gray-500">Administrar publicaciones</p>
            </div>
          </div>
        </Link>
      </div>
    </div>
  )
}

export default Dashboard
