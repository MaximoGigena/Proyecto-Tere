<!-- BotonesSwipe.vue -->
<template>
  <div class="flex flex-col items-center gap-6">
    <!-- Contenedor de botones -->
    <div 
      ref="botonesContainer"
      :class="[
        'flex justify-center gap-14 z-20 transition-all duration-700 ease-out',
        { 'opacity-0 translate-y-10': !mostrarBotones, 'opacity-100 translate-y-0': mostrarBotones }
      ]"
    >
      <!-- Botón Dislike -->
      <button 
        @click="handleDislike"
        :disabled="procesandoSwipe"
        :class="[
          'bg-white border border-black w-16 h-16 rounded-full shadow-lg',
          'flex items-center justify-center transition duration-300',
          'hover:bg-red-50 active:scale-95',
          { 'cursor-not-allowed opacity-50': procesandoSwipe }
        ]"
        :aria-label="procesandoSwipe ? 'Procesando...' : 'No me interesa'"
        :aria-busy="procesandoSwipe"
      >
        <font-awesome-icon 
          :icon="['fas', 'xmark']" 
          :class="[
            'text-black text-5xl',
            { 'hover:text-red-400': !procesandoSwipe }
          ]" 
        />
      </button>

      <!-- Botón Like -->
      <button 
        @click="handleLike"
        :disabled="procesandoSwipe"
        :class="[
          'bg-white border border-black w-16 h-16 rounded-full shadow-lg',
          'flex items-center justify-center transition duration-300',
          'hover:bg-green-50 active:scale-95',
          { 'cursor-not-allowed opacity-50': procesandoSwipe }
        ]"
        :aria-label="procesandoSwipe ? 'Procesando...' : 'Me gusta'"
        :aria-busy="procesandoSwipe"
      >
        <font-awesome-icon 
          :icon="['fas', 'heart']" 
          :class="[
            'text-black text-4xl',
            { 'hover:text-green-400': !procesandoSwipe }
          ]" 
        />
      </button>
    </div>

    <!-- Indicadores de swipe -->
    <div 
      v-if="mostrandoIndicadorSwipe"
      :class="[
        'fixed inset-0 flex items-center justify-center z-40 pointer-events-none',
        'transition-opacity duration-300',
        indicadorSwipeClass
      ]"
    >
      <div :class="[
        'text-6xl font-bold px-8 py-4 rounded-2xl shadow-2xl',
        'animate-pulse',
        indicadorSwipeTextoClass
      ]">
        {{ indicadorSwipeTexto }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useSwipe } from '@/composables/useSwipe'

// ============ INTERFACES ============
interface SwipeData {
  mascotaId: string | number | null
  ofertaId: string | number | null
  timestamp: string
}

interface SwipeAnimation {
  transform: string
  opacity: string
}

interface SwipeButtonProps {
  mascotaId?: string | number | null
  ofertaId?: string | number | null
  mostrarBotones?: boolean
  mostrarInstrucciones?: boolean
  contenedorElement?: HTMLElement | null
}

// ============ PROPS ============
const props = withDefaults(defineProps<SwipeButtonProps>(), {
  mascotaId: null,
  ofertaId: null,
  mostrarBotones: true,
  mostrarInstrucciones: true,
  contenedorElement: null
})

// ============ EMITS ============
const emit = defineEmits<{
  (e: 'like', data: SwipeData): void
  (e: 'dislike', data: SwipeData): void
  (e: 'swipe-start', tipo: string): void
  (e: 'swipe-end', tipo: string): void
  (e: 'swipe-cancel', tipo: string): void
  (e: 'swipe-animation', animation: SwipeAnimation): void
}>()

// ============ REFS Y REACTIVIDAD ============
const botonesContainer = ref<HTMLElement | null>(null)
const procesandoSwipe = ref<boolean>(false)
const mostrandoIndicadorSwipe = ref<boolean>(false)
const indicadorSwipeTexto = ref<string>('')
const indicadorSwipeClass = ref<string>('')
const indicadorSwipeTextoClass = ref<string>('')

// Variables para gestos táctiles
let startX = 0
let startY = 0
let isSwiping = false
let currentDeltaX = 0

// ============ FUNCIONES PRINCIPALES ============
/**
 * Maneja la acción de "dislike" (no me gusta)
 */
async function handleDislike(): Promise<void> {
  if (procesandoSwipe.value) return

  if (!props.mascotaId && !props.ofertaId) {
   console.warn('No hay ID para realizar el like')
   return
  }
  
  procesandoSwipe.value = true
  emit('swipe-start', 'dislike')
  
  try {
    mostrarIndicadorSwipe('NO', 'red')
    emitSwipeAnimation(-100, -10)
    
    await new Promise(resolve => setTimeout(resolve, 500))
    
    emit('dislike', {
      mascotaId: props.mascotaId || null,
      ofertaId: props.ofertaId || null,
      timestamp: new Date().toISOString()
    })
    
  } catch (error) {
    console.error('Error en handleDislike:', error)
    emit('swipe-cancel', 'dislike')
  } finally {
    setTimeout(() => {
      resetSwipeAnimation()
      ocultarIndicadorSwipe()
      procesandoSwipe.value = false
      emit('swipe-end', 'dislike')
    }, 100)
  }
}

/**
 * Maneja la acción de "like" (me gusta)
 */
async function handleLike(): Promise<void> {
  if (procesandoSwipe.value) return
  
  if (!props.mascotaId && !props.ofertaId) {
   console.warn('No hay ID para realizar el like')
   return
  }

  procesandoSwipe.value = true
  emit('swipe-start', 'like')
  
  try {
    mostrarIndicadorSwipe('LIKE', 'green')
    emitSwipeAnimation(100, 10)
    
    await new Promise(resolve => setTimeout(resolve, 500))
    
    emit('like', {
      mascotaId: props.mascotaId || null,
      ofertaId: props.ofertaId || null,
      timestamp: new Date().toISOString()
    })
    
  } catch (error) {
    console.error('Error en handleLike:', error)
    emit('swipe-cancel', 'like')
  } finally {
    setTimeout(() => {
      resetSwipeAnimation()
      ocultarIndicadorSwipe()
      procesandoSwipe.value = false
      emit('swipe-end', 'like')
    }, 100)
  }
}

/**
 * Emite animación de swipe al componente padre
 */
function emitSwipeAnimation(translateX: number, rotation: number): void {
  emit('swipe-animation', {
    transform: `translateX(${translateX}vw) rotate(${rotation}deg)`,
    opacity: 'opacity-0'
  })
}

/**
 * Resetea la animación de swipe
 */
function resetSwipeAnimation(): void {
  emit('swipe-animation', {
    transform: '',
    opacity: ''
  })
}

// ============ INDICADORES VISUALES ============
/**
 * Muestra indicador visual del tipo de swipe
 */
function mostrarIndicadorSwipe(texto: string, tipo: 'green' | 'red'): void {
  indicadorSwipeTexto.value = texto
  
  if (tipo === 'green') {
    indicadorSwipeClass.value = 'bg-green-50 bg-opacity-90'
    indicadorSwipeTextoClass.value = 'text-green-700'
  } else if (tipo === 'red') {
    indicadorSwipeClass.value = 'bg-red-50 bg-opacity-90'
    indicadorSwipeTextoClass.value = 'text-red-700'
  }
  
  mostrandoIndicadorSwipe.value = true
}

/**
 * Oculta el indicador visual
 */
function ocultarIndicadorSwipe(): void {
  mostrandoIndicadorSwipe.value = false
  indicadorSwipeTexto.value = ''
  indicadorSwipeClass.value = ''
  indicadorSwipeTextoClass.value = ''
}

// ============ GESTOS TÁCTILES ============
/**
 * Configura los gestos táctiles para swipe manual
 */
function setupSwipeGestures(): (() => void) | undefined {
  // Si estamos en la vista de "encuentros", el gesto táctil
  // puede interferir con la navegación del padre
  // Podemos desactivarlo o limitarlo
  const isEncuentros = window.location.pathname.includes('/explorar/encuentros')
  
  if (isEncuentros) {
    console.log('Gestos táctiles limitados en vista de encuentros')
    return
  }
  
  const container = props.contenedorElement || document.body
  
  if (!container) {
    console.warn('No hay contenedor para configurar gestos táctiles')
    return
  }
  
  const onTouchStart = (e: TouchEvent): void => {
    if (procesandoSwipe.value) return
    
    startX = e.touches[0].clientX
    startY = e.touches[0].clientY
    isSwiping = true
    currentDeltaX = 0
    
    emit('swipe-start', 'manual')
  }
  
  const onTouchMove = (e: TouchEvent): void => {
    if (!isSwiping || procesandoSwipe.value) return
    
    const currentX = e.touches[0].clientX
    const currentY = e.touches[0].clientY
    
    const deltaX = currentX - startX
    const deltaY = currentY - startY
    
    currentDeltaX = deltaX
    
    // Solo considerar movimientos horizontales significativos
    if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 10) {
      e.preventDefault()
      
      const rotation = deltaX * 0.1
      const translateX = (deltaX / window.innerWidth) * 100
      
      emit('swipe-animation', {
        transform: `translateX(${translateX}vw) rotate(${rotation}deg)`,
        opacity: `opacity-${Math.max(70, 100 - Math.abs(deltaX) / 3)}`
      })
      
      // Mostrar indicador visual del tipo de swipe
      if (deltaX > 50 && !mostrandoIndicadorSwipe.value) {
        mostrarIndicadorSwipe('LIKE', 'green')
      } else if (deltaX < -50 && !mostrandoIndicadorSwipe.value) {
        mostrarIndicadorSwipe('DISLIKE', 'red')
      } else if (Math.abs(deltaX) < 50 && mostrandoIndicadorSwipe.value) {
        ocultarIndicadorSwipe()
      }
    }
  }
  
  const onTouchEnd = (e: TouchEvent): void => {
    if (!isSwiping || procesandoSwipe.value) return
    isSwiping = false
    
    const endX = e.changedTouches[0].clientX
    const deltaX = endX - startX
    
    // Si el desplazamiento es significativo
    if (Math.abs(deltaX) > 100) {
      if (deltaX > 0) {
        handleLike()
      } else {
        handleDislike()
      }
    } else {
      resetSwipeAnimation()
      ocultarIndicadorSwipe()
      emit('swipe-cancel', 'manual')
    }
  }
  
  // Agregar event listeners para touch
  container.addEventListener('touchstart', onTouchStart, { passive: true })
  container.addEventListener('touchmove', onTouchMove, { passive: false })
  container.addEventListener('touchend', onTouchEnd)
  
  // También para mouse (para desarrollo)
  const onMouseDown = (e: MouseEvent): void => {
    if (procesandoSwipe.value) return
    
    startX = e.clientX
    startY = e.clientY
    isSwiping = true
    currentDeltaX = 0
    
    emit('swipe-start', 'manual')
    
    const onMouseMove = (e: MouseEvent): void => {
      if (!isSwiping || procesandoSwipe.value) return
      
      const currentX = e.clientX
      const currentY = e.clientY
      const deltaX = currentX - startX
      const deltaY = currentY - startY
      
      currentDeltaX = deltaX
      
      if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 10) {
        const rotation = deltaX * 0.1
        const translateX = (deltaX / window.innerWidth) * 100
        
        emit('swipe-animation', {
          transform: `translateX(${translateX}vw) rotate(${rotation}deg)`,
          opacity: `opacity-${Math.max(70, 100 - Math.abs(deltaX) / 3)}`
        })
        
        if (deltaX > 50 && !mostrandoIndicadorSwipe.value) {
          mostrarIndicadorSwipe('LIKE', 'green')
        } else if (deltaX < -50 && !mostrandoIndicadorSwipe.value) {
          mostrarIndicadorSwipe('DISLIKE', 'red')
        } else if (Math.abs(deltaX) < 50 && mostrandoIndicadorSwipe.value) {
          ocultarIndicadorSwipe()
        }
      }
    }
    
    const onMouseUp = (e: MouseEvent): void => {
      if (!isSwiping || procesandoSwipe.value) return
      isSwiping = false
      
      const endX = e.clientX
      const deltaX = endX - startX
      
      if (Math.abs(deltaX) > 100) {
        if (deltaX > 0) {
          handleLike()
        } else {
          handleDislike()
        }
      } else {
        resetSwipeAnimation()
        ocultarIndicadorSwipe()
        emit('swipe-cancel', 'manual')
      }
      
      document.removeEventListener('mousemove', onMouseMove)
      document.removeEventListener('mouseup', onMouseUp)
    }
    
    document.addEventListener('mousemove', onMouseMove)
    document.addEventListener('mouseup', onMouseUp)
  }
  
  container.addEventListener('mousedown', onMouseDown)
  
  // Cleanup function
  return () => {
    container.removeEventListener('touchstart', onTouchStart)
    container.removeEventListener('touchmove', onTouchMove)
    container.removeEventListener('touchend', onTouchEnd)
    container.removeEventListener('mousedown', onMouseDown)
  }
}

// ============ LIFECYCLE HOOKS ============
/**
 * Inicializa los gestos táctiles al montar el componente
 */
onMounted(() => {
  // Configurar gestos táctiles
  const cleanupSwipeGestures = setupSwipeGestures()
  
  // Cleanup al desmontar
  onUnmounted(() => {
    if (cleanupSwipeGestures) {
      cleanupSwipeGestures()
    }
  })
})

// ============ EXPOSICIÓN DE MÉTODOS ============
/**
 * Expone métodos públicos para ser llamados desde el componente padre
 */
defineExpose({
  handleLike,
  handleDislike,
  mostrarIndicadorSwipe,
  ocultarIndicadorSwipe,
  resetSwipeAnimation
})
</script>

<style scoped>
/* Animaciones personalizadas */
@keyframes pulse-scale {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

.animate-pulse {
  animation: pulse-scale 1s ease-in-out infinite;
}

/* Estilos para el swipe visual */
.swipe-left {
  transform: translateX(-100vw) rotate(-10deg);
  opacity: 0;
}

.swipe-right {
  transform: translateX(100vw) rotate(10deg);
  opacity: 0;
}

.transition-swipe {
  transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275), 
              opacity 0.5s ease-in-out;
}
</style>