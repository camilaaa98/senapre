'use client'

import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import DashboardLayout from '@/components/dashboard-layout'

export default function RegistrarAsistenciaPage() {
  const [selectedFicha, setSelectedFicha] = useState('2558496')
  const [selectedDate] = useState(new Date().toISOString().split('T')[0])
  const [showSuccess, setShowSuccess] = useState(false)
  const [asistencias, setAsistencias] = useState([
    { id: 1, nombre: 'Juan Carlos Pérez', documento: '1234567890', presente: true },
    { id: 2, nombre: 'María Fernanda López', documento: '9876543210', presente: true },
    { id: 3, nombre: 'Carlos Andrés Gómez', documento: '5554443332', presente: false },
    { id: 4, nombre: 'Ana María Rodríguez', documento: '1112223334', presente: true },
    { id: 5, nombre: 'Luis Fernando Martínez', documento: '2223334445', presente: true },
  ])

  const toggleAsistencia = (id: number) => {
    setAsistencias(asistencias.map(a => 
      a.id === id ? { ...a, presente: !a.presente } : a
    ))
  }

  const handleGuardar = () => {
    setShowSuccess(true)
    setTimeout(() => setShowSuccess(false), 3000)
  }

  const presentesCount = asistencias.filter(a => a.presente).length
  const ausentesCount = asistencias.length - presentesCount

  return (
    <DashboardLayout role="instructor" userName="Instructor SENA">
      <div className="space-y-6 animate-slide-in">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Registrar Asistencia</h2>
          <p className="text-muted-foreground mt-1">Marca la asistencia de tus aprendices</p>
        </div>

        {showSuccess && (
          <Card className="border-success bg-success/5 animate-slide-in">
            <CardContent className="pt-6">
              <div className="flex items-center gap-3 text-success">
                <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p className="font-semibold">¡Asistencia guardada exitosamente!</p>
              </div>
            </CardContent>
          </Card>
        )}

        <Card className="shadow-lg border-border/50">
          <CardHeader>
            <CardTitle>Información de Registro</CardTitle>
            <CardDescription>Selecciona la ficha y verifica la fecha</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="ficha-registro">Número de Ficha</Label>
                <Input
                  id="ficha-registro"
                  value={selectedFicha}
                  onChange={(e) => setSelectedFicha(e.target.value)}
                  className="h-11"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="fecha-registro">Fecha</Label>
                <Input
                  id="fecha-registro"
                  type="date"
                  value={selectedDate}
                  readOnly
                  className="h-11 bg-muted"
                />
              </div>
            </div>
          </CardContent>
        </Card>

        <div className="grid gap-4 md:grid-cols-3">
          <Card className="shadow-lg border-border/50">
            <CardContent className="pt-6">
              <div className="text-center space-y-2">
                <p className="text-sm text-muted-foreground">Total Aprendices</p>
                <p className="text-4xl font-bold">{asistencias.length}</p>
              </div>
            </CardContent>
          </Card>
          <Card className="shadow-lg border-border/50">
            <CardContent className="pt-6">
              <div className="text-center space-y-2">
                <p className="text-sm text-muted-foreground">Presentes</p>
                <p className="text-4xl font-bold text-success">{presentesCount}</p>
              </div>
            </CardContent>
          </Card>
          <Card className="shadow-lg border-border/50">
            <CardContent className="pt-6">
              <div className="text-center space-y-2">
                <p className="text-sm text-muted-foreground">Ausentes</p>
                <p className="text-4xl font-bold text-destructive">{ausentesCount}</p>
              </div>
            </CardContent>
          </Card>
        </div>

        <Card className="shadow-lg border-border/50">
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle>Lista de Aprendices</CardTitle>
                <CardDescription>Marca la asistencia de cada aprendiz</CardDescription>
              </div>
              <Button
                onClick={handleGuardar}
                className="bg-gradient-to-r from-primary to-secondary hover:opacity-90 shadow-lg"
              >
                <svg className="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
                Guardar Asistencia
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              {asistencias.map((aprendiz) => (
                <div
                  key={aprendiz.id}
                  className="flex items-center justify-between p-4 rounded-lg border border-border hover:bg-muted/30 transition-colors"
                >
                  <div>
                    <p className="font-medium">{aprendiz.nombre}</p>
                    <p className="text-sm text-muted-foreground">{aprendiz.documento}</p>
                  </div>
                  <Button
                    onClick={() => toggleAsistencia(aprendiz.id)}
                    variant={aprendiz.presente ? 'default' : 'outline'}
                    className={`min-w-[120px] ${
                      aprendiz.presente
                        ? 'bg-success hover:bg-success/90 text-success-foreground'
                        : 'border-destructive text-destructive hover:bg-destructive/10'
                    }`}
                  >
                    {aprendiz.presente ? (
                      <>
                        <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                        </svg>
                        Presente
                      </>
                    ) : (
                      <>
                        <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Ausente
                      </>
                    )}
                  </Button>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  )
}
