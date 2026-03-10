'use client'

import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import DashboardLayout from '@/components/dashboard-layout'

export default function ProgramasPage() {
  const [showForm, setShowForm] = useState(false)

  const programas = [
    { id: 1, nombre: 'Desarrollo de Software', codigo: 'DS-001', duracion: '24 meses', fichas: 8, aprendices: 240 },
    { id: 2, nombre: 'Diseño Gráfico', codigo: 'DG-002', duracion: '18 meses', fichas: 5, aprendices: 150 },
    { id: 3, nombre: 'Administración de Empresas', codigo: 'AE-003', duracion: '12 meses', fichas: 6, aprendices: 180 },
    { id: 4, nombre: 'Electricidad Industrial', codigo: 'EI-004', duracion: '24 meses', fichas: 4, aprendices: 120 },
  ]

  return (
    <DashboardLayout role="admin" userName="Administrador SENA">
      <div className="space-y-6 animate-slide-in">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-3xl font-bold tracking-tight">Gestionar Programas</h2>
            <p className="text-muted-foreground mt-1">Administra los programas de formación</p>
          </div>
          <Button
            onClick={() => setShowForm(!showForm)}
            className="gap-2 bg-gradient-to-r from-primary to-secondary hover:opacity-90 shadow-lg"
          >
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
            </svg>
            Nuevo Programa
          </Button>
        </div>

        {showForm && (
          <Card className="shadow-lg border-border/50 animate-slide-in">
            <CardHeader>
              <CardTitle>Crear Nuevo Programa</CardTitle>
              <CardDescription>Complete la información del programa de formación</CardDescription>
            </CardHeader>
            <CardContent>
              <form className="space-y-4">
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="nombre-programa">Nombre del Programa</Label>
                    <Input id="nombre-programa" placeholder="Desarrollo de Software" />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="codigo-programa">Código</Label>
                    <Input id="codigo-programa" placeholder="DS-001" />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="duracion">Duración</Label>
                    <Input id="duracion" placeholder="24 meses" />
                  </div>
                </div>
                <div className="flex gap-3 justify-end pt-4">
                  <Button type="button" variant="outline" onClick={() => setShowForm(false)}>
                    Cancelar
                  </Button>
                  <Button type="submit" className="bg-gradient-to-r from-primary to-secondary">
                    Guardar Programa
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>
        )}

        <div className="grid gap-4 md:grid-cols-2">
          {programas.map((programa) => (
            <Card key={programa.id} className="shadow-lg border-border/50 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
              <CardHeader>
                <div className="flex items-start justify-between">
                  <div>
                    <CardTitle className="text-xl">{programa.nombre}</CardTitle>
                    <CardDescription className="mt-1">Código: {programa.codigo}</CardDescription>
                  </div>
                  <div className="w-12 h-12 rounded-lg bg-gradient-to-br from-primary to-secondary flex items-center justify-center shadow-md">
                    <span className="text-2xl">📚</span>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                <div className="space-y-3">
                  <div className="flex items-center justify-between p-3 rounded-lg bg-muted/50">
                    <span className="text-sm text-muted-foreground">Duración</span>
                    <span className="font-semibold">{programa.duracion}</span>
                  </div>
                  <div className="flex items-center justify-between p-3 rounded-lg bg-muted/50">
                    <span className="text-sm text-muted-foreground">Fichas Activas</span>
                    <span className="font-semibold">{programa.fichas}</span>
                  </div>
                  <div className="flex items-center justify-between p-3 rounded-lg bg-muted/50">
                    <span className="text-sm text-muted-foreground">Aprendices</span>
                    <span className="font-semibold">{programa.aprendices}</span>
                  </div>
                  <div className="flex gap-2 pt-2">
                    <Button variant="outline" className="flex-1 hover:bg-primary/10 hover:text-primary hover:border-primary">
                      Editar
                    </Button>
                    <Button variant="outline" className="flex-1 hover:bg-destructive/10 hover:text-destructive hover:border-destructive">
                      Eliminar
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </DashboardLayout>
  )
}
