import { useEffect, useState, useMemo } from 'react'
import { Link } from 'react-router'
import {
  MessageSquare,
  Search,
  Trash2,
  BookOpen,
  User,
  Info,
} from 'lucide-react'
import { getComentarios, deleteComentario, type Comentario } from '../../services/comentarioService'

const ComentariosList = () => {
  const [comentarios, setComentarios] = useState<Comentario[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [search, setSearch] = useState('')
  const [deletingId, setDeletingId] = useState<number | null>(null)

  const loadData = async () => {
    try {
      setLoading(true)
      const data = await getComentarios()
      setComentarios(data)
      setError(null)
    } catch {
      setError('Error al cargar la lista de comentarios')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    loadData()
  }, [])

  const filtered = useMemo(() => {
    const term = search.toLowerCase().trim()
    if (!term) return comentarios
    return comentarios.filter((c) =>
      [c.contenido, c.usuario?.nombre, c.publicacion?.titulo].some((v) =>
        v?.toLowerCase().includes(term)
      )
    )
  }, [comentarios, search])

  const handleDelete = async (id: number) => {
    if (!confirm('¿Seguro que deseas eliminar este comentario?')) return
    try {
      setDeletingId(id)
      await deleteComentario(id)
      setComentarios((prev) => prev.filter((c) => c.id !== id))
    } catch {
      setError('No se pudo eliminar el comentario')
    } finally {
      setDeletingId(null)
    }
  }

  return (
    <div className="space-y-6 max-w-7xl">
      {/* Encabezado */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <MessageSquare className="text-amber-600" size={26} />
            Gestión de Comentarios
          </h1>
          <p className="mt-1 text-sm text-gray-600">
            Relación 1:N doble: pertenece a un autor (<code>Usuario</code>) y a una <code>Publicacion</code> cargados con Eager Loading.
          </p>
        </div>
        <div className="flex items-center gap-2 text-xs bg-amber-50 text-amber-800 px-3 py-1.5 rounded-lg border border-amber-200">
          <Info size={15} />
          <span>{"Comentario::with('usuario', 'publicacion')->get()"}</span>
        </div>
      </div>

      {error && (
        <div className="p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">
          {error}
        </div>
      )}

      {/* Barra de Filtros */}
      <div className="bg-white border border-gray-200/80 rounded-2xl p-4 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-2.5 text-gray-400" size={17} />
          <input
            type="text"
            placeholder="Buscar por contenido, autor o publicación..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
          />
        </div>
        <span className="text-xs font-semibold text-gray-500">
          Mostrando {filtered.length} de {comentarios.length} comentarios
        </span>
      </div>

      {/* Lista de comentarios */}
      <div className="bg-white border border-gray-200/80 rounded-2xl shadow-xs overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-gray-50/80 text-gray-600 text-xs uppercase tracking-wider border-b border-gray-100">
              <tr>
                <th className="px-5 py-3 font-semibold">Autor</th>
                <th className="px-4 py-3 font-semibold">Comentario</th>
                <th className="px-4 py-3 font-semibold">Publicación Asociada</th>
                <th className="px-4 py-3 font-semibold">Fecha</th>
                <th className="px-4 py-3 font-semibold text-center">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {loading ? (
                Array.from({ length: 5 }).map((_, i) => (
                  <tr key={i} className="animate-pulse">
                    <td className="px-5 py-4"><div className="h-4 bg-gray-200 rounded w-28" /></td>
                    <td className="px-4 py-4"><div className="h-4 bg-gray-200 rounded w-60" /></td>
                    <td className="px-4 py-4"><div className="h-4 bg-gray-200 rounded w-36" /></td>
                    <td className="px-4 py-4"><div className="h-4 bg-gray-200 rounded w-20" /></td>
                    <td className="px-4 py-4"><div className="h-4 bg-gray-200 rounded w-8 mx-auto" /></td>
                  </tr>
                ))
              ) : filtered.length === 0 ? (
                <tr>
                  <td colSpan={5} className="text-center py-10 text-gray-500 text-sm">
                    No se encontraron comentarios.
                  </td>
                </tr>
              ) : (
                filtered.map((com) => (
                  <tr key={com.id} className="hover:bg-gray-50/70 transition-colors">
                    <td className="px-5 py-3.5">
                      <div className="flex items-center gap-2.5">
                        <div className="w-8 h-8 rounded-full bg-amber-100 text-amber-700 font-bold flex items-center justify-center text-xs">
                          {com.usuario?.nombre ? com.usuario.nombre.slice(0, 2).toUpperCase() : <User size={13} />}
                        </div>
                        <div>
                          <p className="font-semibold text-gray-900 text-xs sm:text-sm">
                            {com.usuario?.nombre ?? 'Anónimo'}
                          </p>
                          <p className="text-[11px] text-gray-400 font-mono">ID #{com.usuario_id}</p>
                        </div>
                      </div>
                    </td>

                    <td className="px-4 py-3.5">
                      <p className="text-gray-800 text-xs sm:text-sm max-w-md line-clamp-2">
                        "{com.contenido}"
                      </p>
                    </td>

                    <td className="px-4 py-3.5">
                      {com.publicacion ? (
                        <Link
                          to={`/dashboard/blogs/${com.publicacion.slug}`}
                          className="inline-flex items-center gap-1.5 font-medium text-xs text-blue-600 hover:underline max-w-xs truncate"
                        >
                          <BookOpen size={12} className="shrink-0" />
                          <span className="truncate">{com.publicacion.titulo}</span>
                        </Link>
                      ) : (
                        <span className="text-xs text-gray-400 italic">Publicación eliminada</span>
                      )}
                    </td>

                    <td className="px-4 py-3.5 text-xs text-gray-500 whitespace-nowrap">
                      {com.created_at ? new Date(com.created_at).toLocaleDateString() : '—'}
                    </td>

                    <td className="px-4 py-3.5 text-center">
                      <button
                        onClick={() => handleDelete(com.id)}
                        disabled={deletingId === com.id}
                        className="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors disabled:opacity-50"
                        title="Eliminar comentario"
                      >
                        <Trash2 size={15} />
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

export default ComentariosList
