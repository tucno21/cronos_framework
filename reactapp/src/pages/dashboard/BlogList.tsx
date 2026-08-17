import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router'
import { deleteBlog, getBlogs, type Blog } from '../../services/blogService'

const BlogList = () => {
  const [blogs, setBlogs] = useState<Blog[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [search, setSearch] = useState('')
  const [rowsPerPage, setRowsPerPage] = useState(10)
  const [page, setPage] = useState(1)

  const loadBlogs = useCallback(async () => {
    setLoading(true)
    try {
      const data = await getBlogs()
      setBlogs(data)
      setError(null)
    } catch {
      setError('Error al cargar los blogs')
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    loadBlogs()
  }, [loadBlogs])

  const filtered = useMemo(() => {
    const term = search.toLowerCase()
    if (!term) return blogs
    return blogs.filter((b) =>
      [b.title, b.slug, b.name].some((v) => v?.toLowerCase().includes(term)),
    )
  }, [blogs, search])

  const totalPages = Math.ceil(filtered.length / rowsPerPage) || 1
  const currentPage = Math.min(page, totalPages)
  const visible = filtered.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage)

  const handleDelete = async (id: number) => {
    if (!confirm('¿Seguro que deseas eliminar este blog?')) return
    try {
      await deleteBlog(id)
      loadBlogs()
    } catch {
      setError('Error al eliminar el blog')
    }
  }

  const headers = ['title', 'slug', 'name']

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">Gestión de Blogs</h1>
          <p className="mt-1 text-gray-600">Administra todos tus blogs y artículos</p>
        </div>
        <Link
          to="/dashboard/blogs/crear"
          className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
        >
          <i className="bi bi-plus-lg"></i>
          Crear Blog
        </Link>
      </div>

      {error && (
        <div className="mb-4 p-3 rounded-md bg-red-50 text-red-600 text-sm border border-red-200">
          {error}
        </div>
      )}

      <div className="bg-white border border-gray-200 rounded-xl shadow-sm">
        <div className="flex flex-wrap items-center gap-3 p-4 border-b border-gray-100">
          <select
            value={rowsPerPage}
            onChange={(e) => {
              setRowsPerPage(Number(e.target.value))
              setPage(1)
            }}
            className="px-3 py-2 border border-gray-300 rounded-md text-sm"
          >
            {[10, 25, 50].map((n) => (
              <option key={n} value={n}>
                {n} filas
              </option>
            ))}
          </select>
          <input
            type="text"
            value={search}
            onChange={(e) => {
              setSearch(e.target.value)
              setPage(1)
            }}
            placeholder="Buscar..."
            className="px-3 py-2 border border-gray-300 rounded-md text-sm flex-1 max-w-xs"
          />
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-gray-50 text-left text-gray-600">
                {headers.map((h) => (
                  <th key={h} className="px-4 py-3 font-medium capitalize">
                    {h}
                  </th>
                ))}
                <th className="px-4 py-3 font-medium">Acciones</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                Array.from({ length: 5 }).map((_, i) => (
                  <tr key={i}>
                    <td colSpan={4} className="px-4 py-3">
                      <div className="animate-pulse h-4 bg-gray-200 rounded w-3/4"></div>
                    </td>
                  </tr>
                ))
              ) : visible.length === 0 ? (
                <tr>
                  <td colSpan={4} className="px-4 py-10 text-center text-gray-400">
                    {search ? `Sin resultados para "${search}"` : 'No hay datos disponibles'}
                  </td>
                </tr>
              ) : (
                visible.map((blog) => (
                  <tr key={blog.id} className="border-t border-gray-100 hover:bg-gray-50">
                    <td className="px-4 py-3 text-gray-800">{blog.title}</td>
                    <td className="px-4 py-3 text-gray-500">{blog.slug}</td>
                    <td className="px-4 py-3 text-gray-500">{blog.name ?? '—'}</td>
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-2">
                        <Link
                          to={`/dashboard/blogs/${blog.slug}`}
                          title="Ver post"
                          className="p-1.5 rounded text-gray-500 hover:bg-gray-100 hover:text-blue-600"
                        >
                          <i className="bi bi-file-earmark-text"></i>
                        </Link>
                        <Link
                          to={`/dashboard/blogs/${blog.id}/editar`}
                          title="Editar post"
                          className="p-1.5 rounded text-gray-500 hover:bg-gray-100 hover:text-blue-600"
                        >
                          <i className="bi bi-pencil"></i>
                        </Link>
                        <button
                          onClick={() => handleDelete(blog.id)}
                          title="Eliminar post"
                          className="p-1.5 rounded text-gray-500 hover:bg-gray-100 hover:text-red-600"
                        >
                          <i className="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {!loading && filtered.length > 0 && (
          <div className="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-t border-gray-100">
            <span className="text-sm text-gray-500">
              Mostrando {(currentPage - 1) * rowsPerPage + 1}–
              {Math.min(currentPage * rowsPerPage, filtered.length)} de {filtered.length}
            </span>
            <div className="flex items-center gap-1">
              <button
                disabled={currentPage === 1}
                onClick={() => setPage(currentPage - 1)}
                className="px-2 py-1 rounded border border-gray-300 text-gray-600 disabled:opacity-40"
              >
                ‹
              </button>
              {Array.from({ length: totalPages }, (_, i) => i + 1).map((n) => (
                <button
                  key={n}
                  disabled={n === currentPage}
                  onClick={() => setPage(n)}
                  className={`px-2.5 py-1 rounded text-sm ${
                    n === currentPage
                      ? 'bg-blue-600 text-white'
                      : 'border border-gray-300 text-gray-600 hover:bg-gray-100'
                  }`}
                >
                  {n}
                </button>
              ))}
              <button
                disabled={currentPage === totalPages}
                onClick={() => setPage(currentPage + 1)}
                className="px-2 py-1 rounded border border-gray-300 text-gray-600 disabled:opacity-40"
              >
                ›
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}

export default BlogList
