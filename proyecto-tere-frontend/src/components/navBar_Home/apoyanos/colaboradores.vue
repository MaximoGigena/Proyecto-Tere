<template>
  <div class="min-h-screen bg-white">
    <!-- Encabezado -->
    <section class="h-28 w-auto flex flex-col justify-center items-center bg-gradient-to-r from-teal-500 to-green-300 relative px-6 text-center">
      <!-- Logo en esquina -->
      <router-link @click="volverOInicio()" to="/" class="absolute top-4 left-4">
        <img 
          src="@/assets/Logo_Pagina_Oscura.png" 
          alt="Logo TERE"
          class="h-60 w-auto hover:opacity-80 transition -mt-16"
        />
      </router-link>

      <!-- Título principal -->
      <h1 class="text-4xl font-bold text-white">Colaboradores de TERE</h1>
    </section>

    <!-- Progreso de funcionalidades -->
    <section class="max-w-4xl mx-auto mt-8 px-6">
      <h2 class="text-lg text-center font-semibold text-gray-700 mb-4">
        Progreso de funcionalidades gracias a colaboradores
      </h2>
      <div 
        v-for="(funcionalidad, index) in funcionalidades" 
        :key="index" 
        class="mb-6"
      >
        <p class="text-sm font-medium text-gray-700 mb-1">
          {{ funcionalidad.nombre }} - {{ funcionalidadAnimada[index] }}%
        </p>
        <div class="w-full bg-gray-200 rounded-full h-5 overflow-hidden shadow-inner">
          <div 
            class="h-full bg-gradient-to-r from-teal-500 to-green-300 text-white text-xs flex items-center justify-center transition-all duration-700"
            :style="{ width: funcionalidadAnimada[index] + '%' }"
          >
            {{ funcionalidadAnimada[index] }}%
          </div>
        </div>
      </div>
    </section>

    <div class="max-w-5xl mx-auto space-y-10">
      <!-- Personas colaboradoras -->
      <section>
        <h2 class="text-xl font-semibold text-teal-600 mb-4">Veterinarias</h2>
        <div class="relative max-w-6xl mx-auto px-4">
          <!-- Flechas -->
          <button 
            @click="prevVeterinaria" 
            class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-white/80 hover:bg-white p-2 rounded-full shadow-md transition-all"
            :disabled="currentVetIndex === 0"
            :class="{'opacity-50 cursor-not-allowed': currentVetIndex === 0}"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          
          <button 
            @click="nextVeterinaria" 
            class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-white/80 hover:bg-white p-2 rounded-full shadow-md transition-all"
            :disabled="currentVetIndex === groupedVeterinarias.length - 1"
            :class="{'opacity-50 cursor-not-allowed': currentVetIndex === groupedVeterinarias.length - 1}"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>

          <!-- Carrusel -->
          <div class="overflow-hidden py-8">
            <div 
              class="flex transition-transform duration-500 ease-in-out" 
              :style="{ transform: `translateX(-${currentVetIndex * 100}%)` }"
            >
              <div 
                v-for="(group, index) in groupedVeterinarias" 
                :key="'vet-'+index"
                class="w-full flex-shrink-0 px-4"
              >
                <div class="grid grid-cols-4 gap-6 items-center">
                  <div
                    v-for="veterinaria in group"
                    :key="veterinaria.nombre"
                    class="flex flex-col items-center p-4 border border-teal-100 rounded-xl bg-white shadow-sm hover:shadow-md transition-all hover:scale-105"
                  >
                    <img
                      :src="veterinaria.logo"
                      alt="Logo de veterinaria"
                      class="w-24 h-24 object-contain mb-3 grayscale hover:grayscale-0 transition-all"
                    />
                    <p class="text-center text-sm font-medium text-gray-700">{{ veterinaria.nombre }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Indicadores -->
          <div class="flex justify-center gap-2 mt-4">
            <button 
              v-for="(_, index) in groupedVeterinarias" 
              :key="'vet-ind-'+index"
              @click="currentVetIndex = index"
              class="w-3 h-3 rounded-full transition-all"
              :class="currentVetIndex === index ? 'bg-teal-500 w-6' : 'bg-teal-200'"
            ></button>
          </div>
        </div>
      </section>

      <!-- Marcas -->
      <section>
        <h2 class="text-xl font-semibold text-teal-600 mb-4">Marcas</h2>
        <div class="relative max-w-6xl mx-auto px-4">
          <!-- Flechas -->
          <button 
            @click="prevMarca" 
            class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-white/80 hover:bg-white p-2 rounded-full shadow-md transition-all"
            :disabled="currentMarcaIndex === 0"
            :class="{'opacity-50 cursor-not-allowed': currentMarcaIndex === 0}"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          
          <button 
            @click="nextMarca" 
            class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-white/80 hover:bg-white p-2 rounded-full shadow-md transition-all"
            :disabled="currentMarcaIndex === groupedMarcas.length - 1"
            :class="{'opacity-50 cursor-not-allowed': currentMarcaIndex === groupedMarcas.length - 1}"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>

          <!-- Carrusel -->
          <div class="overflow-hidden py-8">
            <div 
              class="flex transition-transform duration-500 ease-in-out" 
              :style="{ transform: `translateX(-${currentMarcaIndex * 100}%)` }"
            >
              <div 
                v-for="(group, index) in groupedMarcas" 
                :key="'marca-'+index"
                class="w-full flex-shrink-0 px-4"
              >
                <div class="grid grid-cols-4 gap-6 items-center">
                  <div
                    v-for="marca in group"
                    :key="marca.nombre"
                    class="flex flex-col items-center p-4 border border-teal-100 rounded-xl bg-white shadow-sm hover:shadow-md transition-all hover:scale-105"
                  >
                    <img
                      :src="marca.logo"
                      alt="Logo de marca"
                      class="w-24 h-24 object-contain mb-3 grayscale hover:grayscale-0 transition-all"
                    />
                    <p class="text-center text-sm font-medium text-gray-700">{{ marca.nombre }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Indicadores -->
          <div class="flex justify-center gap-2 mt-4">
            <button 
              v-for="(_, index) in groupedMarcas" 
              :key="'marca-ind-'+index"
              @click="currentMarcaIndex = index"
              class="w-3 h-3 rounded-full transition-all"
              :class="currentMarcaIndex === index ? 'bg-teal-500 w-6' : 'bg-teal-200'"
            ></button>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'

export default {
  setup() {
    const funcionalidades = ref([
      { nombre: 'Nueva sección de adopciones', progreso: 80 },
      { nombre: 'Sistema de registro de voluntarios', progreso: 55 },
      { nombre: 'Integración con veterinarias', progreso: 40 },
      { nombre: 'Tienda solidaria online', progreso: 65 }
    ])

    // Animación desde 0
    const funcionalidadAnimada = ref(funcionalidades.value.map(() => 0))
    
    onMounted(() => {
      setTimeout(() => {
        funcionalidades.value.forEach((f, i) => {
          setTimeout(() => {
            funcionalidadAnimada.value[i] = f.progreso
          }, i * 200)
        })
      }, 500)
    })

    return {
      funcionalidades,
      funcionalidadAnimada
    }
  },
  data() {
    return {
      currentVetIndex: 0,
      currentMarcaIndex: 0,
      marcas: [
        { nombre: "Marca 1", logo: "https://cdn.pixabay.com/photo/2017/01/31/23/42/animal-2028258_1280.png" },
        { nombre: "Marca 2", logo: "https://cdn.pixabay.com/photo/2017/01/31/23/42/animal-2028258_1280.png" },
        { nombre: "Marca 3", logo: "https://cdn.pixabay.com/photo/2017/01/31/23/42/animal-2028258_1280.png" },
        { nombre: "Marca 4", logo: "https://cdn.pixabay.com/photo/2017/01/31/23/42/animal-2028258_1280.png" },
        { nombre: "Marca 5", logo: "https://cdn.pixabay.com/photo/2023/09/22/15/45/panda-8269336_1280.png" },
        { nombre: "Marca 6", logo: "https://cdn.pixabay.com/photo/2023/09/22/15/45/panda-8269336_1280.png" },
        { nombre: "Marca 7", logo: "https://cdn.pixabay.com/photo/2023/09/22/15/45/panda-8269336_1280.png" },
        { nombre: "Marca 8", logo: "https://cdn.pixabay.com/photo/2023/09/22/15/45/panda-8269336_1280.png" },
        { nombre: "Marca 9", logo: "https://cdn.pixabay.com/photo/2017/01/31/23/42/animal-2028258_1280.png" },
        { nombre: "Marca 10", logo: "https://cdn.pixabay.com/photo/2017/01/31/23/42/animal-2028258_1280.png" },
        { nombre: "Marca 11", logo: "https://cdn.pixabay.com/photo/2017/01/31/23/42/animal-2028258_1280.png" },
        { nombre: "Marca 12", logo: "https://cdn.pixabay.com/photo/2017/01/31/23/42/animal-2028258_1280.png" },
      ],
      veterinarias: [
        { nombre: "Veterinaria 1", logo: "https://cdn.pixabay.com/photo/2022/03/09/07/12/cat-7057193_1280.png" },
        { nombre: "Veterinaria 2", logo: "https://cdn.pixabay.com/photo/2022/03/09/07/12/cat-7057193_1280.png" },
        { nombre: "Veterinaria 3", logo: "https://cdn.pixabay.com/photo/2022/03/09/07/12/cat-7057193_1280.png" },
        { nombre: "Veterinaria 4", logo: "https://cdn.pixabay.com/photo/2022/03/09/07/12/cat-7057193_1280.png" },
        { nombre: "Veterinaria 5", logo: "https://cdn.pixabay.com/photo/2022/03/09/07/12/cat-7057193_1280.png" },
        { nombre: "Veterinaria 6", logo: "https://cdn.pixabay.com/photo/2022/03/09/07/12/cat-7057193_1280.png" },
        { nombre: "Veterinaria 7", logo: "https://cdn.pixabay.com/photo/2022/03/09/07/12/cat-7057193_1280.png" },
        { nombre: "Veterinaria 8", logo: "https://cdn.pixabay.com/photo/2022/03/09/07/12/cat-7057193_1280.png" },
        { nombre: "Veterinaria 9", logo: "https://cdn.pixabay.com/photo/2022/03/09/07/12/cat-7057193_1280.png" },
        { nombre: "Veterinaria 10", logo: "https://cdn.pixabay.com/photo/2022/03/09/07/12/cat-7057193_1280.png" },
        { nombre: "Veterinaria 11", logo: "https://cdn.pixabay.com/photo/2022/03/09/07/12/cat-7057193_1280.png" },
        { nombre: "Veterinaria 12", logo: "https://cdn.pixabay.com/photo/2022/03/09/07/12/cat-7057193_1280.png" }
      ],
      itemsPerGroup: 4 
    }
  },
  computed: {
    groupedVeterinarias() {
      const groups = [];
      for (let i = 0; i < this.veterinarias.length; i += this.itemsPerGroup) {
        groups.push(this.veterinarias.slice(i, i + this.itemsPerGroup));
      }
      return groups;
    },
    groupedMarcas() {
      const groups = [];
      for (let i = 0; i < this.marcas.length; i += this.itemsPerGroup) {
        groups.push(this.marcas.slice(i, i + this.itemsPerGroup));
      }
      return groups;
    }
  },
  methods: {
    volverOInicio() {
      if (window.history.length > 1) {
        this.$router.back()
      } else {
        this.$router.push('/')
      }
    },
    nextVeterinaria() {
      if (this.currentVetIndex < this.groupedVeterinarias.length - 1) {
        this.currentVetIndex++;
      }
    },
    prevVeterinaria() {
      if (this.currentVetIndex > 0) {
        this.currentVetIndex--;
      }
    },
    nextMarca() {
      if (this.currentMarcaIndex < this.groupedMarcas.length - 1) {
        this.currentMarcaIndex++;
      }
    },
    prevMarca() {
      if (this.currentMarcaIndex > 0) {
        this.currentMarcaIndex--;
      }
    }
  }
}
</script>