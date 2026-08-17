import { Link, Outlet } from 'react-router'

const navItems = [
  { to: '/prueba/page1', label: 'Página 1' },
  { to: '/prueba/page2', label: 'Página 2' },
]

const Layout = () => {
  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-gray-900 text-white px-5 py-4">
        <div className="flex items-center gap-6">
          <h1 className="font-bold text-lg">Cronos SPA</h1>
          {navItems.map((item) => (
            <Link
              key={item.to}
              to={item.to}
              className="text-gray-300 hover:text-white transition-colors"
            >
              {item.label}
            </Link>
          ))}
        </div>
      </nav>

      <main className="p-8">
        <Outlet />
      </main>
    </div>
  )
}

export default Layout
