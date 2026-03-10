'use client'

import { useState } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import DashboardLayout from '@/components/dashboard-layout'

export default function AsistenciasAdminPage() {
  const [selectedFicha, setSelectedFicha] = useState('2558496')
  const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0])

  const asistencias = [
    { nombre: 'Juan Carlos Pérez', documento: '1234567890', estado: 'Presente', hora: '07:45 AM' },
    { nombre: 'María Fernanda López', documento: '9876543210', estado: 'Presente', hora: '07:50 AM' },
    { nombre: 'Carlos Andrés Gómez', documento: '5554443332', estado: 'Ausente', hora: '-' },
    { nombre: 'Ana María Rodríguez', documento: '1112223334', estado: 'Presente', hora: '08:00 AM' },
    { nombre: 'Luis Fernando Martínez', documento: '2223334445', estado: 'Tarde', hora: '08:15 AM' },
  ]

  const stats = [
    { label: 'Total Aprendices', value: '30', color: 'from-blue-500 to-blue-600' },
    { label: 'Presentes', value: '24', color: 'from-green-500 to-green-600' },
    { label: 'Ausentes', value: '4', color: 'from-red-500 to-red-600' },
    { label: 'Tardes', value: '2', color: 'from-yellow-500 to-yellow-600' },
  ]

  return (
    <DashboardLayout role="admin" userName="Administrador SENA">
      <div className="space-y-6 animate-slide-in">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Consultar Asistencias</h2>
          <p className="text-muted-foreground mt-1">Revisa el registro de asistencias por ficha y fecha</p>
        </div>

        <Card className="shadow-lg border-border/50">
          <CardHeader>
            <CardTitle>Filtros de Búsqueda</CardTitle>
            <CardDescription>Selecciona la ficha y fecha para consultar</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="ficha">Número de Ficha</Label>
                <Input
                  id="ficha"
                  value={selectedFicha}
                  onChange={(e) => setSelectedFicha(e.target.value)}
                  placeholder="2558496"
                  className="h-11"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="fecha">Fecha</Label>
                <Input
                  id="fecha"
                  type="date"
                  value={selectedDate}
                  onChange={(e) => setSelectedDate(e.target.value)}
                  className="h-11"
                />
              </div>
            </div>
          </CardContent>
        </Card>

        <div className="grid gap-4 md:grid-cols-4">
          {stats.map((stat, index) => (
            <Card key={index} className="shadow-lg border-border/50">
              <CardContent className="pt-6">
                <div className="text-center space-y-2">
                  <p className="text-sm text-muted-foreground">{stat.label}</p>
                  <p className="text-4xl font-bold bg-gradient-to-r ${stat.color} bg-clip-text text-transparent">
                    {stat.value}
                  </p>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>

        <Card className="shadow-lg border-border/50">
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle>Registro de Asistencia</CardTitle>
                <CardDescription>Ficha {selectedFicha} - {new Date(selectedDate).toLocaleDateString('es-ES', { dateStyle: 'long' })}</CardDescription>
              </div>
              <Button className="bg-gradient-to-r from-primary to-secondary hover:opacity-90">
                Exportar PDF
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div className="relative overflow-x-auto">
              <table className="w-full text-sm">
                <thead className="text-xs uppercase bg-muted/50">
                  <tr>
                    <th className="px-6 py-3 text-left">Aprendiz</th>
                    <th className="px-6 py-3 text-left">Documento</th>
                    <th className="px-6 py-3 text-center">Estado</th>
                    <th className="px-6 py-3 text-center">Hora de Llegada</th>
                  </tr>
                </thead>
                <tbody>
                  {asistencias.map((asistencia, index) => (
                    <tr key={index} className="border-b border-border hover:bg-muted/30 transition-colors">
                      <td className="px-6 py-4 font-medium">{asistencia.nombre}</td>
                      <td className="px-6 py-4">{asistencia.documento}</td>
                      <td className="px-6 py-4 text-center">
                        <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                          asistencia.estado === 'Presente'
                            ? 'bg-success/10 text-success'
                            : asistencia.estado === 'Ausente'
                            ? 'bg-destructive/10 text-destructive'
                            : 'bg-yellow-500/10 text-yellow-600'
                        }`}>
                          {asistencia.estado}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-center">{asistencia.hora}</td>
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
