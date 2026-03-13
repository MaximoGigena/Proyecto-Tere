<!-- views/gestionarAdopciones.vue -->
<template>
  <div class="p-4 max-w-3xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Gestión de Adopciones</h1>

    <!-- Loading state -->
    <div v-if="cargando" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-2"></div>
      <p class="text-gray-600">Cargando mascotas...</p>
    </div>

    <!-- Listado de mascotas en adopción -->
    <div v-else>
      <div v-if="mascotasEnAdopcion.length" class="space-y-4">
        <div
          v-for="mascota in mascotasEnAdopcion"
          :key="mascota.id"
          class="bg-white rounded-xl shadow p-4 flex items-center justify-between"
        >
          <div class="flex items-center gap-4">
            <img :src="mascota.foto || '/placeholder-mascota.jpg'" alt="foto mascota" class="w-16 h-16 object-cover rounded-full" />
            <div>
              <h2 class="font-bold">{{ mascota.nombre }}</h2>
              <p class="text-sm text-gray-500">Estado: {{ mascota.estadoAdopcion || 'Publicada' }}</p>
            </div>
          </div>
          <div class="flex gap-2">
            <button @click="verSolicitudes(mascota)" class="px-3 py-1 rounded-lg bg-blue-500 text-white hover:bg-blue-600 text-sm">Ver solicitudes</button>
            <button @click="cancelarAdopcion(mascota.id)" class="px-3 py-1 rounded-lg bg-red-500 hover:bg-red-600 text-white text-sm">Cancelar</button>
          </div>
        </div>
      </div>
      <div v-else class="text-gray-600">No tenés mascotas en proceso de adopción.</div>

      <!-- Botón para abrir overlay -->
      <button
        @click="abrirOverlaySeleccion"
        class="text-white bg-blue-600 rounded-full px-5 py-2 text-base md:text-lg font-bold shadow-md hover:bg-blue-700 hover:scale-105 transition transform duration-200 mx-auto block mt-8"
      >
        + Adopción
      </button>
    </div>

    <!-- Overlay de selección de mascotas -->
    <OverlaySeleccionarMascota
      v-if="mostrarOverlay"
      :mascotas="mascotasDisponibles"
      @close="cerrarOverlay"
      @select="mascotaSeleccionada"
    />

    <!-- Overlay de advertencia y configuración -->
    <OverlayAdvertenciaAdopcion
      v-if="mostrarAdvertencia"
      :mascota="mascotaSeleccionadaParaAdopcion"
      @close="cerrarAdvertencia"
      @confirmar="crearOfertaAdopcion"
    />

    <!-- En el template, después del OverlayAdvertenciaAdopcion -->
    <OverlayConfirmacionFinal
      v-if="mostrarConfirmacionFinal"
      :mascota="datosOfertaCompleta?.mascota || mascotaSeleccionadaParaAdopcion"
      :permisos="permisosSeleccionados"
      @close="cerrarConfirmacionFinal"
      @confirmar="publicarOfertaAdopcion"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import OverlaySeleccionarMascota from '@/components/módulo_adopciones/OverlaySeleccionarMascota.vue'
import OverlayAdvertenciaAdopcion from '@/components/módulo_adopciones/OverlayAdvertenciaAdopcion.vue'
import OverlayConfirmacionFinal from '@/components/módulo_adopciones/OverlayConfirmacionFinal.vue'

const router = useRouter()

// Estados
const cargando = ref(true)
const mascotasEnAdopcion = ref([])
const mascotasDisponibles = ref([])
const mostrarOverlay = ref(false)

const mostrarAdvertencia = ref(false)
const mascotaSeleccionadaParaAdopcion = ref(null)

// Agrega estos estados
const mostrarConfirmacionFinal = ref(false)
const permisosSeleccionados = ref(null)
const datosOfertaCompleta = ref(null)

// Cargar datos del usuario - VERSIÓN CORREGIDA
async function cargarMascotasUsuario() {
  try {
    cargando.value = true;
    
    console.log('🚀 Iniciando carga de mascotas para adopciones...');
    
    // 1. Cargar mascotas disponibles (NO en adopción)
    const response = await fetch(`/api/adopciones/mis-mascotas/disponibles`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json'
      }
    });
    
    console.log('📡 Response status (disponibles):', response.status);
    
    if (response.ok) {
      const data = await response.json();
      console.log('📦 Datos completos recibidos (disponibles):', data);
      
      if (data.success) {
        console.log('📊 Estructura de cada mascota disponible:', data.data[0]);
        
        mascotasDisponibles.value = data.data.map(m => ({
          id: m.id,
          nombre: m.nombre,
          foto: m.foto_url || '/placeholder-mascota.jpg',
          especie: capitalizeFirstLetter(m.especie) || 'Sin especificar',
          raza: m.caracteristicas?.raza || 'No especificada',
          edad: m.edad || calcularEdadAproximada(m.edad_formateada) || '?',
          descripcion: m.caracteristicas?.descripcion || ''
        }));
        
        console.log('✅ Mascotas disponibles procesadas:', mascotasDisponibles.value.length);
        console.log('📋 IDs de mascotas disponibles:', mascotasDisponibles.value.map(m => m.id));
      } else {
        console.error('❌ API no devolvió success:', data);
      }
    } else {
      console.error('❌ Error en la respuesta:', response.status);
      // Intentar leer el cuerpo del error
      try {
        const errorData = await response.json();
        console.error('📄 Cuerpo del error:', errorData);
      } catch (e) {
        console.error('No se pudo parsear el error como JSON');
      }
    }
    
    // 2. Cargar mascotas en adopción
    await cargarMascotasEnAdopcion();
    
  } catch (error) {
    console.error('💥 Error al cargar mascotas:', error);
    await cargarMascotasFallback();
  } finally {
    cargando.value = false;
    console.log('🏁 Carga de mascotas completada');
  }
}

// 2. Cargar mascotas en adopción
async function cargarMascotasEnAdopcion() {
  try {
    const responseAdopcion = await fetch(`/api/adopciones/mis-mascotas/en-adopcion`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json'
      }
    });
    
    console.log('📡 Status de mascotas en adopción:', responseAdopcion.status);
    
    if (responseAdopcion.ok) {
      const data = await responseAdopcion.json();
      console.log('📦 Datos de mascotas en adopción:', data);
      
      if (data.success) {
        mascotasEnAdopcion.value = data.data.map(m => ({
          id: m.id,
          nombre: m.nombre,
          foto: m.foto || '/placeholder-mascota.jpg',
          especie: capitalizeFirstLetter(m.especie) || 'Sin especificar',
          estadoAdopcion: m.estadoAdopcion || 'Publicada'
        }));
        
        console.log('✅ Mascotas en adopción procesadas:', mascotasEnAdopcion.value);
      }
    } else {
      console.error('❌ Error al cargar mascotas en adopción:', responseAdopcion.status);
    }
  } catch (error) {
    console.error('💥 Error en cargarMascotasEnAdopcion:', error);
  }
}

// Método fallback usando el endpoint existente - TAMBIÉN CORREGIDO
async function cargarMascotasFallback() {
  try {
    console.log('🔄 Usando fallback: cargando todas las mascotas...');
    
    const response = await fetch(`/api/mascotas`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json'
      }
    });
    
    if (response.ok) {
      const data = await response.json();
      console.log('📦 Datos fallback:', data);
      
      if (data.success && data.mascotas) {
        // CORRECCIÓN: NO usar 'this'
        mascotasDisponibles.value = data.mascotas.map(m => ({
          id: m.id,
          nombre: m.nombre,
          foto: m.foto_principal_url || '/placeholder-mascota.jpg',
          especie: capitalizeFirstLetter(m.especie) || 'Sin especificar', // SIN 'this'
          raza: m.caracteristicas?.raza || 'No especificada',
          edad: calcularEdadAproximada(m.edad_formateada) || '?', // SIN 'this'
          descripcion: m.caracteristicas?.descripcion || ''
        }));
        
        console.log('✅ Fallback exitoso:', mascotasDisponibles.value.length, 'mascotas');
      }
    }
  } catch (error) {
    console.error('💥 Error en fallback:', error);
    // Último recurso: datos de ejemplo
    cargarDatosEjemplo();
  }
}

// Métodos auxiliares - definidos normalmente (sin 'this' en composition API)
function capitalizeFirstLetter(string) {
  if (!string) return '';
  return string.charAt(0).toUpperCase() + string.slice(1);
}

function calcularEdadAproximada(edadFormateada) {
  if (!edadFormateada) return '?';
  
  // Extraer el número de la cadena de edad
  const match = edadFormateada.match(/(\d+)/);
  return match ? match[1] : '?';
}

// Datos de ejemplo mejorados
function cargarDatosEjemplo() {
  console.log('🎭 Cargando datos de ejemplo...');
  
  // Usar datos hardcodeados
  const mascotasEjemplo = [
    {
      id: 1,
      nombre: 'Luna',
      foto: 'https://cdn.pixabay.com/photo/2025/01/23/19/21/french-bulldog-9355276_1280.jpg',
      especie: 'Perro',
      raza: 'Bulldog Francés',
      edad: '2',
      descripcion: 'Muy juguetona y cariñosa'
    },
    {
      id: 2,
      nombre: 'Rocky',
      foto: 'https://cdn.pixabay.com/photo/2019/06/29/10/53/pet-4305994_1280.jpg',
      especie: 'Perro',
      raza: 'Labrador',
      edad: '3',
      descripcion: 'Tranquilo y buen guardián'
    }
  ];
  
  mascotasDisponibles.value = mascotasEjemplo;
  mascotasEnAdopcion.value = [];
  
  console.log('✅ Datos de ejemplo cargados');
}


// Funciones del overlay
function abrirOverlaySeleccion() {
  console.log('Mascotas disponibles al abrir overlay:', mascotasDisponibles.value);
  
  // Verificar si hay mascotas disponibles
  if (mascotasDisponibles.value.length === 0) {
    alert('No tenés mascotas disponibles para poner en adopción.');
    return;
  }
  
  mostrarOverlay.value = true;
}

function cerrarOverlay() {
  mostrarOverlay.value = false
}

function mascotaSeleccionada(mascota) {
  cerrarOverlay()
  mascotaSeleccionadaParaAdopcion.value = mascota
  mostrarAdvertencia.value = true
}

function verSolicitudes(mascota) {
  router.push({
    name: 'SolicitudesAdopcion',
    params: { mascotaId: mascota.id }
  })
}

async function cancelarAdopcion(idMascota) {
  if (!confirm('¿Estás seguro de cancelar esta adopción?')) return
  
  try {
    // Llamar a API para cancelar adopción
    const response = await fetch(`/api/adopciones/${idMascota}/cancelar`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      // Actualizar lista local
      mascotasEnAdopcion.value = mascotasEnAdopcion.value.filter(m => m.id !== idMascota)
      
      // Recargar mascotas disponibles
      await cargarMascotasUsuario()
      
      alert('Adopción cancelada exitosamente')
    } else {
      throw new Error('Error al cancelar adopción')
    }
  } catch (error) {
    console.error('Error:', error)
    alert('Hubo un error al cancelar la adopción')
  }
}

// Agrega estas funciones
function cerrarAdvertencia() {
  mostrarAdvertencia.value = false
  mascotaSeleccionadaParaAdopcion.value = null
}

// Modifica la función crearOfertaAdopcion para mostrar el overlay de confirmación
// Modifica la función crearOfertaAdopcion para mostrar el overlay de confirmación
async function crearOfertaAdopcion(datosOferta) {
  console.log('🎯 EVENTO crearOfertaAdopcion RECIBIDO!')
  console.log('📦 Datos recibidos:', datosOferta)
  console.log('📝 Mascota seleccionada:', mascotaSeleccionadaParaAdopcion.value)
  
  // Guardar los datos para usarlos después
  datosOfertaCompleta.value = datosOferta
  permisosSeleccionados.value = datosOferta.permisos
  
  console.log('💾 Datos guardados:')
  console.log('- datosOfertaCompleta:', datosOfertaCompleta.value)
  console.log('- permisosSeleccionados:', permisosSeleccionados.value)
  
  // Cerrar el overlay de advertencia
  cerrarAdvertencia()
  
  console.log('🚪 Overlay de advertencia cerrado')
  console.log('🔍 Estado antes de mostrar confirmación:')
  console.log('- mostrarConfirmacionFinal:', mostrarConfirmacionFinal.value)
  console.log('- mostrarAdvertencia:', mostrarAdvertencia.value)
  
  // Mostrar el overlay de confirmación final
  mostrarConfirmacionFinal.value = true
  
  console.log('✅ Overlay de confirmación final activado:', mostrarConfirmacionFinal.value)
}

// Función para cerrar la confirmación final
function cerrarConfirmacionFinal() {
  mostrarConfirmacionFinal.value = false
  permisosSeleccionados.value = null
  datosOfertaCompleta.value = null
}

// Función final que realmente publica la oferta
async function publicarOfertaAdopcion() {
  try {
    if (!datosOfertaCompleta.value) {
      throw new Error('No hay datos de oferta')
    }

    console.log('📤 Publicando oferta con datos:', datosOfertaCompleta.value);
    console.log('🆔 Mascota ID:', datosOfertaCompleta.value.mascotaId);
    
    // ✅ USAR mascotaSeleccionadaParaAdopcion en lugar de datosOfertaCompleta.value.mascota
    const response = await fetch('/api/adopciones', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(datosOfertaCompleta.value)
    })
    
    const responseData = await response.json();
    console.log('📥 Respuesta del servidor:', responseData);
    
    if (response.ok && responseData.success) {
      cerrarConfirmacionFinal()
      
      // ✅ CORREGIDO: Usar mascotaSeleccionadaParaAdopcion.value
      const nombreMascota = mascotaSeleccionadaParaAdopcion.value?.nombre || 'la mascota';
      
      alert(`¡Oferta de adopción creada exitosamente para ${nombreMascota}!`)
      await cargarMascotasUsuario()
    } else {
      throw new Error(responseData.message || 'Error al crear oferta')
    }
  } catch (error) {
    console.error('Error:', error)
    alert('Hubo un error al crear la oferta de adopción: ' + error.message)
    cerrarConfirmacionFinal()
  }
}

// Cargar datos al montar el componente
onMounted(() => {
  cargarMascotasUsuario()
})
</script>

<style scoped>
/* Estilos adicionales */
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

