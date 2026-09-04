import { useEffect, useState } from 'react'
import { Link } from 'react-router'
import {
  BookOpen,
  Users,
  FolderTree,
  Tag,
  MessageSquare,
  Flame,
  Eye,
  TrendingUp,
  Sparkles,
  ArrowRight,
  Database,
  CheckCircle2,
  Clock,
  Archive,
} from 'lucide-react'
import { useAuthStore } from '../../store/authStore'
import { getDashboardStats, type DashboardData } from '../../services/dashboardService'

const Dashboard = () => {
  const usuario = useAuthStore((state) => state.usuario)
  const [data, setData] = useState<DashboardData | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    const fetchStats = async () => {
      try {
        setLoading(true)
        const res = await getDashboardStats()
        setData(res)
      } catch {
        setError('No se pudieron cargar las estadísticas del servidor.')
      } finally {
        setLoading(false)
      }
    }
    fetchStats()
  }, [])

  const m = data?.metricas

  return (
    <div className="space-y-8 max-w-7xl">
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 className="text-3xl font-black text-gray-900 tracking-tight">
            Hola, {usuario?.nombre ?? 'Usuario'} 👋
          </h1>
          <p className="mt-1 text-gray-600">
            Panel de control impulsado por el ORM Cronos y base de datos relacional MySQL.
          </p>
        </div>
        <Link
          to="/dashboard/orm-lab"
          className="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm hover:shadow transition-all"
        >
          <Database size={17} />
          Explorador de Consultas ORM
          <ArrowRight size={15} />
        </Link>
      </div>

      {error && (
        <div className="p-4 rounded-xl bg-red-50 text-red-700 text-sm border border-red-200">
          {error}
        </div>
      )}

      {/* Grid de Métricas Principales (Agregados ORM) */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div className="p-5 bg-white border border-gray-200/80 rounded-2xl shadow-xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-gray-500 uppercase tracking-wider">
              Publicaciones
            </span>
            <div className="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
              <BookOpen size={18} />
            </div>
          </div>
          <div className="mt-3">
            <span className="text-3xl font-extrabold text-gray-900">
              {loading ? '...' : (m?.publicaciones ?? 0)}
            </span>
            <span className="ml-2 text-xs text-gray-500 font-medium">artículos</span>
          </div>
          <div className="mt-3 text-xs text-gray-600 flex items-center gap-2 border-t border-gray-100 pt-2">
            <span className="text-emerald-600 font-semibold">{m?.estados.publicado ?? 0}</span> pub. |{' '}
            <span className="text-amber-600 font-semibold">{m?.estados.borrador ?? 0}</span> borrador
          </div>
        </div>

        <div className="p-5 bg-white border border-gray-200/80 rounded-2xl shadow-xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-gray-500 uppercase tracking-wider">
              Vistas Totales
            </span>
            <div className="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
              <Eye size={18} />
            </div>
          </div>
          <div className="mt-3">
            <span className="text-3xl font-extrabold text-gray-900">
              {loading ? '...' : (m?.vistas.total?.toLocaleString() ?? 0)}
            </span>
            <span className="ml-2 text-xs text-indigo-600 font-medium">sum(vistas)</span>
          </div>
          <div className="mt-3 text-xs text-gray-600 flex items-center gap-1 border-t border-gray-100 pt-2">
            <TrendingUp size={13} className="text-indigo-500" />
            Promedio: <strong className="text-gray-800">{m?.vistas.promedio ?? 0}</strong> por post
          </div>
        </div>

        <div className="p-5 bg-white border border-gray-200/80 rounded-2xl shadow-xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-gray-500 uppercase tracking-wider">
              Usuarios
            </span>
            <div className="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
              <Users size={18} />
            </div>
          </div>
          <div className="mt-3">
            <span className="text-3xl font-extrabold text-gray-900">
              {loading ? '...' : (m?.usuarios ?? 0)}
            </span>
            <span className="ml-2 text-xs text-gray-500 font-medium">miembros</span>
          </div>
          <div className="mt-3 text-xs text-gray-600 flex items-center gap-1 border-t border-gray-100 pt-2">
            <Sparkles size={13} className="text-purple-500" />
            <strong className="text-gray-800">{m?.roles ?? 0}</strong> roles configurados
          </div>
        </div>

        <div className="p-5 bg-white border border-gray-200/80 rounded-2xl shadow-xs">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-gray-500 uppercase tracking-wider">
              Comentarios
            </span>
            <div className="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
              <MessageSquare size={18} />
            </div>
          </div>
          <div className="mt-3">
            <span className="text-3xl font-extrabold text-gray-900">
              {loading ? '...' : (m?.comentarios ?? 0)}
            </span>
            <span className="ml-2 text-xs text-gray-500 font-medium">en total</span>
          </div>
          <div className="mt-3 text-xs text-gray-600 flex items-center gap-1 border-t border-gray-100 pt-2">
            En <strong className="text-gray-800">{m?.categorias ?? 0}</strong> categorías
          </div>
        </div>
      </div>

      {/* Secciones intermedias: Estados y Módulos rápidos */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Distribución de Estados */}
        <div className="bg-white border border-gray-200/80 rounded-2xl p-6 shadow-xs flex flex-col justify-between">
          <div>
            <h3 className="font-bold text-gray-800 text-base">Distribución por Estado</h3>
            <p className="text-xs text-gray-500 mt-0.5">{"Filtrado con where('estado', ...)->count()"}</p>
            
            <div className="mt-6 space-y-4">
              <div>
                <div className="flex justify-between text-xs font-semibold mb-1">
                  <span className="flex items-center gap-1.5 text-emerald-700">
                    <CheckCircle2 size={14} /> Publicados
                  </span>
                  <span className="text-gray-700">{m?.estados.publicado ?? 0}</span>
                </div>
                <div className="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                  <div
                    className="h-full bg-emerald-500 rounded-full"
                    style={{
                      width: `${((m?.estados.publicado ?? 0) / Math.max(m?.publicaciones ?? 1, 1)) * 100}%`,
                    }}
                  />
                </div>
              </div>

              <div>
                <div className="flex justify-between text-xs font-semibold mb-1">
                  <span className="flex items-center gap-1.5 text-amber-700">
                    <Clock size={14} /> Borradores
                  </span>
                  <span className="text-gray-700">{m?.estados.borrador ?? 0}</span>
                </div>
                <div className="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                  <div
                    className="h-full bg-amber-500 rounded-full"
                    style={{
                      width: `${((m?.estados.borrador ?? 0) / Math.max(m?.publicaciones ?? 1, 1)) * 100}%`,
                    }}
                  />
                </div>
              </div>

              <div>
                <div className="flex justify-between text-xs font-semibold mb-1">
                  <span className="flex items-center gap-1.5 text-gray-600">
                    <Archive size={14} /> Archivados
                  </span>
                  <span className="text-gray-700">{m?.estados.archivado ?? 0}</span>
                </div>
                <div className="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                  <div
                    className="h-full bg-gray-400 rounded-full"
                    style={{
                      width: `${((m?.estados.archivado ?? 0) / Math.max(m?.publicaciones ?? 1, 1)) * 100}%`,
                    }}
                  />
                </div>
              </div>
            </div>
          </div>

          <div className="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
            <span>Récord vistas: <strong>{m?.vistas.max ?? 0}</strong></span>
            <span>Mínimo: <strong>{m?.vistas.min ?? 0}</strong></span>
          </div>
        </div>

        {/* Categorías con Posts (withCount) */}
        <div className="bg-white border border-gray-200/80 rounded-2xl p-6 shadow-xs">
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-bold text-gray-800 text-base">Categorías (withCount)</h3>
            <Link to="/dashboard/categorias" className="text-xs text-blue-600 hover:underline font-semibold">
              Ver todas
            </Link>
          </div>
          <div className="space-y-2.5">
            {data?.categorias.slice(0, 5).map((cat) => (
              <div
                key={cat.id}
                className="flex items-center justify-between p-2.5 rounded-xl bg-gray-50/70 border border-gray-100 text-sm"
              >
                <div className="flex items-center gap-2.5">
                  <FolderTree size={16} className="text-blue-500" />
                  <span className="font-medium text-gray-800">{cat.nombre}</span>
                </div>
                <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                  {cat.publicaciones_count} posts
                </span>
              </div>
            ))}
          </div>
        </div>

        {/* Acceso Rápido a Vistas del Sistema */}
        <div className="bg-white border border-gray-200/80 rounded-2xl p-6 shadow-xs">
          <h3 className="font-bold text-gray-800 text-base mb-4">Módulos del Sistema</h3>
          <div className="grid grid-cols-2 gap-3">
            <Link
              to="/dashboard/usuarios"
              className="p-3.5 rounded-xl border border-gray-200/80 hover:border-purple-300 hover:bg-purple-50/40 transition-colors group"
            >
              <Users size={20} className="text-purple-600 mb-1.5 group-hover:scale-110 transition-transform" />
              <p className="font-semibold text-gray-800 text-sm">Usuarios</p>
              <p className="text-xs text-gray-500">Perfiles y roles</p>
            </Link>

            <Link
              to="/dashboard/categorias"
              className="p-3.5 rounded-xl border border-gray-200/80 hover:border-blue-300 hover:bg-blue-50/40 transition-colors group"
            >
              <FolderTree size={20} className="text-blue-600 mb-1.5 group-hover:scale-110 transition-transform" />
              <p className="font-semibold text-gray-800 text-sm">Categorías</p>
              <p className="text-xs text-gray-500">Árbol jerárquico</p>
            </Link>

            <Link
              to="/dashboard/etiquetas"
              className="p-3.5 rounded-xl border border-gray-200/80 hover:border-emerald-300 hover:bg-emerald-50/40 transition-colors group"
            >
              <Tag size={20} className="text-emerald-600 mb-1.5 group-hover:scale-110 transition-transform" />
              <p className="font-semibold text-gray-800 text-sm">Etiquetas</p>
              <p className="text-xs text-gray-500">Relaciones N:M</p>
            </Link>

            <Link
              to="/dashboard/comentarios"
              className="p-3.5 rounded-xl border border-gray-200/80 hover:border-amber-300 hover:bg-amber-50/40 transition-colors group"
            >
              <MessageSquare size={20} className="text-amber-600 mb-1.5 group-hover:scale-110 transition-transform" />
              <p className="font-semibold text-gray-800 text-sm">Comentarios</p>
              <p className="text-xs text-gray-500">16 registrados</p>
            </Link>
          </div>
        </div>
      </div>

      {/* Top 5 Publicaciones más vistas */}
      <div className="bg-white border border-gray-200/80 rounded-2xl shadow-xs overflow-hidden">
        <div className="p-5 border-b border-gray-100 flex items-center justify-between">
          <div className="flex items-center gap-2.5">
            <Flame size={20} className="text-orange-500" />
            <h3 className="font-bold text-gray-900 text-base">Top 5 Publicaciones Más Leídas</h3>
          </div>
          <span className="text-xs text-gray-500 font-medium">
            {"orderBy('vistas', 'DESC')->limit(5)"}
          </span>
        </div>

        <div className="divide-y divide-gray-100">
          {data?.top_publicaciones.map((pub, idx) => (
            <div
              key={pub.id}
              className="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-gray-50/60 transition-colors"
            >
              <div className="flex items-center gap-3">
                <span className="w-7 h-7 rounded-full bg-gray-100 font-bold text-xs flex items-center justify-center text-gray-600">
                  #{idx + 1}
                </span>
                <div>
                  <Link
                    to={`/dashboard/blogs/${pub.slug}`}
                    className="font-semibold text-gray-800 hover:text-blue-600 transition-colors text-sm"
                  >
                    {pub.titulo}
                  </Link>
                  <p className="text-xs text-gray-500 mt-0.5">
                    Por <span className="font-medium text-gray-700">{pub.usuario?.nombre ?? 'Autor'}</span> •{' '}
                    <span className="text-blue-600">{pub.categoria?.nombre ?? 'Sin categoría'}</span>
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-orange-50 text-orange-700 border border-orange-100">
                  <Eye size={13} />
                  {pub.vistas?.toLocaleString() ?? 0} vistas
                </span>
                <span className="px-2.5 py-1 rounded-full text-xs font-medium capitalize bg-gray-100 text-gray-700">
                  {pub.estado}
                </span>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}

export default Dashboard

