import { Navigate, Outlet, useLocation } from 'react-router'
import { useAuthStore } from '../store/authStore'

const RequireAuth = () => {
  const token = useAuthStore((state) => state.token)
  const location = useLocation()

  if (!token) {
    return <Navigate to="/login" state={{ from: location }} replace />
  }

  return <Outlet />
}

export default RequireAuth
