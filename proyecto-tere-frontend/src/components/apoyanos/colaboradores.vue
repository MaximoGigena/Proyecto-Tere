<template>
  <div class="min-h-screen bg-white p-6">
    <div class="max-w-5xl mx-auto space-y-10">

      <!-- Título principal -->
      <h1 class="text-3xl font-bold text-teal-700 text-center">Colaboradores de TERE</h1>

      <!-- Personas colaboradoras -->
      <section>
        <h2 class="text-xl font-semibold text-teal-600 mb-4">Personas</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
          <div
            v-for="persona in personas"
            :key="persona.nombre"
            class="flex flex-col items-center p-4 border border-teal-100 rounded-xl shadow-sm"
          >
            <img
              :src="persona.foto"
              alt="Foto de colaborador"
              class="w-20 h-20 object-cover rounded-full mb-2"
            />
            <p class="text-center text-sm font-medium text-gray-700">{{ persona.nombre }}</p>
          </div>
        </div>
      </section>

      <!-- Marcas colaboradoras -->
      <section>
        <h2 class="text-xl font-semibold text-teal-600 mb-4">Marcas</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 items-center">
          <div
            v-for="marca in marcas"
            :key="marca.nombre"
            class="flex flex-col items-center p-4 border border-teal-100 rounded-xl shadow-sm"
          >
            <img
              :src="marca.logo"
              alt="Logo de marca"
              class="w-24 h-24 object-contain mb-2"
            />
            <p class="text-center text-sm font-medium text-gray-700">{{ marca.nombre }}</p>
          </div>
        </div>
      </section>

    </div>
  </div>
</template>

<script setup>
const personas = [
  { nombre: 'Laura Martínez', foto: 'https://i.pravatar.cc/100?u=laura' },
  { nombre: 'Carlos Pérez', foto: 'https://i.pravatar.cc/100?u=carlos' },
  { nombre: 'Ana Gómez', foto: 'https://i.pravatar.cc/100?u=ana' },
  { nombre: 'Julián Torres', foto: 'https://i.pravatar.cc/100?u=julian' }
]

const marcas = [
  { nombre: 'Alimascotas S.A.', logo: 'https://cdn.pixabay.com/photo/2025/04/23/04/47/eagle-head-logo-9551557_1280.jpg' },
  { nombre: 'Vet&Co', logo: 'https://cdn.pixabay.com/photo/2025/01/17/04/57/cat-9339089_960_720.jpg' },
  { nombre: 'Amigos Peludos ONG', logo: 'https://cdn.pixabay.com/photo/2025/01/12/18/37/bird-9328907_960_720.jpg' },
  { nombre: 'PetNet', logo: 'https://cdn.pixabay.com/photo/2023/01/24/16/20/apple-7741471_1280.png' }
]
</script>
