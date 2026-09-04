import { useEffect, useState } from 'react'
import { Link } from 'react-router'
import { Tag, BookOpen, Eye, Info, ArrowRight } from 'lucide-react'
import { getEtiquetas, type Etiqueta } from '../../services/etiquetaService'

const EtiquetasList = () => {
  const [etiquetas, setEtiquetas] = useState<Etiqueta[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [selectedTag, setSelectedTag] = useState<Etiqueta | null>(null)

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true)
        const data = await getEtiquetas()
        setEtiquetas(data)
        if (data.length > 0) setSelectedTag(data[0])
        setError(null)
      } catch {
        setError('Error al cargar las etiquetas')
      } finally {
        setLoading(false)
      }
    }
    fetchData()
  }, [])

  return (
    <div className="space-y-6 max-w-7xl">
      {/* Encabezado */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <Tag className="text-emerald-600" size={26} />
            Etiquetas & Relación N:M
          </h1>
          <p className="mt-1 text-sm text-gray-600">
            Relación Many-to-Many entre publicaciones y etiquetas gestionada mediante la tabla intermedia <code>publicacion_etiqueta</code>.
          </p>
        </div>
        <div className="flex items-center gap-2 text-xs bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-lg border border-emerald-200">
          <Info size={15} />
          <span>belongsToMany(Etiqueta::class, 'publicacion_etiqueta')</span>
        </div>
      </div>

      {error && (
        <div className="p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">
          {error}
        </div>
      )}

      {/* Grid de Etiquetas */}
      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        {loading ? (
          Array.from({ length: 6 }).map((_, i) => (
            <div key={i} className="h-20 bg-white rounded-2xl border border-gray-200 animate-pulse" />
          ))
        ) : (
          etiquetas.map((tag) => {
            const isSelected = selectedTag?.id === tag.id
            return (
              <button
                key={tag.id}
                onClick={() => setSelectedTag(tag)}
                className={`p-4 rounded-2xl border text-left transition-all flex flex-col justify-between ${
                  isSelected
                    ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm scale-102'
                    : 'bg-white border-gray-200 hover:border-emerald-300 hover:bg-emerald-50/40 text-gray-800'
                }`}
              >
                <div className="flex items-center justify-between">
                  <Tag size={16} className={isSelected ? 'text-emerald-200' : 'text-emerald-500'} />
                  <span
                    className={`text-xs px-2 py-0.5 rounded-full font-bold ${
                      isSelected ? 'bg-emerald-700 text-white' : 'bg-gray-100 text-gray-600'
                    }`}
                  >
                    {tag.publicaciones_count ?? 0}
                  </span>
                </div>
                <div className="mt-3">
                  <p className="font-bold text-sm tracking-tight">{tag.nombre}</p>
                  <p className={`text-[11px] font-mono ${isSelected ? 'text-emerald-200' : 'text-gray-400'}`}>
                    #{tag.slug}
                  </p>
                </div>
              </button>
            )
          })
        )}
      </div>

      {/* Detalle de publicaciones asociadas a la etiqueta seleccionada */}
      {selectedTag && (
        <div className="bg-white border border-gray-200/80 rounded-2xl shadow-xs overflow-hidden">
          <div className="p-5 border-b border-gray-100 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <BookOpen size={20} className="text-emerald-600" />
              <h3 className="font-bold text-gray-900 text-base">
                Publicaciones etiquetadas con "{selectedTag.nombre}"
              </h3>
            </div>
            <span className="text-xs font-semibold px-3 py-1 rounded-full bg-emerald-100 text-emerald-800">
              {selectedTag.publicaciones?.length ?? 0} artículos asociados
            </span>
          </div>

          <div className="divide-y divide-gray-100">
            {selectedTag.publicaciones && selectedTag.publicaciones.length > 0 ? (
              selectedTag.publicaciones.map((pub) => (
                <div
                  key={pub.id}
                  className="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-gray-50/70 transition-colors"
                >
                  <div>
                    <Link
                      to={`/dashboard/blogs/${pub.slug}`}
                      className="font-semibold text-gray-900 hover:text-emerald-600 transition-colors text-sm"
                    >
                      {pub.titulo}
                    </Link>
                    <p className="text-xs text-gray-500 mt-0.5 font-mono">slug: {pub.slug}</p>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className="inline-flex items-center gap-1 text-xs font-medium text-gray-600 bg-gray-100 px-2.5 py-1 rounded-lg">
                      <Eye size={13} className="text-gray-400" />
                      {pub.vistas ?? 0} vistas
                    </span>
                    <span className="px-2.5 py-1 rounded-full text-xs font-medium capitalize bg-emerald-50 text-emerald-700 border border-emerald-100">
                      {pub.estado ?? 'publicado'}
                    </span>
                    <Link
                      to={`/dashboard/blogs/${pub.slug}`}
                      className="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
                      title="Ver publicación"
                    >
                      <ArrowRight size={16} />
                    </Link>
                  </div>
                </div>
              ))
            ) : (
              <div className="p-8 text-center text-gray-500 text-sm">
                No hay publicaciones asociadas a esta etiqueta en este momento.
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  )
}

export default EtiquetasList
