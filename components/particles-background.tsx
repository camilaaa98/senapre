'use client'

import { useEffect, useRef } from 'react'

export default function ParticlesBackground() {
  const canvasRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (!canvasRef.current) return

    const container = canvasRef.current
    const particleCount = 30

    // Crear partículas
    for (let i = 0; i < particleCount; i++) {
      const particle = document.createElement('div')
      particle.className = 'particle'
      
      const size = Math.random() * 60 + 20
      const startX = Math.random() * 100
      const startY = Math.random() * 100
      const delay = Math.random() * 8
      const duration = Math.random() * 8 + 8

      particle.style.width = `${size}px`
      particle.style.height = `${size}px`
      particle.style.left = `${startX}%`
      particle.style.top = `${startY}%`
      particle.style.animationDelay = `${delay}s`
      particle.style.animationDuration = `${duration}s`

      container.appendChild(particle)
    }

    return () => {
      container.innerHTML = ''
    }
  }, [])

  return <div ref={canvasRef} className="particles-bg" />
}
