'use client'

import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import DashboardLayout from '@/components/dashboard-layout'

export default function FichasPage() {
  const [showForm, setShowForm] = useState(false)
  const [searchTerm, setSearchTerm] = useState('')

  const fichas = [
    { id: 1, numero: '2558496', programa: 'Desarrollo de Software', aprendices: 30, instructor: 'Carlos Méndez', estado: 'Activa' },
    { id: 2, numero: '2558497', programa: 'Diseño Gráfico', aprendices: 28, instructor: 'Ana López', estado: 'Activa' },
    { id: 3, numero: '2558498', programa: 'Administración', aprendices: 32, instructor: 'Pedro García', estado: 'Activa' },
    { id: 4, numero: '2558499', programa: 'Electricidad Industrial', aprendices: 25, instructor: 'María Rodríguez', estado: 'Finalizada' },
  ]

  const filteredFichas = fichas.filter(
    f => f.numero.includes(searchTerm) ||
         f.programa.toLowerCase().includes(searchTerm.toLowerCase()) ||
         f.instructor.toLowerCase().includes(searchTerm.toLowerCase())
  )

  return (
    <DashboardLayout role="admin" userName="Administrador SENA">
      <div className="space-y-6 animate-slide-in">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-3xl font-bold tracking-tight">Gestionar Fichas</h2>
            <p className="text-muted-foreground mt-1">Administra las fichas de formación</p>
          </div>
          <Button
            onClick={() => setShowForm(!showForm)}
            className="gap-2 bg-gradient-to-r from-primary to-secondary hover:opacity-90 shadow-lg"
          >
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
            </svg>
            Nueva Ficha
          </Button>
        </div>

        {showForm && (
          <Card className="shadow-lg border-border/50 animate-slide-in">
            <CardHeader>
              <CardTitle>Crear Nueva Ficha</CardTitle>
              <CardDescription>Complete la información de la ficha</CardDescription>
            </CardHeader>
            <CardContent>
              <form className="space-y-4">
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="numero-ficha">Número de Ficha</Label>
                    <Input id="numero-ficha" placeholder="2558496" />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="programa-ficha">Programa</Label>
                    <Input id="programa-ficha" placeholder="Desarrollo de Software" />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="instructor-ficha">Instructor Asignado</Label>
                    <Input id="instructor-ficha" placeholder="Carlos Méndez" />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="aprendices-ficha">Número de Aprendices</Label>
                    <Input id="aprendices-ficha" type="number" placeholder="30" />
                  </div>
                </div>
                <div className="flex gap-3 justify-end pt-4">
                  <Button type="button" variant="outline" onClick={() => setShowForm(false)}>
                    Cancelar
                  </Button>
                  <Button type="submit" className="bg-gradient-to-r from-primary to-secondary">
                    Guardar Ficha
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>
        )}

        <Card className="shadow-lg border-border/50">
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle>Lista de Fichas</CardTitle>
                <CardDescription>{filteredFichas.length} fichas encontradas</CardDescription>
              </div>
              <div className="w-72">
                <Input
                  placeholder="Buscar por número, programa o instructor..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="h-10"
                />
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4">
              {filteredFichas.map((ficha) => (
                <div
                  key={ficha.id}
                  className="p-4 rounded-lg border border-border hover:bg-muted/30 transition-all duration-200 hover:shadow-md"
                >
                  <div className="flex items-center justify-between">
                    <div className="space-y-1">
                      <div className="flex items-center gap-3">
                        <h3 className="text-lg font-bold">Ficha {ficha.numero}</h3>
                        <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                          ficha.estado === 'Activa' 
                            ? 'bg-success/10 text-success' 
                            : 'bg-muted text-muted-foreground'
                        }`}>
                          {ficha.estado}
                        </span>
                      </div>
                      <p className="text-sm text-muted-foreground">{ficha.programa}</p>
                      <div className="flex items-center gap-4 mt-2 text-sm">
                        <span className="flex items-center gap-1">
                          <svg className="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                          </svg>
                          {ficha.instructor}
                        </span>
                        <span className="flex items-center gap-1">
                          <svg className="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                          </svg>
                          {ficha.aprendices} aprendices
                        </span>
                      </div>
                    </div>
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" className="hover:bg-primary/10 hover:text-primary hover:border-primary">
                        Ver Detalles
                      </Button>
                      <Button variant="outline" size="sm" className="hover:bg-primary/10 hover:text-primary hover:border-primary">
                        Editar
                      </Button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  )
}
