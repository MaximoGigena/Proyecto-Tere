<!-- carpetaHistoriales.vue - VERSIÓN CORREGIDA -->
<template>
  <div 
    ref="animatedBg"
    class="bg-cover bg-repeat bg-center flex flex-col h-screen relative"
    :style="{ backgroundImage: `url(${huellas})` }"
  >
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden mt-4">
      
      <!-- Header -->
      <div class="bg-gray-800 text-white p-4 flex justify-between items-center">
        <h1 class="text-xl font-bold">{{ tituloCabecera }}</h1>
        <button
          @click="cerrarVista"
          class="text-white hover:text-red-400 transition"
          title="Cerrar"
        >
          <font-awesome-icon :icon="['fas', 'times']" />
        </button>
      </div>
  
      <!-- Tabs como links -->
      <div class="flex border-b border-gray-200 bg-gray-100">
        <router-link
          v-for="(tab, index) in tabs"
          :key="index"
          :to="tab.path" 
          class="flex items-center justify-center gap-2 flex-1 py-2 text-sm font-medium text-center transition-colors"
          :class="{
            'border-b-2 border-blue-500 bg-white text-blue-600': isTabActive(tab),
            'text-gray-600 hover:bg-gray-200 hover:text-gray-800': !isTabActive(tab)
          }"
        >
          <font-awesome-icon :icon="['fas', tab.icon]" class="text-xl" />
          <span>{{ tab.nombre }}</span>
        </router-link>
      </div>
      
      <!-- Router View - PASAR PROPS DE PERMISOS -->
      <router-view 
        :key="$route.fullPath"
        :mascotaId="mascotaId"
        :isOverlay="route.meta.overlay"
        :ofertaId="ofertaId"
        :tienePermisoHistorial="tienePermisoHistorial"
        :nombreMascota="nombreMascota"
        :puedeContactarTutor="puedeContactarTutor"
      />
    </div>
  </div>
</template>
  
<script setup>
import { useRouter, useRoute } from 'vue-router'
import { ref, computed, onMounted } from 'vue'
import huellas from '@/assets/huellas.png';

const route = useRoute()
const router = useRouter()

// Obtener parámetros de la URL
const mascotaId = computed(() => {
  return route.params.id || route.query.id;
});

// ✅ Obtener permisos de los query params
const ofertaId = computed(() => route.query.ofertaId || null);
const tienePermisoHistorial = computed(() => {
  // Si viene de una oferta, usar el permiso; si no, por defecto true
  if (route.query.permisoHistorial !== undefined) {
    return route.query.permisoHistorial === '1';
  }
  return true; // Por defecto, si no viene de oferta, tiene permiso
});
const puedeContactarTutor = computed(() => {
  if (route.query.puedeContactar !== undefined) {
    return route.query.puedeContactar === '1';
  }
  return false;
});
const nombreMascota = computed(() => {
  return route.query.nombreMascota || 'la mascota';
});
const origen = computed(() => route.query.origen || 'normal');

// En carpetaHistoriales.vue - tabs
const tabs = computed(() => {
  const id = mascotaId.value;
  const isOverlay = route.meta.overlay;
  
  if (!id) {
    console.warn('⚠️ No hay ID de mascota');
    return [];
  }
  
  // Obtener los parámetros de navegación actuales
  const currentFrom = route.query.from;
  
  // Construir query params para mantener permisos
  const queryParams = {
    from: currentFrom,
    ofertaId: ofertaId.value,
    permisoHistorial: tienePermisoHistorial.value ? '1' : '0',
    puedeContactar: puedeContactarTutor.value ? '1' : '0',
    nombreMascota: nombreMascota.value,
    origen: origen.value
  };
  
  // Filtrar valores undefined
  Object.keys(queryParams).forEach(key => {
    if (queryParams[key] === undefined || queryParams[key] === null) {
      delete queryParams[key];
    }
  });
  
  const basePath = isOverlay ? '/veterinarios/mascota' : '/revisar';
  
  return [
    { 
      nombre: 'Tutores', 
      icon: 'shield-dog',
      path: isOverlay 
        ? { path: `${basePath}/${id}/tutores`, query: queryParams }
        : { path: `${basePath}/tutores/${id}`, query: queryParams },
      activeNames: ['tutores', 'veterinario-tutores']  
    },
    { 
      nombre: 'Ficha',  // 👈 CAMBIADO: antes era 'Episodios'
      icon: 'id-card',          // 👈 CAMBIADO: antes era 'clock-rotate-left'
      path: isOverlay 
        ? { path: `${basePath}/${id}/ficha-medica`, query: queryParams }  // 👈 CAMBIADO
        : { path: `${basePath}/ficha-medica/${id}`, query: queryParams },  // 👈 CAMBIADO
      activeNames: ['fichaMedica', 'veterinario-fichaMedica']  // 👈 CAMBIADO
    },
    { 
      nombre: 'Preventivo', 
      icon: 'square-plus',
      path: isOverlay 
        ? { path: `${basePath}/${id}/historialPreventivo/vacunas`, query: queryParams }
        : { path: `${basePath}/historialPreventivo/${id}/vacunas`, query: queryParams },
      activeNames: ['historialPreventivo','vacunas','desparasitaciones','revisiones','alergias', 'veterinario-historialPreventivo', 'veterinario-vacunas', 'veterinario-desparasitaciones', 'veterinario-revisiones', 'veterinario-alergias']   
    },
    { 
      nombre: 'Clínico', 
      icon: 'stethoscope',
      path: isOverlay 
        ? { path: `${basePath}/${id}/historialClinico/cirugias`, query: queryParams }
        : { path: `${basePath}/historialClinico/${id}/cirugias`, query: queryParams },
      activeNames: [
        'historialClinico', 'cirugias', 'farmacos', 'terapias','diagnosticos', 'paliativos',
        'veterinario-historialClinico', 'veterinario-cirugias', 'veterinario-farmacos', 'veterinario-terapias', 'veterinario-diagnosticos', 'veterinario-paliativos'
      ]
    },
  ];
});

const animatedBg = ref(null)

onMounted(() => {
  console.log('📁 CarpetaHistoriales montado', {
    mascotaId: mascotaId.value,
    ofertaId: ofertaId.value,
    tienePermisoHistorial: tienePermisoHistorial.value,
    puedeContactarTutor: puedeContactarTutor.value,
    nombreMascota: nombreMascota.value,
    origen: origen.value,
    from: route.query.from
  });
  
  if (animatedBg.value) {
    animatedBg.value.style.backgroundImage = `url(${huellas})`
    animatedBg.value.style.animation = 'moverHuellas 120s linear infinite'
    animatedBg.value.style.backgroundPosition = '0 0'
  }
})

// ✅ VERSIÓN CORREGIDA - Cierra la vista restaurando los valores originales
async function cerrarVista() {
  const contextoStr = sessionStorage.getItem('contenido_mascota_contexto')
  
  if (contextoStr) {
    try {
      const contexto = JSON.parse(contextoStr)
      const isRecent = (Date.now() - contexto.timestamp) < 30 * 60 * 1000
      
      if (isRecent && contexto.origenRuta) {
        sessionStorage.removeItem('contenido_mascota_contexto')
        
        const origenRuta = contexto.origenRuta
        const mascotaId = contexto.mascotaId
        const ofertaId = contexto.ofertaId
        
        let returnUrl = ''
        
        if (origenRuta.startsWith('/explorar/cerca')) {
          // ✅ Pasar parámetros para que se abra el overlay automáticamente
          returnUrl = `/explorar/cerca?ts=${Date.now()}&abrir_mascota=${mascotaId}&oferta_id=${ofertaId || ''}`
        } 
        else if (origenRuta.startsWith('/explorar/encuentros')) {
          // ✅ Pasar parámetros para que se abra el overlay automáticamente
          returnUrl = `/explorar/encuentros?ts=${Date.now()}&abrir_mascota=${mascotaId}&oferta_id=${ofertaId || ''}`
        }
        else if (origenRuta.includes('/perfil/mascotas')) {
          // ✅ Pasar parámetros para que se abra el overlay automáticamente
          returnUrl = `/explorar/perfil/mascotas?ts=${Date.now()}&abrir_mascota=${mascotaId}`
        }
        else {
          returnUrl = origenRuta
        }
        
        console.log('🚀 Navegando a URL con parámetros:', returnUrl)
        await router.push(returnUrl)
        return
      }
    } catch (error) {
      console.error('Error:', error)
    }
  }
  
  // SEGUNDO: Intentar usar el query param 'from' (fallback)
  const from = route.query.from
  
  if (from === 'contenido_mascota') {
    const mascotaIdValue = mascotaId.value
    const ofertaIdValue = ofertaId.value
    
    if (mascotaIdValue) {
      if (ofertaIdValue) {
        await router.push(`/explorar/cerca?ts=${Date.now()}&oferta_id=${ofertaIdValue}`)
      } else {
        await router.push('/explorar/perfil/mascotas?ts=${Date.now()}')
      }
      return
    }
  }
  
  // TERCERO: Fallback para veterinarios
  if (route.meta.overlay) {
    await router.push('/veterinarios')
    return
  }
  
  // CUARTO: Último recurso
  await router.push('/explorar/encuentros')
}

const titulosPorRuta = {
  tutores: 'Tutores de la Mascota',
  'veterinario-tutores': 'Tutores de la Mascota',
  fichaMedica: 'Ficha Médica de la Mascota',
  'veterinario-fichaMedica': 'Ficha Médica de la Mascota',
  historialPreventivo: 'Procedimientos Preventivos',
  'veterinario-historialPreventivo': 'Procedimientos Preventivos',
  vacunas: 'Vacunas Aplicadas',
  desparasitaciones: 'Desparasitaciones Realizadas',
  revisiones: 'Revisiones Realizadas',
  alergias: 'Alergias o Sensibilidades',
  historialClinico: 'Historial Clínico',
  'veterinario-historialClinico': 'Historial Clínico',
  cirugias: 'Cirugías Realizadas',
  farmacos: 'Fármacos Administrados',
  terapias: 'Terapias Realizadas',
  diagnosticos: 'Diagnósticos Realizados',
  paliativos: 'Cuidados Paliativos',
}

const tituloCabecera = computed(() => {
  return titulosPorRuta[route.name] || 'Historial de la Mascota'
})

const isTabActive = (tab) => {
  if (tab.activeNames?.includes(route.name)) {
    return true;
  }
  
  const matchedNames = route.matched.map(r => r.name);
  if (matchedNames.some(name => tab.activeNames?.includes(name))) {
    return true;
  }
  
  return false;
};
</script>

<style scoped>  
  @keyframes moverHuellas {
    0% {
      background-position: 0 0;
    }
    100% {
      background-position: 0 1024px;
    }
  }

  .animate-huellas {
    animation: moverHuellas 120s linear infinite;
  }
</style>