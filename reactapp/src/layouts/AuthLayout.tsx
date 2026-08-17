import { Link } from 'react-router'
import { Cpu } from 'lucide-react'
import type { ReactNode } from 'react'

interface AuthLayoutProps {
  title: string
  children: ReactNode
}

const AuthLayout = ({ title, children }: AuthLayoutProps) => {
  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 font-sans">
      <header className="bg-gradient-to-r from-blue-700 via-blue-800 to-blue-900 fixed top-0 w-full h-16 flex z-20">
        <nav className="flex justify-between items-center w-[92%] mx-auto">
          <div>
            <a href="/" className="text-white flex justify-center items-center gap-2">
              <Cpu size={30} className="drop-shadow-lg" />
              <span className="font-bold text-2xl drop-shadow-lg hidden md:block">Cronos Framework</span>
              <span className="font-bold text-2xl drop-shadow-lg md:hidden">Cronos</span>
            </a>
          </div>
          <div>
            <ul className="flex flex-col md:flex-row md:items-center md:gap-[4vw] gap-8 text-white text-2xl md:text-base">
              <li>
                <a className="border-b md:border-none md:hover:font-semibold md:hover:bg-blue-700 md:px-2 md:py-1 md:rounded-tr-lg md:rounded-bl-lg transition duration-300" href="/">
                  Home
                </a>
              </li>
              <li>
                <Link className="border-b md:border-none md:hover:font-semibold md:hover:bg-blue-700 md:px-2 md:py-1 md:rounded-tr-lg md:rounded-bl-lg transition duration-300" to="/login">
                  Login
                </Link>
              </li>
              <li>
                <Link className="border-b md:border-none md:hover:font-semibold md:hover:bg-blue-700 md:px-2 md:py-1 md:rounded-tr-lg md:rounded-bl-lg transition duration-300" to="/register">
                  Register
                </Link>
              </li>
            </ul>
          </div>
        </nav>
      </header>

      <main className="pt-16 min-h-screen flex items-center justify-center px-4">
        <div className="auth-card">
          <h2 className="auth-title">{title}</h2>
          {children}
        </div>
      </main>
    </div>
  )
}

export default AuthLayout
