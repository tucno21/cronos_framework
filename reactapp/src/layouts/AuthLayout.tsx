import { Link } from 'react-router'
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
              <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" className="bi bi-cpu-fill drop-shadow-lg" viewBox="0 0 16 16">
                <path d="M6.5 6a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z" />
                <path d="M5.5.5a.5.5 0 0 0-1 0V2A2.5 2.5 0 0 0 2 4.5H.5a.5.5 0 0 0 0 1H2v1H.5a.5.5 0 0 0 0 1H2v1H.5a.5.5 0 0 0 0 1H2v1H.5a.5.5 0 0 0 0 1H2A2.5 2.5 0 0 0 4.5 14v1.5a.5.5 0 0 0 1 0V14h1v1.5a.5.5 0 0 0 1 0V14h1v1.5a.5.5 0 0 0 1 0V14h1v1.5a.5.5 0 0 0 1 0V14h1v1.5a.5.5 0 0 0 1 0V14a2.5 2.5 0 0 0 2.5-2.5h1.5a.5.5 0 0 0 0-1H14v-1h1.5a.5.5 0 0 0 0-1H14v-1h1.5a.5.5 0 0 0 0-1H14v-1h1.5a.5.5 0 0 0 0-1H14A2.5 2.5 0 0 0 11.5 2V.5a.5.5 0 0 0-1 0V2h-1V.5a.5.5 0 0 0-1 0V2h-1V.5a.5.5 0 0 0-1 0V2h-1V.5zm1 4.5h3A1.5 1.5 0 0 1 11 6.5v3A1.5 1.5 0 0 1 9.5 11h-3A1.5 1.5 0 0 1 5 9.5v-3A1.5 1.5 0 0 1 6.5 5z" />
              </svg>
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
