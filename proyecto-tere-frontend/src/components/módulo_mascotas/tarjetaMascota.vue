<!-- tarjetaMascota.vue -->
<template>
  <div
    :class="[
      'relative flex items-center w-full rounded-2xl px-6 py-4 shadow-lg hover:shadow-xl transition-all duration-300 border',
      cardClasses.container,
      cardClasses.border,
      cardClasses.hover
    ]"
    @click.stop="$emit('click')"
  >
    <!-- Imagen con efecto de anillo decorativo -->
    <div class="relative">
      <div :class="[
        'absolute inset-0 w-32 h-32 rounded-full blur-sm opacity-40',
        cardClasses.ring
      ]"></div>
      <img
        :src="mascota.imagen || mascota.foto_principal_url || '/default-mascota.jpg'"
        :alt="mascota.nombre"
        class="relative w-28 h-28 rounded-full object-cover border-4 border-white shadow-lg z-10"
      />
      <!-- Indicador de sexo -->
      <div
        :class="[
          'absolute bottom-2 right-2 w-7 h-7 rounded-full border-2 border-white flex items-center justify-center z-20',
          formatoSexo === 'Macho' ? 'bg-blue-400' : 'bg-pink-400'
        ]"
      >
        <font-awesome-icon
          :icon="formatoSexo === 'Macho' ? ['fas', 'mars'] : ['fas', 'venus']"
          class="text-white text-xs"
        />
      </div>
    </div>

    <!-- Contenido -->
    <div class="flex-1 pl-6">
      <!-- Cabecera con nombre y especie/raza -->
      <div class="flex items-center justify-between mb-2">
        <div class="flex items-center space-x-3">
          <h3 :class="[
            'text-xl font-bold',
            cardClasses.text
          ]">
            {{ mascota.nombre }}
          </h3>
          <span
            v-if="formatoEspecie"
            :class="[
              'text-xs font-medium px-2 py-1 rounded-full',
              cardClasses.badge
            ]"
          >
            {{ formatoEspecie }}
          </span>
        </div>
        
        <!-- Acciones flotantes -->
        <div class="flex items-center space-x-3">
          <button
            @click.stop="editarMascota"
            :class="[
              'p-2 rounded-full transition-colors duration-200 shadow-sm',
              cardClasses.editButton
            ]"
            title="Editar mascota"
          >
            <font-awesome-icon :icon="['fas', 'pen-to-square']" />
          </button>
          <button
            @click.stop="eliminarMascota"
            class="p-2 rounded-full bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 transition-colors duration-200 shadow-sm"
            title="Eliminar mascota"
          >
            <font-awesome-icon :icon="['fas', 'trash']" />
          </button>
        </div>
      </div>

      <!-- Información detallada -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div :class="[
          'rounded-xl p-3 shadow-sm',
          cardClasses.infoCard
        ]">
          <p :class="[
            'text-xs font-medium mb-1',
            cardClasses.infoLabel
          ]">Edad</p>
          <p :class="[
            'text-sm font-semibold',
            cardClasses.infoValue
          ]">
            {{ edadFormateada }}
          </p>
        </div>
        
        <div :class="[
          'rounded-xl p-3 shadow-sm',
          cardClasses.infoCard
        ]">
          <p :class="[
            'text-xs font-medium mb-1',
            cardClasses.infoLabel
          ]">Sexo</p>
          <p :class="[
            'text-sm font-semibold',
            cardClasses.infoValue
          ]">
            {{ formatoSexo }}
          </p>
        </div>
        
        <div
          v-if="mascota.raza"
          :class="[
            'rounded-xl p-3 shadow-sm',
            cardClasses.infoCard
          ]"
        >
          <p :class="[
            'text-xs font-medium mb-1',
            cardClasses.infoLabel
          ]">Raza</p>
          <p :class="[
            'text-sm font-semibold',
            cardClasses.infoValue
          ]">
            {{ mascota.raza }}
          </p>
        </div>
        
        <div
          v-if="mascota.peso"
          :class="[
            'rounded-xl p-3 shadow-sm',
            cardClasses.infoCard
          ]"
        >
          <p :class="[
            'text-xs font-medium mb-1',
            cardClasses.infoLabel
          ]">Peso</p>
          <p :class="[
            'text-sm font-semibold',
            cardClasses.infoValue
          ]">
            {{ mascota.peso }} kg
          </p>
        </div>
      </div>

      <!-- Estado de salud (opcional) -->
      <div v-if="mascota.estado_salud" class="mt-3">
        <div class="flex items-center space-x-2">
          <div class="w-2 h-2 rounded-full bg-green-400"></div>
          <span :class="[
            'text-xs',
            cardClasses.healthText
          ]">{{ mascota.estado_salud }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { computed } from 'vue'

const props = defineProps({
  mascota: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['editar', 'eliminar', 'click'])

// Computed para la edad con múltiples fallbacks
const edadFormateada = computed(() => {
  const mascota = props.mascota
  
  // 1. Intentar con edad_formateada directo
  if (mascota.edad_formateada && mascota.edad_formateada !== 'Edad no disponible') {
    return mascota.edad_formateada
  }
  
  // 2. Intentar con la relación edadRelacion
  if (mascota.edad_relacion && mascota.edad_relacion.edad_formateada) {
    return mascota.edad_relacion.edad_formateada
  }
  
  // 3. Intentar con el accessor edad
  if (mascota.edad && mascota.edad !== 'Edad no disponible') {
    return mascota.edad
  }
  
  return 'Edad no disponible'
})

// Computed para formatear el sexo
const formatoSexo = computed(() => {
  const sexo = props.mascota.sexo
  if (sexo === 'macho') return 'Macho'
  if (sexo === 'hembra') return 'Hembra'
  return sexo || 'No especificado'
})

// Computed para obtener la taxonomía normalizada según tu selector
const taxonomiaNormalizada = computed(() => {
  // Prioridad: taxonomia → especie → valor por defecto
  const taxonomia = props.mascota.taxonomia || props.mascota.especie || ''
  return taxonomia.toLowerCase().trim()
})

// Computed para formatear la especie para mostrar
const formatoEspecie = computed(() => {
  const taxonomia = props.mascota.taxonomia || props.mascota.especie || ''
  
  // Si no hay taxonomía, no mostrar nada
  if (!taxonomia) return ''
  
  // Mapeo para mostrar nombres más amigables
  const nombresAmigables = {
    'canino': 'Canino',
    'felino': 'Felino',
    'equino': 'Equino',
    'bovino': 'Bovino',
    'ave': 'Ave',
    'pez': 'Pez',
    'otro': 'Otro'
  }
  
  return nombresAmigables[taxonomia.toLowerCase()] || 
    // Capitalizar primera letra de cada palabra como fallback
    taxonomia.split(' ').map(word => 
      word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
    ).join(' ')
})

// Computed para obtener las clases CSS según la taxonomía
const cardClasses = computed(() => {
  const taxonomia = taxonomiaNormalizada.value
  
  // Mapeo directo basado en tus 7 taxonomías principales
  const mapeoTaxonomia = {
    // Caninos
    'canino': 'canino',
    'perro': 'canino',
    'canis': 'canino',
    'dog': 'canino',
    
    // Felinos
    'felino': 'felino',
    'gato': 'felino',
    'felis': 'felino',
    'cat': 'felino',
    
    // Equinos
    'equino': 'equino',
    'caballo': 'equino',
    'yegua': 'equino',
    'poni': 'equino',
    'horse': 'equino',
    
    // Bovinos
    'bovino': 'bovino',
    'vaca': 'bovino',
    'toro': 'bovino',
    'buey': 'bovino',
    'cow': 'bovino',
    'ganado': 'bovino',
    
    // Aves
    'ave': 'ave',
    'pájaro': 'ave',
    'bird': 'ave',
    'loro': 'ave',
    'canario': 'ave',
    'gallina': 'ave',
    'pato': 'ave',
    
    // Peces
    'pez': 'pez',
    'fish': 'pez',
    'acuario': 'pez',
    'pez dorado': 'pez',
    'trucha': 'pez',
    'salmón': 'pez',
    
    // Otros
    'otro': 'otro',
    'roedor': 'otro',
    'reptil': 'otro',
    'hamster': 'otro',
    'conejo': 'otro',
    'tortuga': 'otro',
    'serpiente': 'otro'
  }

  // Determinar la categoría
  let categoria = 'default'
  
  // Buscar coincidencia exacta primero
  if (mapeoTaxonomia[taxonomia]) {
    categoria = mapeoTaxonomia[taxonomia]
  } else {
    // Si no hay coincidencia exacta, buscar por substring
    for (const [key, value] of Object.entries(mapeoTaxonomia)) {
      if (taxonomia.includes(key)) {
        categoria = value
        break
      }
    }
  }

  // Esquemas de colores alineados con tu selector de especies
  const esquemas = {
    // Canino - Azul/Índigo/Púrpura (como en tu selector)
    canino: {
      container: 'bg-gradient-to-r from-blue-50 to-indigo-50',
      border: 'border-blue-200 hover:border-blue-400',
      hover: 'hover:from-blue-100 hover:to-indigo-100',
      ring: 'bg-gradient-to-r from-blue-400 to-indigo-300',
      text: 'text-blue-900',
      badge: 'bg-blue-200 text-blue-800',
      editButton: 'bg-blue-100 text-blue-700 hover:bg-blue-200 hover:text-blue-900',
      infoCard: 'bg-white',
      infoLabel: 'text-blue-500',
      infoValue: 'text-blue-800',
      healthText: 'text-blue-600'
    },
    // Felino - Rosa/Rojo/Rosa oscuro (como en tu selector)
    felino: {
      container: 'bg-gradient-to-r from-pink-50 to-rose-50',
      border: 'border-pink-200 hover:border-pink-400',
      hover: 'hover:from-pink-100 hover:to-rose-100',
      ring: 'bg-gradient-to-r from-pink-400 to-rose-300',
      text: 'text-pink-900',
      badge: 'bg-pink-200 text-pink-800',
      editButton: 'bg-pink-100 text-pink-700 hover:bg-pink-200 hover:text-pink-900',
      infoCard: 'bg-white',
      infoLabel: 'text-pink-500',
      infoValue: 'text-pink-800',
      healthText: 'text-pink-600'
    },
    // Equino - Naranja/Amarillo/Ámbar (como en tu selector)
    equino: {
      container: 'bg-gradient-to-r from-orange-50 to-amber-50',
      border: 'border-orange-200 hover:border-orange-400',
      hover: 'hover:from-orange-100 hover:to-amber-100',
      ring: 'bg-gradient-to-r from-orange-400 to-amber-300',
      text: 'text-orange-900',
      badge: 'bg-orange-200 text-orange-800',
      editButton: 'bg-orange-100 text-orange-700 hover:bg-orange-200 hover:text-orange-900',
      infoCard: 'bg-white',
      infoLabel: 'text-orange-500',
      infoValue: 'text-orange-800',
      healthText: 'text-orange-600'
    },
    // Bovino - Verde/Esmeralda/Verde azulado (como en tu selector)
    bovino: {
      container: 'bg-gradient-to-r from-green-50 to-teal-50',
      border: 'border-green-200 hover:border-green-400',
      hover: 'hover:from-green-100 hover:to-teal-100',
      ring: 'bg-gradient-to-r from-green-400 to-teal-300',
      text: 'text-green-900',
      badge: 'bg-green-200 text-green-800',
      editButton: 'bg-green-100 text-green-700 hover:bg-green-200 hover:text-green-900',
      infoCard: 'bg-white',
      infoLabel: 'text-green-500',
      infoValue: 'text-green-800',
      healthText: 'text-green-600'
    },
    // Ave - Lima/Verde/Esmeralda (como en tu selector)
    ave: {
      container: 'bg-gradient-to-r from-lime-50 to-emerald-50',
      border: 'border-lime-200 hover:border-lime-400',
      hover: 'hover:from-lime-100 hover:to-emerald-100',
      ring: 'bg-gradient-to-r from-lime-400 to-emerald-300',
      text: 'text-lime-900',
      badge: 'bg-lime-200 text-lime-800',
      editButton: 'bg-lime-100 text-lime-700 hover:bg-lime-200 hover:text-lime-900',
      infoCard: 'bg-white',
      infoLabel: 'text-lime-500',
      infoValue: 'text-lime-800',
      healthText: 'text-lime-600'
    },
    // Pez - Cian/Azul/Índigo (como en tu selector)
    pez: {
      container: 'bg-gradient-to-r from-cyan-50 to-blue-50',
      border: 'border-cyan-200 hover:border-cyan-400',
      hover: 'hover:from-cyan-100 hover:to-blue-100',
      ring: 'bg-gradient-to-r from-cyan-400 to-blue-300',
      text: 'text-cyan-900',
      badge: 'bg-cyan-200 text-cyan-800',
      editButton: 'bg-cyan-100 text-cyan-700 hover:bg-cyan-200 hover:text-cyan-900',
      infoCard: 'bg-white',
      infoLabel: 'text-cyan-500',
      infoValue: 'text-cyan-800',
      healthText: 'text-cyan-600'
    },
    // Otro - Verde azulado/Verde/Esmeralda (como en tu selector)
    otro: {
      container: 'bg-gradient-to-r from-teal-50 to-emerald-50',
      border: 'border-teal-200 hover:border-teal-400',
      hover: 'hover:from-teal-100 hover:to-emerald-100',
      ring: 'bg-gradient-to-r from-teal-400 to-emerald-300',
      text: 'text-teal-900',
      badge: 'bg-teal-200 text-teal-800',
      editButton: 'bg-teal-100 text-teal-700 hover:bg-teal-200 hover:text-teal-900',
      infoCard: 'bg-white',
      infoLabel: 'text-teal-500',
      infoValue: 'text-teal-800',
      healthText: 'text-teal-600'
    },
    // Esquema por defecto
    default: {
      container: 'bg-gradient-to-r from-slate-50 to-gray-50',
      border: 'border-slate-200 hover:border-slate-400',
      hover: 'hover:from-slate-100 hover:to-gray-100',
      ring: 'bg-gradient-to-r from-slate-400 to-gray-300',
      text: 'text-slate-900',
      badge: 'bg-slate-200 text-slate-800',
      editButton: 'bg-slate-100 text-slate-700 hover:bg-slate-200 hover:text-slate-900',
      infoCard: 'bg-white',
      infoLabel: 'text-slate-500',
      infoValue: 'text-slate-800',
      healthText: 'text-slate-600'
    }
  }

  return esquemas[categoria] || esquemas.default
})

const editarMascota = () => {
  emit('editar', props.mascota.id)
}

const eliminarMascota = () => {
  emit('eliminar', props.mascota.id)
}
</script>