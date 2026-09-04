import { useEffect, useState, useMemo, useCallback } from 'react'
import {
  Users,
  Search,
  UserCheck,
  Shield,
  Phone,
  Calendar,
  Globe,
  MessageSquare,
  BookOpen,
  Info,
  X,
  ExternalLink,
} from 'lucide-react'
import { getUsuarios, type Usuario, type Perfil } from '../../services/usuarioService'

const UsuariosList = () => {
  const [usuarios, setUsuarios] = useState<Usuario[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [search, setSearch] = useState('')
  const [rolFilter, setRolFilter] = useState<string>('todos')
  const [perfilFilter, setPerfilFilter] = useState<string>('todos')
  const [selectedPerfil, setSelectedPerfil] = useState<{ usuario: Usuario; perfil: Perfil } | null>(null)

  const loadData = useCallback(async () => {
    try {
      setLoading(true)
      const params: { rol?: string; con_perfil?: string } = {}
      if (rolFilter !== 'todos') params.rol = rolFilter
      if (perfilFilter === 'con_perfil') params.con_perfil = 'true'
      if (perfilFilter === 'sin_perfil') params.con_perfil = 'false'

      const res = await getUsuarios(params)
      setUsuarios(res)
      setError(null)
    } catch {
      setError('Error al cargar la lista de usuarios')
    } finally {
      setLoading(false)
    }
  }, [rolFilter, perfilFilter])

  useEffect(() => {
    loadData()
  }, [loadData])

  const filtered = useMemo(() => {
    const term = search.toLowerCase().trim()
    if (!term) return usuarios
    return usuarios.filter((u) =>
      [u.nombre, u.correo, u.rol, u.perfil?.telefono, u.invitadoPor?.nombre].some((val) =>
        val?.toLowerCase().includes(term)
      )
    )
  }, [usuarios, search])

  const getRolBadge = (rol: string) => {
    switch (rol) {
      case 'admin':
        return 'bg-purple-100 text-purple-800 border-purple-200'
      case 'editor':
        return 'bg-blue-100 text-blue-800 border-blue-200'
      default:
        return 'bg-gray-100 text-gray-700 border-gray-200'
    }
  }

  return (
    <div className="space-y-6 max-w-7xl">
      {/* Encabezado */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <Users className="text-purple-600" size={26} />
            Usuarios, Perfiles & Roles
          </h1>
          <p className="mt-1 text-sm text-gray-600">
            Demostración visual de relaciones <strong>1:1</strong> (perfiles), <strong>N:M</strong> (roles con pivote) y <strong>Auto-referencial</strong> (invitado por).
          </p>
        </div>
        <div className="flex items-center gap-2 text-xs bg-purple-50 text-purple-700 px-3 py-1.5 rounded-lg border border-purple-200">
          <Info size={15} />
          <span>Eager loading con with('perfil', 'roles', 'invitadoPor')</span>
        </div>
      </div>

      {error && (
        <div className="p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">
          {error}
        </div>
      )}

      {/* Barra de Filtros y Búsqueda */}
      <div className="bg-white border border-gray-200/80 rounded-2xl p-4 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div className="flex flex-wrap items-center gap-3 flex-1 min-w-[280px]">
          <div className="relative flex-1 max-w-xs">
            <Search className="absolute left-3 top-2.5 text-gray-400" size={17} />
            <input
              type="text"
              placeholder="Buscar por nombre, correo o invitador..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500"
            />
          </div>

          <div className="flex items-center gap-1.5 text-xs text-gray-600">
            <span className="font-medium">Rol:</span>
            <select
              value={rolFilter}
              onChange={(e) => setRolFilter(e.target.value)}
              className="px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs bg-white"
            >
              <option value="todos">Todos los roles</option>
              <option value="admin">Admin</option>
              <option value="editor">Editor</option>
              <option value="usuario">Usuario</option>
            </select>
          </div>

          <div className="flex items-center gap-1.5 text-xs text-gray-600">
            <span className="font-medium">Perfil 1:1:</span>
            <select
              value={perfilFilter}
              onChange={(e) => setPerfilFilter(e.target.value)}
              className="px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs bg-white"
            >
              <option value="todos">Todos</option>
              <option value="con_perfil">Con Perfil (1:1)</option>
              <option value="sin_perfil">Sin Perfil</option>
            </select>
          </div>
        </div>

        <span className="text-xs font-semibold text-gray-500">
          Mostrando {filtered.length} de {usuarios.length} usuarios
        </span>
      </div>

      {/* Tabla de Usuarios */}
      <div className="bg-white border border-gray-200/80 rounded-2xl shadow-xs overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-gray-50/80 text-gray-600 text-xs uppercase tracking-wider border-b border-gray-100">
              <tr>
                <th className="px-5 py-3 font-semibold">Usuario</th>
                <th className="px-4 py-3 font-semibold">Rol Base</th>
                <th className="px-4 py-3 font-semibold">Roles (N:M)</th>
                <th className="px-4 py-3 font-semibold">Perfil (1:1)</th>
                <th className="px-4 py-3 font-semibold">Invitado Por</th>
                <th className="px-4 py-3 font-semibold text-center">Posts / Coment.</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {loading ? (
                Array.from({ length: 5 }).map((_, i) => (
                  <tr key={i} className="animate-pulse">
                    <td className="px-5 py-4"><div className="h-4 bg-gray-200 rounded w-32" /></td>
                    <td className="px-4 py-4"><div className="h-4 bg-gray-200 rounded w-16" /></td>
                    <td className="px-4 py-4"><div className="h-4 bg-gray-200 rounded w-24" /></td>
                    <td className="px-4 py-4"><div className="h-4 bg-gray-200 rounded w-20" /></td>
                    <td className="px-4 py-4"><div className="h-4 bg-gray-200 rounded w-20" /></td>
                    <td className="px-4 py-4"><div className="h-4 bg-gray-200 rounded w-12 mx-auto" /></td>
                  </tr>
                ))
              ) : filtered.length === 0 ? (
                <tr>
                  <td colSpan={6} className="text-center py-10 text-gray-500 text-sm">
                    No se encontraron usuarios con los filtros seleccionados.
                  </td>
                </tr>
              ) : (
                filtered.map((u) => (
                  <tr key={u.id} className="hover:bg-gray-50/70 transition-colors">
                    <td className="px-5 py-3.5">
                      <div className="flex items-center gap-3">
                        <div className="w-9 h-9 rounded-full bg-purple-100 text-purple-700 font-bold flex items-center justify-center text-xs">
                          {u.nombre.slice(0, 2).toUpperCase()}
                        </div>
                        <div>
                          <p className="font-semibold text-gray-900 text-sm">{u.nombre}</p>
                          <p className="text-xs text-gray-500">{u.correo}</p>
                        </div>
                      </div>
                    </td>

                    <td className="px-4 py-3.5">
                      <span
                        className={`inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize border ${getRolBadge(
                          u.rol
                        )}`}
                      >
                        {u.rol}
                      </span>
                    </td>

                    <td className="px-4 py-3.5">
                      <div className="flex flex-wrap gap-1">
                        {u.roles && u.roles.length > 0 ? (
                          u.roles.map((r) => (
                            <span
                              key={r.id}
                              className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-100"
                            >
                              <Shield size={10} />
                              {r.nombre}
                            </span>
                          ))
                        ) : (
                          <span className="text-xs text-gray-400 italic">Sin roles extra</span>
                        )}
                      </div>
                    </td>

                    <td className="px-4 py-3.5">
                      {u.perfil ? (
                        <button
                          onClick={() => setSelectedPerfil({ usuario: u, perfil: u.perfil! })}
                          className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors"
                        >
                          <UserCheck size={13} />
                          Ver Perfil
                        </button>
                      ) : (
                        <span className="text-xs text-gray-400 italic">Sin perfil</span>
                      )}
                    </td>

                    <td className="px-4 py-3.5">
                      {u.invitadoPor ? (
                        <span className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                          {u.invitadoPor.nombre}
                        </span>
                      ) : (
                        <span className="text-xs text-gray-400 italic">—</span>
                      )}
                    </td>

                    <td className="px-4 py-3.5 text-center">
                      <div className="flex items-center justify-center gap-3 text-xs text-gray-600">
                        <span className="inline-flex items-center gap-1" title="Publicaciones">
                          <BookOpen size={13} className="text-blue-500" />
                          {u.publicaciones_count ?? 0}
                        </span>
                        <span className="inline-flex items-center gap-1" title="Comentarios">
                          <MessageSquare size={13} className="text-amber-500" />
                          {u.comentarios_count ?? 0}
                        </span>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal Detalle Perfil 1:1 */}
      {selectedPerfil && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-gray-100 animate-in fade-in zoom-in duration-150">
            <div className="flex items-center justify-between pb-4 border-b border-gray-100">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center">
                  {selectedPerfil.usuario.nombre.slice(0, 2).toUpperCase()}
                </div>
                <div>
                  <h3 className="font-bold text-gray-900">{selectedPerfil.usuario.nombre}</h3>
                  <p className="text-xs text-gray-500">Relación 1:1 con tabla perfiles</p>
                </div>
              </div>
              <button
                onClick={() => setSelectedPerfil(null)}
                className="p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100"
              >
                <X size={18} />
              </button>
            </div>

            <div className="mt-4 space-y-3.5 text-sm">
              {selectedPerfil.perfil.biografia && (
                <div>
                  <label className="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">
                    Biografía
                  </label>
                  <p className="text-gray-700 bg-gray-50 p-3 rounded-xl border border-gray-100 text-xs leading-relaxed">
                    {selectedPerfil.perfil.biografia}
                  </p>
                </div>
              )}

              <div className="grid grid-cols-2 gap-3">
                {selectedPerfil.perfil.telefono && (
                  <div className="p-3 rounded-xl bg-gray-50 border border-gray-100">
                    <span className="text-xs text-gray-500 flex items-center gap-1.5 mb-1">
                      <Phone size={13} /> Teléfono
                    </span>
                    <p className="font-semibold text-xs text-gray-800">{selectedPerfil.perfil.telefono}</p>
                  </div>
                )}

                {selectedPerfil.perfil.fecha_nacimiento && (
                  <div className="p-3 rounded-xl bg-gray-50 border border-gray-100">
                    <span className="text-xs text-gray-500 flex items-center gap-1.5 mb-1">
                      <Calendar size={13} /> Nacimiento
                    </span>
                    <p className="font-semibold text-xs text-gray-800">
                      {selectedPerfil.perfil.fecha_nacimiento}
                    </p>
                  </div>
                )}
              </div>

              {selectedPerfil.perfil.sitio_web && (
                <div className="p-3 rounded-xl bg-gray-50 border border-gray-100">
                  <span className="text-xs text-gray-500 flex items-center gap-1.5 mb-1">
                    <Globe size={13} /> Sitio Web
                  </span>
                  <a
                    href={selectedPerfil.perfil.sitio_web}
                    target="_blank"
                    rel="noreferrer"
                    className="font-medium text-xs text-blue-600 hover:underline flex items-center gap-1 truncate"
                  >
                    {selectedPerfil.perfil.sitio_web}
                    <ExternalLink size={11} />
                  </a>
                </div>
              )}
            </div>

            <div className="mt-6 pt-4 border-t border-gray-100 flex justify-end">
              <button
                onClick={() => setSelectedPerfil(null)}
                className="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-xs rounded-xl transition-colors"
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

export default UsuariosList
