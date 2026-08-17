import { NavLink, Outlet, useNavigate } from 'react-router'
import { useAuthStore } from '../store/authStore'

const navItems = [
  { to: '/dashboard', label: 'Dashboard', icon: 'bi-grid-1x2-fill' },
  { to: '/dashboard/blogs', label: 'Blogs', icon: 'bi-journal-text' },
]

const DashboardLayout = () => {
  const navigate = useNavigate()
  const user = useAuthStore((state) => state.user)
  const logout = useAuthStore((state) => state.logout)

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="fixed inset-y-0 left-0 w-56 bg-gray-900 text-white flex flex-col">
        <div className="flex items-center gap-2 px-5 py-4 border-b border-gray-800">
          <i className="bi bi-cpu-fill text-blue-400 text-xl"></i>
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
              <i className={`bi ${item.icon}`}></i>
              <span>{item.label}</span>
            </NavLink>
          ))}
        </div>

        <div className="px-4 py-4 border-t border-gray-800 space-y-3">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-full bg-blue-600 flex items-center justify-center text-sm">
              <i className="bi bi-person-fill"></i>
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium truncate">{user?.name ?? 'Usuario'}</p>
              <p className="text-xs text-gray-400 capitalize">{user?.role ?? ''}</p>
            </div>
          </div>
          <button
            onClick={handleLogout}
            className="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-300 hover:bg-red-600/20 hover:text-red-300 transition-colors"
          >
            <i className="bi bi-box-arrow-right text-lg"></i>
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
