import { createBrowserRouter } from 'react-router'
import Login from './pages/Login'
import Register from './pages/Register'
import RequireAuth from './components/RequireAuth'
import DashboardLayout from './layouts/DashboardLayout'
import Dashboard from './pages/dashboard/Dashboard'
import BlogList from './pages/dashboard/BlogList'
import BlogShow from './pages/dashboard/BlogShow'
import BlogForm from './pages/dashboard/BlogForm'

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
        ],
      },
    ],
  },
])

export default router
