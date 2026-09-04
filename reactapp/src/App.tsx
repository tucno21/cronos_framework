import { createBrowserRouter } from 'react-router'
import Login from './pages/Login'
import Register from './pages/Register'
import RequireAuth from './components/RequireAuth'
import DashboardLayout from './layouts/DashboardLayout'
import Dashboard from './pages/dashboard/Dashboard'
import BlogList from './pages/dashboard/BlogList'
import BlogShow from './pages/dashboard/BlogShow'
import BlogForm from './pages/dashboard/BlogForm'
import UsuariosList from './pages/dashboard/UsuariosList'
import CategoriasList from './pages/dashboard/CategoriasList'
import EtiquetasList from './pages/dashboard/EtiquetasList'
import ComentariosList from './pages/dashboard/ComentariosList'
import OrmLab from './pages/dashboard/OrmLab'

const router = createBrowserRouter([
  { path: '/login', Component: Login },
  { path: '/register', Component: Register },
  {
    element: <RequireAuth />,
    children: [
      {
        path: '/dashboard',
        Component: DashboardLayout,
        children: [
          { index: true, Component: Dashboard },
          { path: 'blogs', Component: BlogList },
          { path: 'blogs/crear', Component: BlogForm },
          { path: 'blogs/:slug', Component: BlogShow },
          { path: 'blogs/:slug/editar', Component: BlogForm },
          { path: 'usuarios', Component: UsuariosList },
          { path: 'categorias', Component: CategoriasList },
          { path: 'etiquetas', Component: EtiquetasList },
          { path: 'comentarios', Component: ComentariosList },
          { path: 'orm-lab', Component: OrmLab },
        ],
      },
    ],
  },
])

export default router
