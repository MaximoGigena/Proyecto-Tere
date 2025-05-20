<script setup>
defineProps({
  mascota: Object,
  bgColor: String
})

defineEmits(['click'])
</script>

<template>
  <div 
    :class="['rounded-lg p-4 transition cursor-pointer', bgColor]"
    @click="$emit('click')"
  >
    <img :src="mascota.imagen" alt="Mascota" class="w-full h-32 object-cover rounded-lg mb-2"/>
    <h3 class="font-bold">{{ mascota.nombre }}</h3>
    <p class="text-sm">{{ mascota.edad }}, {{ mascota.sexo }}</p>
  </div>
</template>