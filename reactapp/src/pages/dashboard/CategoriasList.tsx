import { useEffect, useState, useMemo } from 'react'
import {
  FolderTree,
  Folder,
  ChevronRight,
  BookOpen,
  Layers,
  Search,
  Info,
} from 'lucide-react'
import { getCategorias, getCategoriasArbol, type Categoria } from '../../services/categoriaService'

const CategoriasList = () => {
  const [categorias, setCategorias] = useState<Categoria[]>([])
  const [arbol, setArbol] = useState<Categoria[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [search, setSearch] = useState('')
  const [vistaModo, setVistaModo] = useState<'arbol' | 'tabla'>('arbol')

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true)
        const [lista, tree] = await Promise.all([getCategorias(), getCategoriasArbol()])
        setCategorias(lista)
        setArbol(tree)
        setError(null)
      } catch {
        setError('Error al cargar las categorías')
      } finally {
        setLoading(false)
      }
    }
    fetchData()
  }, [])

  const filtered = useMemo(() => {
    const term = search.toLowerCase().trim()
    if (!term) return categorias
    return categorias.filter((c) =>
      [c.nombre, c.slug, c.descripcion, c.categoriaPadre?.nombre].some((v) =>
        v?.toLowerCase().includes(term)
      )
    )
  }, [categorias, search])

  const totalPublicacionesCategorias = useMemo(() => {
    return categorias.reduce((sum, c) => sum + (c.publicaciones_count ?? 0), 0)
  }, [categorias])

  return (
    <div className="space-y-6 max-w-7xl">
      {/* Encabezado */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <FolderTree className="text-blue-600" size={26} />
            Categorías & Jerarquía
          </h1>
          <p className="mt-1 text-sm text-gray-600">
            Estructura jerárquica auto-referencial (padres e hijas) mediante <code>categoriaPadre()</code> y <code>subcategorias()</code> con <code>withCount('publicaciones')</code>.
          </p>
        </div>
        <div className="flex items-center gap-2 text-xs bg-blue-50 text-blue-700 px-3 py-1.5 rounded-lg border border-blue-200">
          <Info size={15} />
          <span>Relación 1:N auto-referencial</span>
        </div>
      </div>

      {error && (
        <div className="p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">
          {error}
        </div>
      )}

      {/* Métricas rápidas */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div className="p-4 bg-white border border-gray-200/80 rounded-2xl shadow-xs flex items-center justify-between">
          <div>
            <p className="text-xs font-semibold text-gray-500 uppercase">Total Categorías</p>
            <p className="text-2xl font-black text-gray-900 mt-1">{categorias.length}</p>
          </div>
          <div className="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
            <FolderTree size={20} />
          </div>
        </div>

        <div className="p-4 bg-white border border-gray-200/80 rounded-2xl shadow-xs flex items-center justify-between">
          <div>
            <p className="text-xs font-semibold text-gray-500 uppercase">Categorías Raíz</p>
            <p className="text-2xl font-black text-gray-900 mt-1">{arbol.length}</p>
          </div>
          <div className="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
            <Layers size={20} />
          </div>
        </div>

        <div className="p-4 bg-white border border-gray-200/80 rounded-2xl shadow-xs flex items-center justify-between">
          <div>
            <p className="text-xs font-semibold text-gray-500 uppercase">Posts Asignados</p>
            <p className="text-2xl font-black text-gray-900 mt-1">{totalPublicacionesCategorias}</p>
          </div>
          <div className="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
            <BookOpen size={20} />
          </div>
        </div>
      </div>

      {/* Controles de vista */}
      <div className="bg-white border border-gray-200/80 rounded-2xl p-4 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div className="relative flex-1 max-w-xs">
          <Search className="absolute left-3 top-2.5 text-gray-400" size={17} />
          <input
            type="text"
            placeholder="Buscar categoría..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
          />
        </div>

        <div className="flex items-center gap-2">
          <div className="p-1 rounded-xl bg-gray-100 flex items-center gap-1 text-xs font-semibold">
            <button
              onClick={() => setVistaModo('arbol')}
              className={`px-3 py-1.5 rounded-lg transition-colors ${
                vistaModo === 'arbol'
                  ? 'bg-white text-gray-900 shadow-xs'
                  : 'text-gray-600 hover:text-gray-900'
              }`}
            >
              Vista de Árbol
            </button>
            <button
              onClick={() => setVistaModo('tabla')}
              className={`px-3 py-1.5 rounded-lg transition-colors ${
                vistaModo === 'tabla'
                  ? 'bg-white text-gray-900 shadow-xs'
                  : 'text-gray-600 hover:text-gray-900'
              }`}
            >
              Tabla Detallada
            </button>
          </div>
        </div>
      </div>

      {/* Vista de Árbol */}
      {vistaModo === 'arbol' && (
        <div className="space-y-4">
          {loading ? (
            <div className="p-8 text-center bg-white rounded-2xl border border-gray-200 text-sm text-gray-500">
              Cargando árbol de categorías...
            </div>
          ) : arbol.length === 0 ? (
            <div className="p-8 text-center bg-white rounded-2xl border border-gray-200 text-sm text-gray-500">
              No hay categorías raíz.
            </div>
          ) : (
            arbol.map((raiz) => (
              <div
                key={raiz.id}
                className="bg-white border border-gray-200/80 rounded-2xl p-5 shadow-xs transition-shadow hover:shadow-sm"
              >
                {/* Categoría Raíz */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-gray-100">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
                      <Folder size={20} />
                    </div>
                    <div>
                      <h3 className="font-bold text-gray-900 text-base">{raiz.nombre}</h3>
                      <p className="text-xs text-gray-500">{raiz.descripcion ?? 'Sin descripción'}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="px-2.5 py-1 rounded-md text-xs font-mono bg-gray-100 text-gray-700">
                      slug: {raiz.slug}
                    </span>
                    <span className="px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">
                      {raiz.publicaciones_count ?? 0} artículos
                    </span>
                  </div>
                </div>

                {/* Subcategorías Nivel 1 y Nivel 2 */}
                {raiz.subcategorias && raiz.subcategorias.length > 0 ? (
                  <div className="mt-4 pl-4 border-l-2 border-blue-200 space-y-3">
                    {raiz.subcategorias.map((hija) => (
                      <div
                        key={hija.id}
                        className="p-3.5 rounded-xl bg-gray-50/80 border border-gray-200/60"
                      >
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                          <div className="flex items-center gap-2">
                            <ChevronRight size={16} className="text-blue-500 shrink-0" />
                            <div>
                              <span className="font-semibold text-gray-800 text-sm">{hija.nombre}</span>
                              <span className="text-xs text-gray-500 ml-2 font-mono">({hija.slug})</span>
                              {hija.descripcion && (
                                <p className="text-xs text-gray-500 mt-0.5">{hija.descripcion}</p>
                              )}
                            </div>
                          </div>
                          <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100 self-start sm:self-auto">
                            {hija.publicaciones_count ?? 0} artículos
                          </span>
                        </div>

                        {/* Sub-subcategorías si existen (ej. Frameworks -> PHP) */}
                        {hija.subcategorias && hija.subcategorias.length > 0 && (
                          <div className="mt-2.5 pl-4 border-l-2 border-emerald-200 space-y-2">
                            {hija.subcategorias.map((nieta) => (
                              <div
                                key={nieta.id}
                                className="flex items-center justify-between p-2 rounded-lg bg-white border border-gray-200/50 text-xs"
                              >
                                <div className="flex items-center gap-1.5">
                                  <ChevronRight size={13} className="text-emerald-500" />
                                  <span className="font-medium text-gray-800">{nieta.nombre}</span>
                                  <span className="text-gray-400 font-mono">({nieta.slug})</span>
                                </div>
                                <span className="px-2 py-0.5 rounded-full font-bold bg-gray-100 text-gray-700">
                                  {nieta.publicaciones_count ?? 0} posts
                                </span>
                              </div>
                            ))}
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="mt-3 text-xs text-gray-400 italic">
                    Esta categoría no tiene subcategorías hijas.
                  </p>
                )}
              </div>
            ))
          )}
        </div>
      )}

      {/* Vista de Tabla */}
      {vistaModo === 'tabla' && (
        <div className="bg-white border border-gray-200/80 rounded-2xl shadow-xs overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="bg-gray-50/80 text-gray-600 text-xs uppercase tracking-wider border-b border-gray-100">
                <tr>
                  <th className="px-5 py-3 font-semibold">ID</th>
                  <th className="px-4 py-3 font-semibold">Nombre</th>
                  <th className="px-4 py-3 font-semibold">Slug</th>
                  <th className="px-4 py-3 font-semibold">Categoría Padre</th>
                  <th className="px-4 py-3 font-semibold">Descripción</th>
                  <th className="px-4 py-3 font-semibold text-center">Publicaciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {filtered.map((cat) => (
                  <tr key={cat.id} className="hover:bg-gray-50/70 transition-colors">
                    <td className="px-5 py-3.5 text-xs font-mono text-gray-500">#{cat.id}</td>
                    <td className="px-4 py-3.5 font-semibold text-gray-900">{cat.nombre}</td>
                    <td className="px-4 py-3.5 font-mono text-xs text-blue-600">{cat.slug}</td>
                    <td className="px-4 py-3.5">
                      {cat.categoriaPadre ? (
                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                          {cat.categoriaPadre.nombre}
                        </span>
                      ) : (
                        <span className="text-xs text-gray-400 italic">Raíz (sin padre)</span>
                      )}
                    </td>
                    <td className="px-4 py-3.5 text-xs text-gray-600 max-w-xs truncate">
                      {cat.descripcion ?? '—'}
                    </td>
                    <td className="px-4 py-3.5 text-center">
                      <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <BookOpen size={12} />
                        {cat.publicaciones_count ?? 0}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  )
}

export default CategoriasList
