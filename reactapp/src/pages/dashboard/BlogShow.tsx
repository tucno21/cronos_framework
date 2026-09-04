import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router'
import { ArrowLeft, User, Eye, FolderTree, Tag, MessageSquare } from 'lucide-react'
import { getPublicacion, type Publicacion } from '../../services/blogService'

const BlogShow = () => {
  const { slug } = useParams()
  const [blog, setBlog] = useState<Publicacion | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    const load = async () => {
      if (!slug) return
      setLoading(true)
      setError(null)
      try {
        const data = await getPublicacion(slug)
        if (!data) {
          setError('Publicación no encontrada en el sistema')
        } else {
          setBlog(data)
        }
      } catch (err: any) {
        const msg = err.response?.data?.message || err.message || 'Error al cargar la publicación'
        setError(typeof msg === 'string' ? msg : JSON.stringify(msg))
      } finally {
        setLoading(false)
      }
    }
    load()
  }, [slug])

  if (loading) {
    return (
      <div className="animate-pulse space-y-4 max-w-4xl">
        <div className="h-4 bg-gray-200 rounded w-1/4"></div>
        <div className="h-8 bg-gray-200 rounded w-2/3"></div>
        <div className="h-40 bg-gray-200 rounded-xl"></div>
      </div>
    )
  }

  if (error || !blog) {
    return (
      <div className="max-w-4xl space-y-4">
        <Link
          to="/dashboard/blogs"
          className="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-blue-600 transition-colors"
        >
          <ArrowLeft size={16} />
          Volver al listado
        </Link>
        <div className="p-4 rounded-xl bg-red-50 text-red-600 text-sm border border-red-200">
          {error ?? 'Publicación no encontrada'}
        </div>
      </div>
    )
  }

  return (
    <div className="max-w-4xl space-y-6">
      <Link
        to="/dashboard/blogs"
        className="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-blue-600 transition-colors"
      >
        <ArrowLeft size={16} />
        Volver al listado
      </Link>

      <div className="bg-white border border-gray-200/80 rounded-2xl shadow-xs overflow-hidden">
        <div className="px-8 py-6 border-b border-gray-100">
          <div className="flex flex-wrap items-center gap-3 text-xs text-gray-500 mb-3">
            <div className="flex items-center gap-1.5 font-medium text-gray-700">
              <div className="w-6 h-6 rounded-full bg-blue-600 flex items-center justify-center text-[10px] text-white font-bold">
                <User size={12} />
              </div>
              <span>{blog.nombre_autor ?? 'Autor'}</span>
            </div>

            <span>•</span>

            <span className="inline-flex items-center gap-1 font-mono text-gray-600">
              <Eye size={13} className="text-gray-400" />
              {blog.vistas ?? 0} vistas
            </span>

            {blog.categoria && (
              <>
                <span>•</span>
                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                  <FolderTree size={12} />
                  {blog.categoria.nombre}
                </span>
              </>
            )}

            <span className="ml-auto inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize bg-emerald-50 text-emerald-700 border border-emerald-100">
              {blog.estado ?? 'publicado'}
            </span>
          </div>

          <h1 className="text-2xl font-bold text-gray-900 tracking-tight leading-snug">
            {blog.titulo}
          </h1>

          {blog.etiquetas && blog.etiquetas.length > 0 && (
            <div className="flex flex-wrap items-center gap-1.5 mt-3">
              <Tag size={13} className="text-gray-400 mr-1" />
              {blog.etiquetas.map((t) => (
                <span
                  key={t.id}
                  className="px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200"
                >
                  #{t.nombre}
                </span>
              ))}
            </div>
          )}
        </div>

        <div className="px-8 py-6">
          <p className="text-base text-gray-700 leading-relaxed whitespace-pre-line">
            {blog.contenido}
          </p>
        </div>

        {/* Sección de Comentarios */}
        <div className="px-8 py-6 bg-gray-50/60 border-t border-gray-100">
          <div className="flex items-center gap-2 mb-4">
            <MessageSquare size={18} className="text-amber-600" />
            <h3 className="font-bold text-gray-900 text-base">
              Comentarios ({blog.comentarios?.length ?? 0})
            </h3>
          </div>

          <div className="space-y-3">
            {blog.comentarios && blog.comentarios.length > 0 ? (
              blog.comentarios.map((c) => (
                <div key={c.id} className="p-3.5 bg-white rounded-xl border border-gray-200/70 text-xs sm:text-sm">
                  <div className="flex items-center justify-between mb-1.5">
                    <span className="font-bold text-gray-800">
                      {c.usuario?.nombre ?? 'Usuario'}
                    </span>
                    <span className="text-gray-400 text-xs">
                      {c.created_at ? new Date(c.created_at).toLocaleDateString() : ''}
                    </span>
                  </div>
                  <p className="text-gray-600">{c.contenido}</p>
                </div>
              ))
            ) : (
              <p className="text-xs text-gray-400 italic">No hay comentarios en esta publicación aún.</p>
            )}
          </div>
        </div>

        <div className="px-8 py-4 border-t border-gray-100 flex justify-between items-center">
          <Link
            to="/dashboard/blogs"
            className="inline-flex items-center gap-1.5 px-4 py-2 border border-gray-300 rounded-xl text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors"
          >
            <ArrowLeft size={15} />
            Volver a Blogs
          </Link>
          <Link
            to={`/dashboard/blogs/${blog.slug}/editar`}
            className="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold transition-colors"
          >
            Editar Publicación
          </Link>
        </div>
      </div>
    </div>
  )
}

export default BlogShow
