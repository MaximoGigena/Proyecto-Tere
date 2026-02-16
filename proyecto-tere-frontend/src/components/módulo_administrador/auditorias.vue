<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Auditorías del sistema</h1>

    <!-- Panel de estadísticas -->
    <div v-if="estadisticas" class="bg-white p-4 rounded-xl shadow">
      <h2 class="text-lg font-semibold mb-3">Resumen de auditorías</h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 p-4 rounded-lg">
          <p class="text-sm text-blue-600">Total registros</p>
          <p class="text-2xl font-bold">{{ estadisticas.total_registros }}</p>
        </div>
        <div class="bg-green-50 p-4 rounded-lg" v-for="accion in estadisticas.por_accion" :key="accion.action">
          <p class="text-sm text-green-600">{{ accion.action }}</p>
          <p class="text-2xl font-bold">{{ accion.total }}</p>
        </div>
      </div>
    </div>

    <!-- Filtros mejorados -->
    <div class="bg-white p-4 rounded-xl shadow">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">ID Auditoría:</label>
          <input v-model="filtros.id" type="text" placeholder="Ej: 101" 
                 class="w-full border border-gray-300 rounded px-3 py-2">
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Usuario:</label>
          <input v-model="filtros.usuario" type="text" placeholder="Nombre o email" 
                 class="w-full border border-gray-300 rounded px-3 py-2">
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tabla:</label>
          <select v-model="filtros.tabla" class="w-full border border-gray-300 rounded px-3 py-2">
            <option value="">Todas las tablas</option>
            <option v-for="tabla in tablasUnicas" :key="tabla" :value="tabla">{{ tabla }}</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Acción:</label>
          <select v-model="filtros.accion" class="w-full border border-gray-300 rounded px-3 py-2">
            <option value="">Todas las acciones</option>
            <option value="INSERT">INSERT</option>
            <option value="UPDATE">UPDATE</option>
            <option value="DELETE">DELETE</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fecha desde:</label>
          <input v-model="filtros.fecha_desde" type="date" 
                 class="w-full border border-gray-300 rounded px-3 py-2">
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fecha hasta:</label>
          <input v-model="filtros.fecha_hasta" type="date" 
                 class="w-full border border-gray-300 rounded px-3 py-2">
        </div>
      </div>
      
      <div class="flex flex-wrap gap-2">
        <button @click="cargarAuditorias" 
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded shadow">
          <i class="fas fa-search mr-2"></i>Buscar
        </button>
        
        <button @click="limpiarFiltros" 
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded shadow">
          <i class="fas fa-times mr-2"></i>Limpiar filtros
        </button>
        
        <button @click="exportarCSV" 
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded shadow">
          <i class="fas fa-file-export mr-2"></i>Exportar CSV
        </button>
        
        <button @click="descargarPDF" 
                class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded shadow">
          <i class="fas fa-file-pdf mr-2"></i>Exportar PDF
        </button>
      </div>
    </div>

    <!-- Tabla con datos reales -->
    <div class="bg-white rounded-xl shadow overflow-x-auto">
      <div class="p-4 border-b flex justify-between items-center">
        <p class="text-sm text-gray-600">
          Mostrando {{ auditorias.length }} de {{ pagination.total }} registros
        </p>
        <div class="flex items-center space-x-2">
          <select v-model="itemsPorPagina" @change="cambiarItemsPorPagina" 
                  class="border border-gray-300 rounded px-2 py-1">
            <option value="10">10 por página</option>
            <option value="20">20 por página</option>
            <option value="50">50 por página</option>
            <option value="100">100 por página</option>
          </select>
        </div>
      </div>
      
      <table class="min-w-full text-sm text-left">
        <thead class="bg-gray-100 text-gray-700 font-semibold">
          <tr>
            <th class="px-6 py-3">ID</th>
            <th class="px-6 py-3">Tabla</th>
            <th class="px-6 py-3">Registro ID</th>
            <th class="px-6 py-3">Acción</th>
            <th class="px-6 py-3">Usuario</th>
            <th class="px-6 py-3">IP</th>
            <th class="px-6 py-3">Fecha</th>
            <th class="px-6 py-3">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="audit in auditorias" :key="audit.id" 
              class="border-t hover:bg-gray-50 transition-colors">
            <td class="px-6 py-3 font-mono text-sm">{{ audit.id }}</td>
            <td class="px-6 py-3">
              <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded">
                {{ audit.table_name }}
              </span>
            </td>
            <td class="px-6 py-3 font-mono">{{ audit.record_id }}</td>
            <td class="px-6 py-3">
              <span :class="getBadgeClass(audit.action)" class="px-2 py-1 rounded text-xs font-medium">
                {{ audit.action }}
              </span>
            </td>
            <td class="px-6 py-3">
              <div v-if="audit.user">
                <p class="font-medium">{{ audit.user.name }}</p>
                <p class="text-xs text-gray-500">{{ audit.user.email }}</p>
              </div>
              <span v-else class="text-gray-400 italic">Sistema</span>
            </td>
            <td class="px-6 py-3 font-mono text-xs">{{ audit.ip_address || '-' }}</td>
            <td class="px-6 py-3">
              {{ formatFecha(audit.created_at) }}
            </td>
            <td class="px-6 py-3">
              <div class="flex space-x-2">
                <button @click="verDetalles(audit)" 
                        class="text-blue-600 hover:text-blue-800">
                  <i class="fas fa-eye"></i>
                </button>
                <button @click="descargarAuditoriaPDF(audit)" 
                        class="text-red-600 hover:text-red-800">
                  <i class="fas fa-file-pdf"></i>
                </button>
              </div>
            </td>
          </tr>
          
          <tr v-if="cargando">
            <td colspan="8" class="text-center py-8">
              <div class="flex justify-center items-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                <span class="ml-2">Cargando auditorías...</span>
              </div>
            </td>
          </tr>
          
          <tr v-else-if="auditorias.length === 0">
            <td colspan="8" class="text-center py-8 text-gray-400 italic">
              No se encontraron registros de auditoría.
            </td>
          </tr>
        </tbody>
      </table>
      
      <!-- Paginación -->
      <div v-if="pagination.last_page > 1" class="p-4 border-t">
        <div class="flex justify-between items-center">
          <p class="text-sm text-gray-600">
            Página {{ pagination.current_page }} de {{ pagination.last_page }}
          </p>
          <div class="flex space-x-1">
            <button @click="cambiarPagina(pagination.current_page - 1)" 
                    :disabled="pagination.current_page === 1"
                    :class="{'opacity-50 cursor-not-allowed': pagination.current_page === 1}"
                    class="px-3 py-1 border rounded hover:bg-gray-100">
              <i class="fas fa-chevron-left"></i>
            </button>
            
            <button v-for="page in paginasMostrar" :key="page" 
                    @click="cambiarPagina(page)"
                    :class="{'bg-blue-500 text-white': pagination.current_page === page}"
                    class="px-3 py-1 border rounded hover:bg-gray-100">
              {{ page }}
            </button>
            
            <button @click="cambiarPagina(pagination.current_page + 1)" 
                    :disabled="pagination.current_page === pagination.last_page"
                    :class="{'opacity-50 cursor-not-allowed': pagination.current_page === pagination.last_page}"
                    class="px-3 py-1 border rounded hover:bg-gray-100">
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para ver detalles -->
    <div v-if="modalVisible" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl shadow-lg max-w-4xl w-full max-h-[90vh] overflow-hidden">
        <div class="p-6 border-b flex justify-between items-center">
          <h2 class="text-xl font-bold">Detalles de Auditoría #{{ auditoriaSeleccionada.id }}</h2>
          <button @click="cerrarModal" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>
        
        <div class="p-6 overflow-y-auto max-h-[70vh]">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
              <h3 class="font-semibold mb-2">Información básica</h3>
              <div class="space-y-2">
                <p><span class="font-medium">Tabla:</span> {{ auditoriaSeleccionada.table_name }}</p>
                <p><span class="font-medium">ID Registro:</span> {{ auditoriaSeleccionada.record_id }}</p>
                <p><span class="font-medium">Acción:</span> {{ auditoriaSeleccionada.action }}</p>
                <p><span class="font-medium">Fecha:</span> {{ formatFecha(auditoriaSeleccionada.created_at) }}</p>
                <p><span class="font-medium">IP:</span> {{ auditoriaSeleccionada.ip_address || '-' }}</p>
              </div>
            </div>
            
            <div>
              <h3 class="font-semibold mb-2">Información de usuario</h3>
              <div v-if="auditoriaSeleccionada.user" class="space-y-2">
                <p><span class="font-medium">Nombre:</span> {{ auditoriaSeleccionada.user.name }}</p>
                <p><span class="font-medium">Email:</span> {{ auditoriaSeleccionada.user.email }}</p>
                <p><span class="font-medium">User Agent:</span> {{ auditoriaSeleccionada.user_agent || '-' }}</p>
              </div>
              <p v-else class="text-gray-400 italic">Acción realizada por el sistema</p>
            </div>
          </div>
          
          <div class="mt-6">
            <h3 class="font-semibold mb-4">Cambios realizados</h3>
            <div class="space-y-4">
              <div v-if="auditoriaSeleccionada.old_data && Object.keys(auditoriaSeleccionada.old_data).length > 0">
                <h4 class="font-medium text-gray-600 mb-2">Datos anteriores:</h4>
                <pre class="bg-gray-100 p-4 rounded-lg overflow-x-auto text-sm">{{ 
                  JSON.stringify(auditoriaSeleccionada.old_data, null, 2) 
                }}</pre>
              </div>
              
              <div v-if="auditoriaSeleccionada.new_data && Object.keys(auditoriaSeleccionada.new_data).length > 0">
                <h4 class="font-medium text-gray-600 mb-2">Datos nuevos:</h4>
                <pre class="bg-gray-100 p-4 rounded-lg overflow-x-auto text-sm">{{ 
                  JSON.stringify(auditoriaSeleccionada.new_data, null, 2) 
                }}</pre>
              </div>
              
              <div v-if="auditoriaSeleccionada.changed_columns && auditoriaSeleccionada.changed_columns.length > 0">
                <h4 class="font-medium text-gray-600 mb-2">Columnas modificadas:</h4>
                <div class="flex flex-wrap gap-2">
                  <span v-for="col in auditoriaSeleccionada.changed_columns" :key="col" 
                        class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded text-sm">
                    {{ col }}
                  </span>
                </div>
              </div>
              
              <div v-if="auditoriaSeleccionada.action === 'DELETE' && !auditoriaSeleccionada.old_data">
                <p class="text-red-600 italic">Registro eliminado (no hay datos anteriores disponibles)</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="p-6 border-t flex justify-end space-x-3">
          <button @click="cerrarModal" class="px-4 py-2 border rounded hover:bg-gray-100">
            Cerrar
          </button>
          <button @click="descargarAuditoriaPDF(auditoriaSeleccionada)" 
                  class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
            <i class="fas fa-file-pdf mr-2"></i>Descargar PDF
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import axios from 'axios'
import { useAuthToken } from '@/composables/useAuthToken' 

// Estado
const auditorias = ref([])
const cargando = ref(false)
const modalVisible = ref(false)
const auditoriaSeleccionada = ref(null)
const estadisticas = ref(null)
const tablasUnicas = ref([])
const itemsPorPagina = ref(20)

const { accessToken } = useAuthToken()

const axiosAuth = axios.create()

// Filtros
const filtros = ref({
  id: '',
  usuario: '',
  tabla: '',
  accion: '',
  fecha_desde: '',
  fecha_hasta: ''
})

// Paginación
const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  last_page: 1
})

// Computed para mostrar páginas
const paginasMostrar = computed(() => {
  const paginas = []
  const totalPaginas = pagination.value.last_page
  const paginaActual = pagination.value.current_page
  
  let inicio = Math.max(1, paginaActual - 2)
  let fin = Math.min(totalPaginas, paginaActual + 2)
  
  if (fin - inicio < 4) {
    if (inicio === 1) {
      fin = Math.min(totalPaginas, inicio + 4)
    } else {
      inicio = Math.max(1, fin - 4)
    }
  }
  
  for (let i = inicio; i <= fin; i++) {
    paginas.push(i)
  }
  
  return paginas
})

axiosAuth.interceptors.request.use(
  (config) => {
    if (accessToken.value) {
      config.headers.Authorization = `Bearer ${accessToken.value}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Métodos
onMounted(() => {
  cargarAuditorias()
  cargarEstadisticas()
})

async function cargarAuditorias() {
  cargando.value = true
  
  try {
    const params = new URLSearchParams()
    
    // Agregar filtros
    if (filtros.value.id) params.append('id', filtros.value.id)
    if (filtros.value.usuario) params.append('usuario', filtros.value.usuario)
    if (filtros.value.tabla) params.append('tabla', filtros.value.tabla)
    if (filtros.value.accion) params.append('accion', filtros.value.accion)
    if (filtros.value.fecha_desde) params.append('fecha_desde', filtros.value.fecha_desde)
    if (filtros.value.fecha_hasta) params.append('fecha_hasta', filtros.value.fecha_hasta)
    
    // Paginación
    params.append('page', pagination.value.current_page)
    params.append('per_page', itemsPorPagina.value)
    
    // Usar la instancia de axios con autenticación
    const response = await axiosAuth.get('/api/auditorias', { params })
    
    auditorias.value = response.data.data
    pagination.value = response.data.pagination
    
    // Extraer tablas únicas automáticamente
    const tablas = [...new Set(auditorias.value.map(a => a.table_name))]
    tablasUnicas.value = tablas.sort()
    
  } catch (error) {
    console.error('Error al cargar auditorías:', error)
    
    // Manejar error 401 específicamente
    if (error.response?.status === 401) {
      alert('Sesión expirada. Por favor, inicie sesión nuevamente.')
      // Opcional: redirigir al login
      // window.location.href = '/login'
    } else {
      alert('Error al cargar las auditorías: ' + (error.response?.data?.message || error.message))
    }
  } finally {
    cargando.value = false
  }
}

async function cargarEstadisticas() {
  try {
    const response = await axios.get('/api/auditorias/estadisticas')
    estadisticas.value = response.data.data
  } catch (error) {
    console.error('Error al cargar estadísticas:', error)
  }
}

function limpiarFiltros() {
  filtros.value = {
    id: '',
    usuario: '',
    tabla: '',
    accion: '',
    fecha_desde: '',
    fecha_hasta: ''
  }
  pagination.value.current_page = 1
  cargarAuditorias()
}

function cambiarPagina(page) {
  if (page < 1 || page > pagination.value.last_page) return
  pagination.value.current_page = page
  cargarAuditorias()
}

function cambiarItemsPorPagina() {
  pagination.value.current_page = 1
  pagination.value.per_page = parseInt(itemsPorPagina.value)
  cargarAuditorias()
}

function verDetalles(audit) {
  auditoriaSeleccionada.value = audit
  modalVisible.value = true
}

function cerrarModal() {
  modalVisible.value = false
  auditoriaSeleccionada.value = null
}

function getBadgeClass(action) {
  const classes = {
    'INSERT': 'bg-green-100 text-green-800',
    'UPDATE': 'bg-yellow-100 text-yellow-800',
    'DELETE': 'bg-red-100 text-red-800'
  }
  return classes[action] || 'bg-gray-100 text-gray-800'
}

function formatFecha(fecha) {
  if (!fecha) return ''
  const date = new Date(fecha)
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

async function exportarCSV() {
  try {
    const params = new URLSearchParams()
    Object.keys(filtros.value).forEach(key => {
      if (filtros.value[key]) params.append(key, filtros.value[key])
    })
    
    // Usar la nueva ruta /api/auditorias/exportar
    window.open(`/api/auditorias/exportar?${params.toString()}`, '_blank')
  } catch (error) {
    console.error('Error al exportar CSV:', error)
    alert('Error al exportar el archivo CSV')
  }
}

function descargarPDF() {
  const doc = new jsPDF()
  
  // Título
  doc.setFontSize(16)
  doc.text('Reporte de Auditorías del Sistema', 14, 15)
  
  // Filtros aplicados
  let yPos = 25
  doc.setFontSize(10)
  doc.setTextColor(100, 100, 100)
  doc.text('Filtros aplicados:', 14, yPos)
  yPos += 7
  
  const filtrosAplicados = Object.entries(filtros.value)
    .filter(([key, value]) => value)
    .map(([key, value]) => `${key}: ${value}`)
  
  if (filtrosAplicados.length > 0) {
    filtrosAplicados.forEach(filtro => {
      doc.text(`• ${filtro}`, 20, yPos)
      yPos += 5
    })
  } else {
    doc.text('• Ningún filtro aplicado', 20, yPos)
    yPos += 5
  }
  
  yPos += 5
  
  // Tabla de datos
  const tableData = auditorias.value.map(audit => [
    audit.id,
    audit.table_name,
    audit.record_id,
    audit.action,
    audit.user ? audit.user.name : 'Sistema',
    formatFecha(audit.created_at)
  ])
  
  autoTable(doc, {
    startY: yPos,
    head: [['ID', 'Tabla', 'Registro ID', 'Acción', 'Usuario', 'Fecha']],
    body: tableData,
    styles: { fontSize: 8 },
    headStyles: { fillColor: [41, 128, 185] },
    margin: { top: yPos }
  })
  
  // Pie de página
  const pageCount = doc.internal.getNumberOfPages()
  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i)
    doc.setFontSize(8)
    doc.text(
      `Página ${i} de ${pageCount} - Generado el ${new Date().toLocaleDateString('es-ES')}`,
      14,
      doc.internal.pageSize.height - 10
    )
  }
  
  doc.save(`auditorias_${new Date().toISOString().split('T')[0]}.pdf`)
}

function descargarAuditoriaPDF(audit) {
  const doc = new jsPDF()
  
  // Título
  doc.setFontSize(16)
  doc.text(`Auditoría #${audit.id}`, 14, 15)
  
  // Información básica
  doc.setFontSize(10)
  let yPos = 25
  
  const infoBasica = [
    ['Tabla:', audit.table_name],
    ['ID Registro:', audit.record_id],
    ['Acción:', audit.action],
    ['Fecha:', formatFecha(audit.created_at)],
    ['IP:', audit.ip_address || 'No registrada'],
    ['User Agent:', audit.user_agent || 'No registrado']
  ]
  
  if (audit.user) {
    infoBasica.push(['Usuario:', audit.user.name])
    infoBasica.push(['Email:', audit.user.email])
  } else {
    infoBasica.push(['Usuario:', 'Sistema'])
  }
  
  // Función segura para mostrar texto
  const safeText = (text) => {
    if (text === null || text === undefined) return ''
    if (typeof text === 'string') return text
    if (typeof text === 'number') return text.toString()
    if (typeof text === 'boolean') return text ? 'Sí' : 'No'
    return String(text)
  }
  
  infoBasica.forEach(([label, value]) => {
    doc.setFont('helvetica', 'bold')
    doc.text(safeText(label), 14, yPos)
    doc.setFont('helvetica', 'normal')
    doc.text(safeText(value), 50, yPos)
    yPos += 7
  })
  
  yPos += 5
  
  // Datos anteriores - CON VERSIÓN MEJORADA Y SEGURA
  if (audit.old_data && Object.keys(audit.old_data).length > 0) {
    doc.setFont('helvetica', 'bold')
    doc.text('Datos anteriores:', 14, yPos)
    yPos += 7
    
    doc.setFont('helvetica', 'normal')
    
    try {
      // Usar una función de formateo más robusta
      const formatJSONForPDF = (data) => {
        if (!data) return "No hay datos"
        
        try {
          // Convertir a string con manejo de errores
          const jsonString = JSON.stringify(data, null, 2)
          
          // Reemplazar caracteres problemáticos
          const safeString = jsonString
            .replace(/[^\x00-\x7F]/g, '') // Eliminar caracteres no ASCII
            .replace(/[\u0000-\u001F\u007F-\u009F]/g, '') // Eliminar caracteres de control
          
          // Dividir en líneas seguras
          return doc.splitTextToSize(safeString, 180)
        } catch (error) {
          console.warn('Error formateando JSON para PDF:', error)
          return ["Error al formatear datos"]
        }
      }
      
      const oldDataLines = formatJSONForPDF(audit.old_data)
      if (oldDataLines && oldDataLines.length > 0) {
        doc.text(oldDataLines, 14, yPos)
        yPos += (oldDataLines.length * 5) + 10
      }
    } catch (error) {
      console.error('Error procesando datos antiguos:', error)
      doc.text('Error al procesar datos anteriores', 14, yPos)
      yPos += 15
    }
  }
  
  // Datos nuevos - CON VERSIÓN MEJORADA Y SEGURA
  if (audit.new_data && Object.keys(audit.new_data).length > 0) {
    doc.setFont('helvetica', 'bold')
    doc.text('Datos nuevos:', 14, yPos)
    yPos += 7
    
    doc.setFont('helvetica', 'normal')
    
    try {
      const formatJSONForPDF = (data) => {
        if (!data) return "No hay datos"
        
        try {
          // Intentar convertir los datos de manera segura
          const processedData = {}
          Object.keys(data).forEach(key => {
            let value = data[key]
            
            // Convertir valores no string a string de manera segura
            if (value === null || value === undefined) {
              value = ''
            } else if (typeof value === 'object') {
              value = JSON.stringify(value)
            } else {
              value = String(value)
            }
            
            // Limpiar caracteres problemáticos
            value = value.replace(/[^\x20-\x7E]/g, '')
            processedData[key] = value
          })
          
          const jsonString = JSON.stringify(processedData, null, 2)
          return doc.splitTextToSize(jsonString, 180)
        } catch (error) {
          console.warn('Error procesando datos para PDF:', error)
          return ["[Datos binarios o no imprimibles]"]
        }
      }
      
      const newDataLines = formatJSONForPDF(audit.new_data)
      if (newDataLines && newDataLines.length > 0) {
        doc.text(newDataLines, 14, yPos)
        yPos += (newDataLines.length * 5) + 10
      }
    } catch (error) {
      console.error('Error procesando datos nuevos:', error)
      doc.text('Error al procesar datos nuevos', 14, yPos)
      yPos += 15
    }
  }
  
  // Columnas modificadas
  if (audit.changed_columns && audit.changed_columns.length > 0) {
    doc.setFont('helvetica', 'bold')
    doc.text('Columnas modificadas:', 14, yPos)
    yPos += 7
    
    doc.setFont('helvetica', 'normal')
    
    try {
      // Asegurar que todas las columnas sean strings
      const safeColumns = audit.changed_columns.map(col => 
        col ? String(col).replace(/[^\x20-\x7E]/g, '') : 'N/A'
      )
      const columnsStr = safeColumns.join(', ')
      doc.text(columnsStr, 14, yPos)
    } catch (error) {
      console.error('Error procesando columnas modificadas:', error)
      doc.text('Error al mostrar columnas modificadas', 14, yPos)
    }
  }
  
  // Pie de página
  doc.setFontSize(8)
  doc.text(
    `Generado el ${new Date().toLocaleDateString('es-ES')} ${new Date().toLocaleTimeString('es-ES')}`,
    14,
    doc.internal.pageSize.height - 10
  )
  
  // Nombre del archivo seguro
  const safeFileName = `${audit.id}_${audit.table_name}_${audit.action}`
    .replace(/[^\w\s.-]/gi, '') // Eliminar caracteres especiales
    .replace(/\s+/g, '_') // Reemplazar espacios con guiones bajos
  
  doc.save(`auditoria_${safeFileName}.pdf`)
}
</script>

<style scoped>
pre {
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: 0.875rem;
  line-height: 1.4;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>