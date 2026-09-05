import { useEffect, useState } from 'react'
import {
  Database,
  Code2,
  Terminal,
  Play,
  Clock,
  Layers,
  Check,
  Copy,
  FileCode,
  Table as TableIcon,
  Search,
} from 'lucide-react'
import { getOrmLabQueries, type OrmQueryDemo } from '../../services/ormLabService'

const OrmLab = () => {
  const [consultas, setConsultas] = useState<OrmQueryDemo[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [selectedId, setSelectedId] = useState<string>('')
  const [filterSearch, setFilterSearch] = useState('')
  const [copied, setCopied] = useState(false)
  const [viewMode, setViewMode] = useState<'visual' | 'json'>('visual')

  const loadQueries = async () => {
    try {
      setLoading(true)
      const res = await getOrmLabQueries()
      setConsultas(res.consultas)
      if (res.consultas.length > 0 && !selectedId) {
        setSelectedId(res.consultas[0].id)
      }
      setError(null)
    } catch {
      setError('Error al consultar el laboratorio de ORM del framework')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    loadQueries()
  }, [])

  const selectedQuery = consultas.find((q) => q.id === selectedId) ?? consultas[0]

  const filteredConsultas = consultas.filter((c) =>
    [c.titulo, c.seccion, c.descripcion, c.php].some((val) =>
      val.toLowerCase().includes(filterSearch.toLowerCase().trim())
    )
  )

  const handleCopy = (text: string) => {
    navigator.clipboard.writeText(text)
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  // Renderizador dinámico del resultado en tabla
  const renderVisualResult = (resultado: unknown) => {
    if (!resultado) {
      return (
        <div className="p-8 text-center text-gray-500 text-sm italic">
          null (sin resultados coincidentes en la base de datos)
        </div>
      )
    }

    // Caso 1: Objeto plano (agregados)
    if (typeof resultado === 'object' && !Array.isArray(resultado)) {
      const entries = Object.entries(resultado as Record<string, unknown>)
      return (
        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 p-4">
          {entries.map(([k, v]) => (
            <div key={k} className="p-4 rounded-xl bg-gray-50 border border-gray-200/80">
              <span className="text-[11px] font-bold text-gray-500 uppercase tracking-wider block">
                {k.replace(/_/g, ' ')}
              </span>
              <span className="text-xl font-black text-gray-900 mt-1 block">
                {String(v)}
              </span>
            </div>
          ))}
        </div>
      )
    }

    // Caso 2: Array de objetos (ModelCollection serializada)
    if (Array.isArray(resultado)) {
      if (resultado.length === 0) {
        return (
          <div className="p-8 text-center text-gray-500 text-sm">
            Array vacío: ninguna fila cumplió la condición.
          </div>
        )
      }

      // Obtener todas las columnas primarias del primer objeto (excluyendo sub-arrays complejos en la cabecera principal)
      const firstItem = resultado[0]
      if (typeof firstItem !== 'object' || firstItem === null) {
        return (
          <div className="p-4">
            <pre className="text-xs">{JSON.stringify(resultado, null, 2)}</pre>
          </div>
        )
      }

      const keys = Object.keys(firstItem).filter(
        (k) => !['contenido', 'resumen', 'contrasena'].includes(k)
      )

      return (
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-gray-50/80 text-gray-600 uppercase tracking-wider border-b border-gray-100">
              <tr>
                {keys.map((k) => (
                  <th key={k} className="px-4 py-3 font-semibold">
                    {k.replace(/_/g, ' ')}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {resultado.map((row, idx) => (
                <tr key={idx} className="hover:bg-gray-50/60 transition-colors">
                  {keys.map((k) => {
                    const val = (row as Record<string, unknown>)[k]
                    return (
                      <td key={k} className="px-4 py-2.5 max-w-xs truncate font-mono">
                        {val === null ? (
                          <span className="text-gray-400 italic">null</span>
                        ) : typeof val === 'object' ? (
                          <span className="inline-block px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 text-[10px] font-sans font-medium truncate max-w-[160px]">
                            {Array.isArray(val) ? `[${val.length} items]` : '{relación cargada}'}
                          </span>
                        ) : (
                          String(val)
                        )}
                      </td>
                    )
                  })}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )
    }

    return (
      <div className="p-4">
        <pre className="text-xs">{JSON.stringify(resultado, null, 2)}</pre>
      </div>
    )
  }

  return (
    <div className="space-y-6 max-w-7xl">
      {/* Encabezado */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <Database className="text-blue-600" size={26} />
            Laboratorio de Consultas ORM Cronos
          </h1>
          <p className="mt-1 text-sm text-gray-600">
            Validador visual y ejecutor interactivo de todas las consultas documentadas en <code>Documentation/03-database-and-orm/01-modelos-orm.md</code> contra MySQL real.
          </p>
        </div>
        <button
          onClick={loadQueries}
          disabled={loading}
          className="inline-flex items-center gap-2 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-xs transition-colors disabled:opacity-50"
        >
          <Play size={14} />
          {loading ? 'Ejecutando...' : 'Re-ejecutar Todas'}
        </button>
      </div>

      {error && (
        <div className="p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">
          {error}
        </div>
      )}

      {/* Grid Principal: Selector lateral de consultas + Panel de inspección */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        {/* Selector lateral de consultas (4 cols) */}
        <div className="lg:col-span-4 bg-white border border-gray-200/80 rounded-2xl p-4 shadow-xs space-y-3">
          <div className="relative">
            <Search className="absolute left-3 top-2.5 text-gray-400" size={15} />
            <input
              type="text"
              placeholder="Filtrar consultas..."
              value={filterSearch}
              onChange={(e) => setFilterSearch(e.target.value)}
              className="w-full pl-8 pr-3 py-1.5 border border-gray-300 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
            />
          </div>

          <div className="space-y-1.5 max-h-[640px] overflow-y-auto pr-1">
            {filteredConsultas.map((c) => {
              const isSelected = selectedQuery?.id === c.id
              return (
                <button
                  key={c.id}
                  onClick={() => setSelectedId(c.id)}
                  className={`w-full text-left p-3 rounded-xl border transition-all ${
                    isSelected
                      ? 'bg-blue-50/80 border-blue-300 text-blue-950 shadow-xs'
                      : 'bg-white border-gray-100 hover:border-gray-200 hover:bg-gray-50/70 text-gray-700'
                  }`}
                >
                  <div className="flex items-center justify-between gap-1 mb-1">
                    <span className="text-[10px] font-mono font-bold uppercase tracking-wider text-blue-600 truncate">
                      {c.seccion.split(' ')[0]} {c.seccion.split(' ')[1]}
                    </span>
                    <span className="text-[10px] font-mono font-semibold px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">
                      {c.duracion_ms}ms
                    </span>
                  </div>
                  <p className="text-xs font-bold leading-tight line-clamp-1">{c.titulo}</p>
                  <p className="text-[11px] text-gray-500 mt-1 line-clamp-1">{c.descripcion}</p>
                </button>
              )
            })}
          </div>
        </div>

        {/* Panel de Inspección y Resultados (8 cols) */}
        <div className="lg:col-span-8 space-y-5">
          {selectedQuery ? (
            <>
              {/* Tarjeta de Información de la consulta */}
              <div className="bg-white border border-gray-200/80 rounded-2xl p-5 shadow-xs">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-gray-100">
                  <div>
                    <span className="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-800">
                      {selectedQuery.seccion}
                    </span>
                    <h2 className="text-lg font-bold text-gray-900 mt-1.5">
                      {selectedQuery.titulo}
                    </h2>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className="inline-flex items-center gap-1 text-xs font-mono text-gray-600 bg-gray-100 px-2.5 py-1 rounded-lg">
                      <Clock size={12} className="text-blue-500" />
                      {selectedQuery.duracion_ms} ms
                    </span>
                    <span className="inline-flex items-center gap-1 text-xs font-mono text-gray-600 bg-gray-100 px-2.5 py-1 rounded-lg">
                      <Layers size={12} className="text-emerald-500" />
                      {selectedQuery.filas} filas
                    </span>
                  </div>
                </div>
                <p className="mt-3 text-xs text-gray-600 leading-relaxed">
                  {selectedQuery.descripcion}
                </p>
              </div>

              {/* Bloques de Código: PHP ORM vs SQL Generado */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* Código PHP ORM */}
                <div className="bg-gray-900 text-gray-100 rounded-2xl p-4 shadow-sm border border-gray-800 flex flex-col justify-between">
                  <div>
                    <div className="flex items-center justify-between pb-2 mb-2 border-b border-gray-800 text-xs">
                      <span className="flex items-center gap-1.5 font-mono text-blue-400 font-semibold">
                        <Code2 size={14} /> Sintaxis PHP ORM
                      </span>
                      <button
                        onClick={() => handleCopy(selectedQuery.php)}
                        className="p-1 rounded text-gray-400 hover:text-white transition-colors"
                        title="Copiar código PHP"
                      >
                        {copied ? <Check size={14} className="text-emerald-400" /> : <Copy size={14} />}
                      </button>
                    </div>
                    <pre className="font-mono text-xs text-gray-200 overflow-x-auto whitespace-pre-wrap leading-relaxed">
                      {selectedQuery.php}
                    </pre>
                  </div>
                </div>

                {/* Consulta SQL equivalente */}
                <div className="bg-gray-900 text-gray-100 rounded-2xl p-4 shadow-sm border border-gray-800 flex flex-col justify-between">
                  <div>
                    <div className="flex items-center justify-between pb-2 mb-2 border-b border-gray-800 text-xs">
                      <span className="flex items-center gap-1.5 font-mono text-emerald-400 font-semibold">
                        <Terminal size={14} /> Consulta SQL MySQL
                      </span>
                      <button
                        onClick={() => handleCopy(selectedQuery.sql)}
                        className="p-1 rounded text-gray-400 hover:text-white transition-colors"
                        title="Copiar SQL"
                      >
                        {copied ? <Check size={14} className="text-emerald-400" /> : <Copy size={14} />}
                      </button>
                    </div>
                    <pre className="font-mono text-xs text-emerald-300 overflow-x-auto whitespace-pre-wrap leading-relaxed">
                      {selectedQuery.sql}
                    </pre>
                  </div>
                </div>
              </div>

              {/* Panel de Resultados */}
              <div className="bg-white border border-gray-200/80 rounded-2xl shadow-xs overflow-hidden">
                <div className="p-4 border-b border-gray-100 flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <FileCode size={18} className="text-indigo-600" />
                    <h3 className="font-bold text-gray-900 text-sm">
                      Resultado de la Consulta en MySQL
                    </h3>
                  </div>
                  <div className="p-0.5 rounded-lg bg-gray-100 flex items-center text-xs font-semibold">
                    <button
                      onClick={() => setViewMode('visual')}
                      className={`flex items-center gap-1 px-2.5 py-1 rounded-md transition-colors ${
                        viewMode === 'visual'
                          ? 'bg-white text-gray-900 shadow-xs'
                          : 'text-gray-600 hover:text-gray-900'
                      }`}
                    >
                      <TableIcon size={12} />
                      Tabla
                    </button>
                    <button
                      onClick={() => setViewMode('json')}
                      className={`flex items-center gap-1 px-2.5 py-1 rounded-md transition-colors ${
                        viewMode === 'json'
                          ? 'bg-white text-gray-900 shadow-xs'
                          : 'text-gray-600 hover:text-gray-900'
                      }`}
                    >
                      <Code2 size={12} />
                      JSON
                    </button>
                  </div>
                </div>

                {viewMode === 'visual' ? (
                  renderVisualResult(selectedQuery.resultado)
                ) : (
                  <div className="p-4 bg-gray-950 text-gray-100 max-h-96 overflow-y-auto font-mono text-xs">
                    <pre>{JSON.stringify(selectedQuery.resultado, null, 2)}</pre>
                  </div>
                )}
              </div>
            </>
          ) : (
            <div className="p-12 text-center bg-white rounded-2xl border border-gray-200 text-gray-500 text-sm">
              Selecciona una consulta para inspeccionar.
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

export default OrmLab
