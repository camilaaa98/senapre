'use client'

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import DashboardLayout from '@/components/dashboard-layout'

export default function ReportesInstructorPage() {
  const reportes = [
    {
      icon: '📊',
      titulo: 'Reporte Mensual por Ficha',
      descripcion: 'Consolidado de asistencias del mes para cada ficha asignada',
      color: 'from-blue-500 to-blue-600',
    },
    {
      icon: '📈',
      titulo: 'Estadísticas de Asistencia',
      descripcion: 'Gráficos y análisis de tendencias de asistencia',
      color: 'from-purple-500 to-purple-600',
    },
    {
      icon: '⚠️',
      titulo: 'Alertas de Inasistencia',
      descripcion: 'Aprendices con riesgo por inasistencias frecuentes',
      color: 'from-red-500 to-red-600',
    },
    {
      icon: '🎯',
      titulo: 'Porcentaje de Cumplimiento',
      descripcion: 'Métricas de asistencia por ficha y período',
      color: 'from-green-500 to-green-600',
    },
  ]

  return (
    <DashboardLayout role="instructor" userName="Instructor SENA">
      <div className="space-y-6 animate-slide-in">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Generar Reportes</h2>
          <p className="text-muted-foreground mt-1">Crea reportes de tus fichas asignadas</p>
        </div>

        <div className="grid gap-4 md:grid-cols-2">
          {reportes.map((reporte, index) => (
            <Card key={index} className="shadow-lg border-border/50 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
              <CardHeader>
                <div className={`w-16 h-16 rounded-xl bg-gradient-to-br ${reporte.color} flex items-center justify-center shadow-md mb-3`}>
                  <span className="text-3xl">{reporte.icon}</span>
                </div>
                <CardTitle className="text-lg">{reporte.titulo}</CardTitle>
                <CardDescription>{reporte.descripcion}</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="flex gap-2">
                  <Button variant="outline" className="flex-1 hover:bg-primary/10 hover:text-primary hover:border-primary">
                    <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Ver
                  </Button>
                  <Button className="flex-1 bg-gradient-to-r from-primary to-secondary hover:opacity-90">
                    <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    PDF
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </DashboardLayout>
  )
}
