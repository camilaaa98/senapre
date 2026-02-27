'use client'

import { useEffect, useState } from 'react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import ParticlesBackground from '@/components/particles-background'
import LoadingScreen from '@/components/loading-screen'

export default function LoginPage() {
  const [isLoading, setIsLoading] = useState(true)
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [validationState, setValidationState] = useState<{ email: boolean | null; password: boolean | null }>({
    email: null,
    password: null,
  })

  useEffect(() => {
    // Simular carga inicial del sistema
    const timer = setTimeout(() => setIsLoading(false), 2000)
    return () => clearTimeout(timer)
  }, [])

  const validateEmail = (email: string) => {
    const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
    setValidationState(prev => ({ ...prev, email: isValid }))
    return isValid
  }

  const validatePassword = (password: string) => {
    const isValid = password.length >= 6
    setValidationState(prev => ({ ...prev, password: isValid }))
    return isValid
  }

  const handleLogin = (e: React.FormEvent) => {
    e.preventDefault()
    setError('')

    const emailValid = validateEmail(email)
    const passwordValid = validatePassword(password)

    if (!emailValid || !passwordValid) {
      setError('Por favor, ingrese credenciales válidas')
      return
    }

    // Simular autenticación
    setIsLoading(true)
    setTimeout(() => {
      // Determinar el rol basado en el email
      if (email.includes('admin')) {
        window.location.href = '/admin/dashboard'
      } else if (email.includes('instructor')) {
        window.location.href = '/instructor/dashboard'
      } else {
        setError('Credenciales inválidas. Use "admin@sena.edu.co" o "instructor@sena.edu.co"')
        setIsLoading(false)
      }
    }, 1500)
  }

  if (isLoading) {
    return <LoadingScreen />
  }

  return (
    <div className="min-h-screen relative flex items-center justify-center p-4 bg-gradient-to-br from-background via-muted/30 to-background">
      <ParticlesBackground />
      
      <Card className="w-full max-w-md animate-slide-in shadow-2xl border-border/50 backdrop-blur-sm bg-card/95 z-10">
        <CardHeader className="space-y-4 text-center pb-8">
          <div className="mx-auto w-24 h-24 rounded-2xl bg-gradient-to-br from-primary to-secondary flex items-center justify-center shadow-lg animate-pulse-glow">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" className="w-16 h-16 text-primary-foreground">
              <path fill="currentColor" d="M100 20L180 60V140L100 180L20 140V60L100 20Z" opacity="0.3"/>
              <path fill="currentColor" d="M100 40L160 70V130L100 160L40 130V70L100 40Z"/>
              <circle cx="100" cy="100" r="25" fill="currentColor"/>
            </svg>
          </div>
          <div>
            <CardTitle className="text-3xl font-bold bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">
              AsistNet
            </CardTitle>
            <CardDescription className="text-base mt-2">
              Sistema de Gestión de Asistencias SENA
            </CardDescription>
          </div>
        </CardHeader>

        <CardContent>
          <form onSubmit={handleLogin} className="space-y-6">
            <div className="space-y-2">
              <Label htmlFor="email" className="text-sm font-medium">
                Correo Electrónico
              </Label>
              <div className="relative">
                <Input
                  id="email"
                  type="email"
                  placeholder="usuario@sena.edu.co"
                  value={email}
                  onChange={(e) => {
                    setEmail(e.target.value)
                    if (e.target.value) validateEmail(e.target.value)
                  }}
                  className={`h-11 transition-all duration-300 ${
                    validationState.email === true
                      ? 'border-success focus-visible:ring-success'
                      : validationState.email === false
                      ? 'border-destructive focus-visible:ring-destructive'
                      : ''
                  }`}
                  required
                />
                {validationState.email !== null && (
                  <div className="absolute right-3 top-1/2 -translate-y-1/2">
                    {validationState.email ? (
                      <svg className="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                      </svg>
                    ) : (
                      <svg className="w-5 h-5 text-destructive" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    )}
                  </div>
                )}
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="password" className="text-sm font-medium">
                Contraseña
              </Label>
              <div className="relative">
                <Input
                  id="password"
                  type="password"
                  placeholder="••••••••"
                  value={password}
                  onChange={(e) => {
                    setPassword(e.target.value)
                    if (e.target.value) validatePassword(e.target.value)
                  }}
                  className={`h-11 transition-all duration-300 ${
                    validationState.password === true
                      ? 'border-success focus-visible:ring-success'
                      : validationState.password === false
                      ? 'border-destructive focus-visible:ring-destructive'
                      : ''
                  }`}
                  required
                />
                {validationState.password !== null && (
                  <div className="absolute right-3 top-1/2 -translate-y-1/2">
                    {validationState.password ? (
                      <svg className="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                      </svg>
                    ) : (
                      <svg className="w-5 h-5 text-destructive" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    )}
                  </div>
                )}
              </div>
            </div>

            {error && (
              <div className="p-3 rounded-lg bg-destructive/10 border border-destructive/30 animate-slide-in">
                <p className="text-sm text-destructive font-medium">{error}</p>
              </div>
            )}

            <Button
              type="submit"
              className="w-full h-11 text-base font-semibold bg-gradient-to-r from-primary to-secondary hover:opacity-90 transition-all duration-300 shadow-lg hover:shadow-xl"
            >
              Iniciar Sesión
            </Button>

            <div className="text-center pt-4 border-t border-border/50">
              <p className="text-xs text-muted-foreground">
                Credenciales de prueba:<br />
                <span className="font-mono text-primary">admin@sena.edu.co</span> o{' '}
                <span className="font-mono text-primary">instructor@sena.edu.co</span>
              </p>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
