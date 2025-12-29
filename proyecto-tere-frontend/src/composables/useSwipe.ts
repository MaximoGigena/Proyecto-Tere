// src/composables/useSwipe.ts
import { ref } from 'vue'
import type { Ref } from 'vue'

export interface SwipeOptions {
  onLike?: () => void
  onDislike?: () => void
  threshold?: number
  rotationFactor?: number
}

export interface SwipeReturn {
  isSwiping: Ref<boolean>
  startX: Ref<number>
  startY: Ref<number>
  deltaX: Ref<number>
  swipeTransform: Ref<string>
  swipeClass: Ref<string>
  setupSwipe: () => (() => void) | undefined
  resetSwipe: () => void
}

export function useSwipe(
  containerRef: Ref<HTMLElement | null>,
  options: SwipeOptions = {}
): SwipeReturn {
  const {
    onLike = () => {},
    onDislike = () => {},
    threshold = 100,
    rotationFactor = 0.1
  } = options

  const isSwiping = ref<boolean>(false)
  const startX = ref<number>(0)
  const startY = ref<number>(0)
  const deltaX = ref<number>(0)
  const swipeTransform = ref<string>('')
  const swipeClass = ref<string>('')

  const handleTouchStart = (e: TouchEvent) => {
    if (isSwiping.value || !containerRef.value) return
    
    const touches = e.touches
    if (!touches || touches.length === 0) return
    
    startX.value = touches[0].clientX
    startY.value = touches[0].clientY
    isSwiping.value = true
  }

  const handleTouchMove = (e: TouchEvent) => {
    if (!isSwiping.value || !containerRef.value) return
    
    const touches = e.touches
    if (!touches || touches.length === 0) return
    
    const currentX = touches[0].clientX
    const currentY = touches[0].clientY
    
    deltaX.value = currentX - startX.value
    const deltaY = currentY - startY.value
    
    if (Math.abs(deltaX.value) > Math.abs(deltaY)) {
      e.preventDefault()
      
      const rotation = deltaX.value * rotationFactor
      const translateX = (deltaX.value / window.innerWidth) * 100
      
      swipeTransform.value = `translateX(${translateX}vw) rotate(${rotation}deg)`
      const opacity = 100 - Math.min(Math.abs(deltaX.value) / 3, 30)
      swipeClass.value = `opacity-${Math.round(opacity)}`
    }
  }

  const handleTouchEnd = () => {
    if (!isSwiping.value) return
    isSwiping.value = false
    
    if (Math.abs(deltaX.value) > threshold) {
      if (deltaX.value > 0) {
        onLike()
      } else {
        onDislike()
      }
    }
    
    resetSwipe()
  }

  const setupSwipe = (): (() => void) | undefined => {
    const container = containerRef.value
    if (!container) {
      console.warn('Contenedor no disponible para setupSwipe')
      return
    }

    container.addEventListener('touchstart', handleTouchStart, { passive: true })
    container.addEventListener('touchmove', handleTouchMove, { passive: false })
    container.addEventListener('touchend', handleTouchEnd)

    return () => {
      container.removeEventListener('touchstart', handleTouchStart)
      container.removeEventListener('touchmove', handleTouchMove)
      container.removeEventListener('touchend', handleTouchEnd)
    }
  }

  const resetSwipe = () => {
    swipeTransform.value = ''
    swipeClass.value = ''
    deltaX.value = 0
  }

  return {
    isSwiping,
    startX,
    startY,
    deltaX,
    swipeTransform,
    swipeClass,
    setupSwipe,
    resetSwipe
  }
}