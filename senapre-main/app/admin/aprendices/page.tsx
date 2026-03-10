'use client'

import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import DashboardLayout from '@/components/dashboard-layout'

export default function AprendicesPage() {
  const [searchTerm, setSearchTerm] = useState('')
  const [showForm, setShowForm] = useState(false)

  const aprendices = [
    { id: 1, nombre: 'Juan Carlos Pérez', documento: '1234567890', ficha: '2558496', programa: 'Desarrollo de Software', estado: 'Activo' },
    { id: 2, nombre: 'María Fernanda López', documento: '9876543210', ficha: '2558497', programa: 'Diseño Gráfico', estado: 'Activo' },
    { id: 3, nombre: 'Carlos Andrés Gómez', documento: '5554443332', ficha: '2558496', programa: 'Desarrollo de Software', estado: 'Activo' },
    { id: 4, nombre: 'Ana María Rodríguez', documento: '1112223334', ficha: '2558498', programa: 'Administración', estado: 'Inactivo' },
  ]

  const filteredAprendices = aprendices.filter(
    a => a.nombre.toLowerCase().includes(searchTerm.toLowerCase()) ||
         a.documento.includes(searchTerm) ||
         a.ficha.includes(searchTerm)
  )

  return (
    <DashboardLayout role="admin" userName="Administrador SENA">
      <div className="space-y-6 animate-slide-in">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-3xl font-bold tracking-tight">Gestionar Aprendices</h2>
            <p className="text-muted-foreground mt-1">Administra los aprendices registrados en el sistema</p>
          </div>
          <Button
            onClick={() => setShowForm(!showForm)}
            className="gap-2 bg-gradient-to-r from-primary to-secondary hover:opacity-90 shadow-lg"
          >
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
            </svg>
            Nuevo Aprendiz
          </Button>
        </div>

        {showForm && (
          <Card className="shadow-lg border-border/50 animate-slide-in">
            <CardHeader>
              <CardTitle>Registrar Nuevo Aprendiz</CardTitle>
              <CardDescription>Complete la información del aprendiz</CardDescription>
            </CardHeader>
            <CardContent>
              <form className="space-y-4">
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="nombre">Nombre Completo</Label>
                    <Input id="nombre" placeholder="Juan Carlos Pérez" />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="documento">Documento de Identidad</Label>
                    <Input id="documento" placeholder="1234567890" />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="ficha">Ficha</Label>
                    <Input id="ficha" placeholder="2558496" />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="programa">Programa</Label>
                    <Input id="programa" placeholder="Desarrollo de Software" />
                  </div>
                </div>
                <div className="flex gap-3 justify-end pt-4">
                  <Button type="button" variant="outline" onClick={() => setShowForm(false)}>
                    Cancelar
                  </Button>
                  <Button type="submit" className="bg-gradient-to-r from-primary to-secondary">
                    Guardar Aprendiz
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
                <CardTitle>Lista de Aprendices</CardTitle>
                <CardDescription>{filteredAprendices.length} aprendices encontrados</CardDescription>
              </div>
              <div className="w-72">
                <Input
                  placeholder="Buscar por nombre, documento o ficha..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="h-10"
                />
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <div className="relative overflow-x-auto">
              <table className="w-full text-sm">
                <thead className="text-xs uppercase bg-muted/50">
                  <tr>
                    <th className="px-6 py-3 text-left">Nombre</th>
                    <th className="px-6 py-3 text-left">Documento</th>
                    <th className="px-6 py-3 text-left">Ficha</th>
                    <th className="px-6 py-3 text-left">Programa</th>
                    <th className="px-6 py-3 text-left">Estado</th>
                    <th className="px-6 py-3 text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredAprendices.map((aprendiz) => (
                    <tr key={aprendiz.id} className="border-b border-border hover:bg-muted/30 transition-colors">
                      <td className="px-6 py-4 font-medium">{aprendiz.nombre}</td>
                      <td className="px-6 py-4">{aprendiz.documento}</td>
                      <td className="px-6 py-4">{aprendiz.ficha}</td>
                      <td className="px-6 py-4">{aprendiz.programa}</td>
                      <td className="px-6 py-4">
                        <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                          aprendiz.estado === 'Activo' 
                            ? 'bg-success/10 text-success' 
                            : 'bg-muted text-muted-foreground'
                        }`}>
                          {aprendiz.estado}
                        </span>
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center justify-center gap-2">
                          <Button variant="ghost" size="sm" className="hover:text-primary">
                            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                          </Button>
                          <Button variant="ghost" size="sm" className="hover:text-destructive">
                            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                          </Button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  )
}
