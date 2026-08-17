import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router'
import { ArrowLeft, User } from 'lucide-react'
import { getBlog, type Blog } from '../../services/blogService'

const BlogShow = () => {
  const { slug } = useParams()
  const [blog, setBlog] = useState<Blog | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    const load = async () => {
      if (!slug) return
      setLoading(true)
      try {
        const data = await getBlog(slug)
        if (!data) {
          setError('Blog no encontrado')
        } else {
          setBlog(data)
        }
      } catch {
        setError('Error al cargar el blog')
      } finally {
        setLoading(false)
      }
    }
    load()
  }, [slug])

  if (loading) {
    return (
      <div className="animate-pulse">
        <div className="h-4 bg-gray-200 rounded w-1/4 mb-5"></div>
        <div className="h-8 bg-gray-200 rounded w-2/3 mb-4"></div>
        <div className="h-40 bg-gray-200 rounded"></div>
      </div>
    )
  }

  if (error || !blog) {
    return (
      <div className="p-4 rounded-md bg-red-50 text-red-600 text-sm border border-red-200">
        {error ?? 'Blog no encontrado'}
      </div>
    )
  }

  return (
    <div className="max-w-4xl">
      <Link
        to="/dashboard/blogs"
        className="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-blue-600 transition-colors mb-5"
      >
        <ArrowLeft size={16} />
        Volver al listado
      </Link>

      <div className="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div className="px-8 py-6 border-b border-gray-100">
          <div className="flex items-center gap-2 text-sm text-gray-400 mb-3">
            <div className="w-6 h-6 rounded-full bg-blue-600 flex items-center justify-center text-[0.6875rem] text-white">
              <User size={12} />
            </div>
            <span>
              Por <strong className="text-gray-600">{blog.name ?? 'Autor'}</strong>
            </span>
            <span className="text-gray-200">·</span>
            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[0.6875rem] font-semibold bg-blue-100 text-blue-700">
              Publicado
            </span>
          </div>
          <h1 className="text-2xl font-bold text-gray-800 tracking-tight leading-snug">
            {blog.title}
          </h1>
        </div>

        <div className="px-8 py-6">
          <p className="text-base text-gray-600 leading-relaxed whitespace-pre-line">
            {blog.content}
          </p>
        </div>

        <div className="px-8 py-4 border-t border-gray-100 flex gap-3">
          <Link to="/dashboard/blogs" className="btn-secondary">
            <ArrowLeft size={16} />
            Volver
          </Link>
        </div>
      </div>
    </div>
  )
}

export default BlogShow
