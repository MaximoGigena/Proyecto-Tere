<!-- components/ElementosGraficos/TelefonoInput.vue -->
<template>
  <div>
    <label class="block font-medium">Teléfono</label>
    <div class="flex rounded-md shadow-sm">
      <!-- Selector de país -->
      <div class="relative">
        <button
          type="button"
          @click="toggleDropdown"
          class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-blue-500"
        >
          <span class="text-xl mr-1">{{ selectedCountry.flag }}</span>
          <span class="mr-1">{{ selectedCountry.code }}</span>
          <font-awesome-icon icon="chevron-down" class="text-xs" />
        </button>
        
        <!-- Dropdown de países -->
        <div 
          v-if="showDropdown" 
          class="absolute z-50 mt-1 w-64 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
        >
          <input
            v-model="searchTerm"
            type="text"
            placeholder="Buscar país..."
            class="sticky top-0 w-full px-3 py-2 border-b border-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
          <div class="py-1">
            <button
              v-for="country in filteredCountries"
              :key="country.code"
              @click="selectCountry(country)"
              class="w-full px-4 py-2 text-left hover:bg-gray-100 flex items-center gap-2"
            >
              <span class="text-xl">{{ country.flag }}</span>
              <span class="font-medium">{{ country.code }}</span>
              <span class="text-sm text-gray-600">{{ country.name }}</span>
            </button>
          </div>
        </div>
      </div>
      
      <!-- Input del número -->
      <input
        :value="localNumber"
        @input="handleNumberInput"
        type="tel"
        class="flex-1 rounded-r-md border border-gray-300 p-2 focus:ring-blue-500 focus:border-blue-500"
        :placeholder="selectedCountry.placeholder"
      />
    </div>
    <p class="text-xs text-gray-500 mt-1">
      {{ selectedCountry.example }}
    </p>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue'])

// Lista de países
const countries = [
  {
    name: 'Argentina',
    code: '+54',
    flag: '🇦🇷',
    dialCode: '54',
    placeholder: '9 3758 526513',
    example: 'Ej: 9 3758 526513',
    format: (number) => {
        let clean = number.replace(/\D/g, '');
        
        // Si tiene el 9 (celular)
        if (clean.startsWith('9')) {
        let rest = clean.substring(1); // Sacamos el 9
        
        // Formato: 9 + código área (4 dígitos) + número (7-8 dígitos)
        if (rest.length >= 4) {
            let areaCode = rest.substring(0, 4);
            let remaining = rest.substring(4);
            
            if (remaining.length >= 4) {
            let firstPart = remaining.substring(0, 4);
            let secondPart = remaining.substring(4, 10);
            if (secondPart) {
                return `9 ${areaCode} ${firstPart} ${secondPart}`;
            }
            return `9 ${areaCode} ${firstPart}`;
            }
            return `9 ${areaCode} ${remaining}`;
        }
        return `9 ${rest}`;
        }
        
        // Teléfono fijo (sin 9)
        if (clean.length >= 4) {
        let areaCode = clean.substring(0, 4);
        let remaining = clean.substring(4);
        
        if (remaining.length >= 4) {
            let firstPart = remaining.substring(0, 4);
            let secondPart = remaining.substring(4, 10);
            if (secondPart) {
            return `${areaCode} ${firstPart} ${secondPart}`;
            }
            return `${areaCode} ${firstPart}`;
        }
        return `${areaCode} ${remaining}`;
        }
        
        return clean;
    }
    },
  {
    name: 'España',
    code: '+34',
    flag: '🇪🇸',
    dialCode: '34',
    placeholder: '612 345 678',
    example: 'Ej: 612 345 678',
    format: (number) => {
      let clean = number.replace(/\D/g, '')
      if (clean.length >= 3) {
        return `${clean.substring(0, 3)} ${clean.substring(3, 6)} ${clean.substring(6, 9)}`.trim()
      }
      return clean
    }
  },
  {
    name: 'México',
    code: '+52',
    flag: '🇲🇽',
    dialCode: '52',
    placeholder: '55 1234 5678',
    example: 'Ej: 55 1234 5678',
    format: (number) => {
      let clean = number.replace(/\D/g, '')
      if (clean.length >= 2) {
        if (clean.length >= 6) {
          return `${clean.substring(0, 2)} ${clean.substring(2, 6)} ${clean.substring(6, 10)}`.trim()
        }
        return `${clean.substring(0, 2)} ${clean.substring(2)}`
      }
      return clean
    }
  },
  {
    name: 'Chile',
    code: '+56',
    flag: '🇨🇱',
    dialCode: '56',
    placeholder: '9 1234 5678',
    example: 'Ej: 9 1234 5678',
    format: (number) => {
      let clean = number.replace(/\D/g, '')
      if (clean.length >= 1) {
        if (clean.startsWith('9') && clean.length >= 5) {
          return `9 ${clean.substring(1, 5)} ${clean.substring(5, 9)}`.trim()
        }
        if (clean.length >= 4) {
          return `${clean.substring(0, 4)} ${clean.substring(4, 8)}`.trim()
        }
      }
      return clean
    }
  },
  {
    name: 'Uruguay',
    code: '+598',
    flag: '🇺🇾',
    dialCode: '598',
    placeholder: '91 234 567',
    example: 'Ej: 91 234 567',
    format: (number) => {
      let clean = number.replace(/\D/g, '')
      if (clean.length >= 2) {
        return `${clean.substring(0, 2)} ${clean.substring(2, 5)} ${clean.substring(5, 8)}`.trim()
      }
      return clean
    }
  },
  {
    name: 'Colombia',
    code: '+57',
    flag: '🇨🇴',
    dialCode: '57',
    placeholder: '301 1234567',
    example: 'Ej: 301 1234567',
    format: (number) => {
      let clean = number.replace(/\D/g, '')
      if (clean.length >= 3) {
        return `${clean.substring(0, 3)} ${clean.substring(3, 7)} ${clean.substring(7, 10)}`.trim()
      }
      return clean
    }
  },
  {
    name: 'Perú',
    code: '+51',
    flag: '🇵🇪',
    dialCode: '51',
    placeholder: '987 654 321',
    example: 'Ej: 987 654 321',
    format: (number) => {
      let clean = number.replace(/\D/g, '')
      if (clean.length >= 3) {
        return `${clean.substring(0, 3)} ${clean.substring(3, 6)} ${clean.substring(6, 9)}`.trim()
      }
      return clean
    }
  },
  {
    name: 'Estados Unidos',
    code: '+1',
    flag: '🇺🇸',
    dialCode: '1',
    placeholder: '(555) 123-4567',
    example: 'Ej: (555) 123-4567',
    format: (number) => {
      let clean = number.replace(/\D/g, '')
      if (clean.length >= 3) {
        if (clean.length >= 6) {
          return `(${clean.substring(0, 3)}) ${clean.substring(3, 6)}-${clean.substring(6, 10)}`
        }
        return `(${clean.substring(0, 3)}) ${clean.substring(3)}`
      }
      return clean
    }
  }
]

// Estado
const showDropdown = ref(false)
const searchTerm = ref('')
const selectedCountry = ref(countries[0]) // Argentina por defecto
const localNumber = ref('')

// Países filtrados por búsqueda
const filteredCountries = computed(() => {
  if (!searchTerm.value) return countries
  const term = searchTerm.value.toLowerCase()
  return countries.filter(country => 
    country.name.toLowerCase().includes(term) ||
    country.code.includes(term) ||
    country.dialCode.includes(term)
  )
})

// Parsear valor inicial (formato internacional +5493758526513)
const parseInitialValue = (value) => {
  if (!value) return { country: countries[0], number: '' }
  
  const clean = value.replace(/\D/g, '')
  
  // Buscar país por código de discado
  for (const country of countries) {
    if (clean.startsWith(country.dialCode)) {
      let numberPart = clean.substring(country.dialCode.length)
      return { country, number: numberPart }
    }
  }
  
  return { country: countries[0], number: clean }
}

// Generar valor completo para emitir
const getFullNumber = () => {
  if (!localNumber.value) return ''
  const cleanNumber = localNumber.value.replace(/\D/g, '')
  if (!cleanNumber) return ''
  return `${selectedCountry.value.dialCode}${cleanNumber}`
}

// Manejar input del número
const handleNumberInput = (event) => {
  let rawValue = event.target.value
  // Aplicar formato específico del país
  const formatted = selectedCountry.value.format(rawValue)
  localNumber.value = formatted
  
  // Emitir valor completo
  emit('update:modelValue', getFullNumber())
}

// Seleccionar país
const selectCountry = (country) => {
  selectedCountry.value = country
  showDropdown.value = false
  searchTerm.value = ''
  
  // Reformatear el número con el nuevo país
  if (localNumber.value) {
    const cleanNumber = localNumber.value.replace(/\D/g, '')
    localNumber.value = country.format(cleanNumber)
    emit('update:modelValue', getFullNumber())
  }
}

// Toggle dropdown
const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value
}

// Cerrar dropdown al hacer click fuera
const handleClickOutside = (event) => {
  if (!event.target.closest('.relative')) {
    showDropdown.value = false
  }
}

// Watch para cambios externos
watch(() => props.modelValue, (newValue) => {
  if (newValue !== getFullNumber()) {
    const { country, number } = parseInitialValue(newValue)
    selectedCountry.value = country
    localNumber.value = country.format(number)
  }
})

// Inicializar
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  if (props.modelValue) {
    const { country, number } = parseInitialValue(props.modelValue)
    selectedCountry.value = country
    localNumber.value = country.format(number)
  }
})

// Limpiar evento
import { onBeforeUnmount } from 'vue'
onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>