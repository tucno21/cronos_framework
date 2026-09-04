import { NavLink, Outlet, useNavigate } from 'react-router'
import {
  BookOpen,
  Cpu,
  LayoutGrid,
  LogOut,
  User,
  Users,
  FolderTree,
  Tag,
  MessageSquare,
  Database,
} from 'lucide-react'
import { useAuthStore } from '../store/authStore'

const navItems = [
  { to: '/dashboard', label: 'Dashboard', icon: LayoutGrid },
  { to: '/dashboard/blogs', label: 'Blogs', icon: BookOpen },
  { to: '/dashboard/usuarios', label: 'Usuarios & Roles', icon: Users },
  { to: '/dashboard/categorias', label: 'Categorías', icon: FolderTree },
  { to: '/dashboard/etiquetas', label: 'Etiquetas', icon: Tag },
  { to: '/dashboard/comentarios', label: 'Comentarios', icon: MessageSquare },
  { to: '/dashboard/orm-lab', label: 'Laboratorio ORM', icon: Database },
]

const DashboardLayout = () => {
  const navigate = useNavigate()
  const usuario = useAuthStore((state) => state.usuario)
  const logout = useAuthStore((state) => state.logout)

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="fixed inset-y-0 left-0 w-56 bg-gray-900 text-white flex flex-col">
        <div className="flex items-center gap-2 px-5 py-4 border-b border-gray-800">
          <Cpu className="text-blue-400 text-xl" size={22} />
          <h1 className="font-bold text-lg">Cronos</h1>
        </div>

        <div className="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
          <span className="block px-2 pb-2 text-xs font-semibold text-gray-500 uppercase">
            Principal
          </span>
          {navItems.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.to === '/dashboard'}
              className={({ isActive }) =>
                `flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors ${
                  isActive
                    ? 'bg-blue-600 text-white'
                    : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                }`
              }
            >
              <item.icon size={18} />
              <span>{item.label}</span>
            </NavLink>
          ))}
        </div>

        <div className="px-4 py-4 border-t border-gray-800 space-y-3">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-full bg-blue-600 flex items-center justify-center text-sm">
              <User size={16} />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium truncate">{usuario?.nombre ?? 'Usuario'}</p>
              <p className="text-xs text-gray-400 capitalize">{usuario?.rol ?? ''}</p>
            </div>
          </div>
          <button
            onClick={handleLogout}
            className="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-300 hover:bg-red-600/20 hover:text-red-300 transition-colors"
          >
            <LogOut size={18} />
            Cerrar sesión
          </button>
        </div>
      </nav>

      <main className="ml-56 p-8">
        <Outlet />
      </main>
    </div>
  )
}

export default DashboardLayout
