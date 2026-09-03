import { useEffect, useState, type FormEvent } from 'react'
import { Link, useNavigate, useParams } from 'react-router'
import { ArrowLeft, Check } from 'lucide-react'
import { createPublicacion, getPublicacion, updatePublicacion } from '../../services/blogService'

const inputClass =
  'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent'

const BlogForm = () => {
  const navigate = useNavigate()
  const { slug } = useParams()
  const isEditing = Boolean(slug)

  const [publicacionId, setPublicacionId] = useState<number | null>(null)
  const [titulo, setTitulo] = useState('')
  const [slugValue, setSlug] = useState('')
  const [contenido, setContenido] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (!slug) return
    const load = async () => {
      try {
        const data = await getPublicacion(slug)
        if (data) {
          setPublicacionId(data.id)
          setTitulo(data.titulo)
          setSlug(data.slug)
          setContenido(data.contenido)
        }
      } catch {
        setError('Error al cargar la publicacion')
      }
    }
    load()
  }, [slug])

  const generateSlug = (value: string) =>
    value
      .toLowerCase()
      .trim()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')

  const handleTituloChange = (value: string) => {
    setTitulo(value)
    if (!isEditing) {
      setSlug(generateSlug(value))
    }
  }

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setError(null)
    try {
      if (isEditing) {
        await updatePublicacion(publicacionId!, { titulo, slug: slugValue, contenido })
      } else {
        await createPublicacion({ titulo, slug: slugValue, contenido })
      }
      navigate('/dashboard/blogs')
    } catch (err: any) {
      const msg = err.response?.data?.message
      setError(Array.isArray(msg) ? msg.join(', ') : (msg ?? 'Error al guardar la publicacion'))
      setLoading(false)
    }
  }

  return (
    <div className="max-w-2xl">
      <Link
        to="/dashboard/blogs"
        className="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-blue-600 transition-colors mb-5"
      >
        <ArrowLeft size={16} />
        Volver al listado
      </Link>

      <h1 className="text-2xl font-bold text-gray-800 mb-6">
        {isEditing ? 'Editar Publicacion' : 'Crear Nueva Publicacion'}
      </h1>

      {error && (
        <div className="mb-4 p-3 rounded-md bg-red-50 text-red-600 text-sm border border-red-200">
          {error}
        </div>
      )}

      <form
        onSubmit={handleSubmit}
        className="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-5"
      >
        <div>
          <label htmlFor="titulo" className="block text-sm font-medium text-gray-700 mb-1">
            Título<span className="text-red-500 ml-0.5">*</span>
          </label>
          <input
            type="text"
            id="titulo"
            value={titulo}
            onChange={(e) => handleTituloChange(e.target.value)}
            placeholder="Ingresa el título de la publicación"
            required
            className={inputClass}
          />
        </div>

        <div>
          <label htmlFor="slug" className="block text-sm font-medium text-gray-700 mb-1">
            Slug<span className="text-red-500 ml-0.5">*</span>
          </label>
          <input
            type="text"
            id="slug"
            value={slugValue}
            onChange={(e) => setSlug(generateSlug(e.target.value))}
            placeholder="se-genera-automaticamente"
            required
            readOnly={isEditing}
            className={`${inputClass} ${isEditing ? 'bg-gray-50 text-gray-500' : ''}`}
          />
          <p className="mt-1 text-xs text-gray-400">
            {isEditing ? 'El slug se genera desde el título' : 'El slug se genera automáticamente desde el título'}
          </p>
        </div>

        <div>
          <label htmlFor="contenido" className="block text-sm font-medium text-gray-700 mb-1">
            Contenido<span className="text-red-500 ml-0.5">*</span>
          </label>
          <textarea
            id="contenido"
            value={contenido}
            onChange={(e) => setContenido(e.target.value)}
            placeholder="Escribe el contenido de tu publicación..."
            required
            rows={8}
            className={`${inputClass} resize-y`}
          ></textarea>
        </div>

        <div className="flex gap-3 pt-2">
          <button
            type="button"
            onClick={() => navigate('/dashboard/blogs')}
            className="px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 rounded-lg text-sm font-medium transition-colors"
          >
            Cancelar
          </button>
          <button
            type="submit"
            disabled={loading}
            className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50"
          >
            <Check size={16} />
            {loading ? 'Guardando...' : isEditing ? 'Actualizar' : 'Guardar'}
          </button>
        </div>
      </form>
    </div>
  )
}

export default BlogForm
