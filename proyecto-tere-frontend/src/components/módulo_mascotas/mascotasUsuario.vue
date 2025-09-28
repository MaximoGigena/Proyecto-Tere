<!-- mascotasUsuario.vue -->
<template>
  <div class="space-y-6">
    <MascotaCard
      v-for="(mascota, index) in mascotas"
      :key="mascota.id"
      :mascota="mascota"
      :bgColor="bgColors[index % bgColors.length]"
      @click="abrirDetalleMascota(mascota.id)"
      @editar="editarMascota"
      @eliminar="eliminarMascota"
    />

    <div v-if="loading" class="text-center py-8 flex flex-col items-center">
      <div class="w-10 h-10 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
      <p class="mt-4 text-lg font-medium">Cargando mascotas...</p>
    </div>

    <div v-else-if="mascotas.length === 0" class="text-center py-8">
      <p>No tienes mascotas registradas</p>
    </div>

    <button
      @click="abrirRegistroMascota"
      class="text-white bg-blue-600 rounded-full px-5 py-2 text-base md:text-lg font-bold shadow-md hover:bg-blue-700 hover:scale-105 transition transform duration-200 mx-auto block"
    >
      + Mascota
    </button>

    <div class="relative">
      <router-view />
      <router-view name="overlay" />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import MascotaCard from '@/components/módulo_mascotas/tarjetaMascota.vue'
import { useAuthToken } from '@/composables/useAuthToken'

const router = useRouter()
const { accessToken, isAuthenticated, tokenData, loadTokenFromStorage } = useAuthToken()
const mascotas = ref([])
const loading = ref(true)

// Logs para ver el estado del token
console.log('[MascotasUsuario] Estado inicial del token:', {
  accessToken: accessToken.value,
  isAuthenticated: isAuthenticated.value,
  tokenData: tokenData.value
})

// Watcher para monitorear cambios en el token
watch(accessToken, (newToken, oldToken) => {
  console.log('[MascotasUsuario] Token cambiado:', {
    oldToken: oldToken ? `${oldToken.substring(0, 10)}...` : 'null',
    newToken: newToken ? `${newToken.substring(0, 10)}...` : 'null'
  })
})

watch(isAuthenticated, (newAuthStatus) => {
  console.log('[MascotasUsuario] Estado autenticación cambiado:', newAuthStatus)
})

// Cargar mascotas al montar el componente
const cargarMascotas = async () => {
  try {
    loading.value = true
    
    // Verificar autenticación antes de hacer la petición
    if (!isAuthenticated.value) {
      console.warn('[MascotasUsuario] Usuario no autenticado, intentando cargar token desde storage')
      loadTokenFromStorage()
      
      if (!isAuthenticated.value) {
        console.error('[MascotasUsuario] No hay token de autenticación disponible')
        throw new Error('Usuario no autenticado')
      }
    }

    console.log('[MascotasUsuario] Realizando petición a /api/mascotas con token:', {
      tokenPresente: !!accessToken.value,
      tokenInicio: accessToken.value ? `${accessToken.value.substring(0, 10)}...` : 'null'
    })

    const response = await axios.get('/api/mascotas', {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Content-Type': 'application/json'
      }
    })

    console.log('[MascotasUsuario] Respuesta del servidor:', {
      success: response.data.success,
      cantidadMascotas: response.data.mascotas?.length || 0
    })

    if (response.data.success) {
      mascotas.value = response.data.mascotas.map(mascota => {
        console.log('[MascotasUsuario] Procesando mascota:', {
          id: mascota.id,
          nombre: mascota.nombre,
          cantidadFotos: mascota.fotos?.length || 0
        })
        
        let imagenUrl = 'https://cdn.pixabay.com/photo/2017/08/18/06/49/capybara-2653996_1280.jpg';
        
        if (mascota.fotos && mascota.fotos.length > 0) {
          const foto = mascota.fotos[0];
          imagenUrl = foto.url;
          console.log('[MascotasUsuario] URL de imagen desde accessor:', imagenUrl)
        } else {
          console.log('[MascotasUsuario] Usando imagen por defecto para mascota:', mascota.nombre)
        }
        
        return {
          id: mascota.id,
          nombre: mascota.nombre,
          edad: `${mascota.edad} ${mascota.unidad_edad}`,
          sexo: mascota.sexo === 'macho' ? 'Macho' : 'Hembra',
          imagen: imagenUrl
        }
      })

      console.log('[MascotasUsuario] Mascotas cargadas exitosamente:', mascotas.value.length)
    }
  } catch (error) {
    console.error('[MascotasUsuario] Error al cargar mascotas:', {
      error: error.message,
      status: error.response?.status,
      data: error.response?.data
    })

    // Manejo específico de errores de autenticación
    if (error.response?.status === 401) {
      console.warn('[MascotasUsuario] Token inválido o expirado')
      // Aquí podrías redirigir al login o intentar refrescar el token
    }
  } finally {
    loading.value = false
    console.log('[MascotasUsuario] Carga de mascotas finalizada')
  }
}

onMounted(() => {
  cargarMascotas()
})

const abrirRegistroMascota = () => {
  router.push({
    path: '/explorar/perfil/registro',
    query: {
      from: '/explorar/perfil/mascotas'
    }
  });
};


const abrirDetalleMascota = (id) => {
  router.push({
    path: `/explorar/perfil/mascota/${id}`,
    query: {
      from: '/explorar/perfil/mascotas' // Ruta exacta a la que quieres volver
    }
  });
};

const bgColors = [
  'bg-orange-200 hover:bg-orange-400',
  'bg-yellow-200 hover:bg-yellow-400',
  'bg-purple-200 hover:bg-purple-400',
  'bg-red-200 hover:bg-red-400',
  'bg-sky-200 hover:bg-sky-400',
  'bg-fuchsia-200 hover:bg-fuchsia-400',
  'bg-emerald-200 hover:bg-emerald-400'
];


// En tu componente que lista las mascotas
const editarMascota = (id) => {
  router.push({ 
    path: `/explorar/perfil/mascotas/editar/${id}`,
    query: {
      from: '/explorar/perfil/mascotas'
    }
  })
}

const eliminarMascota = (id) => {
  router.push({
    name: "darBajaMascota", // 👉 definiremos esta ruta
    params: { id },
    query: { from: "/explorar/perfil/mascotas" }
  })
}
</script>

<style scoped>
.bg-transparent {
  background-color: transparent;
  /* Opcional: asegura que no afecte el layout */
  position: static;
  z-index: 0;
}
</style>