'use client'

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import DashboardLayout from '@/components/dashboard-layout'

export default function ReportesAdminPage() {
  const reportes = [
    {
      icon: '📊',
      titulo: 'Reporte de Asistencias Mensual',
      descripcion: 'Consolidado de asistencias del mes actual por programa y ficha',
      color: 'from-blue-500 to-blue-600',
    },
    {
      icon: '📈',
      titulo: 'Estadísticas por Programa',
      descripcion: 'Análisis comparativo de asistencias entre programas de formación',
      color: 'from-purple-500 to-purple-600',
    },
    {
      icon: '👥',
      titulo: 'Reporte de Aprendices',
      descripcion: 'Listado completo de aprendices con historial de asistencias',
      color: 'from-green-500 to-green-600',
    },
    {
      icon: '📋',
      titulo: 'Reporte por Ficha',
      descripcion: 'Asistencias detalladas de una ficha específica',
      color: 'from-orange-500 to-orange-600',
    },
    {
      icon: '⚠️',
      titulo: 'Alertas de Inasistencia',
      descripcion: 'Aprendices con alto índice de inasistencias',
      color: 'from-red-500 to-red-600',
    },
    {
      icon: '🎯',
      titulo: 'Cumplimiento de Objetivos',
      descripcion: 'Porcentaje de asistencia por instructor y programa',
      color: 'from-teal-500 to-teal-600',
    },
  ]

  return (
    <DashboardLayout role="admin" userName="Administrador SENA">
      <div className="space-y-6 animate-slide-in">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Generar Reportes</h2>
          <p className="text-muted-foreground mt-1">Crea y descarga reportes de asistencias</p>
        </div>

        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
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

        <Card className="shadow-lg border-border/50">
          <CardHeader>
            <CardTitle>Reportes Recientes</CardTitle>
            <CardDescription>Últimos reportes generados</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {[
                { nombre: 'Asistencias Mensuales - Octubre 2024', fecha: 'Hace 2 horas', tipo: 'PDF' },
                { nombre: 'Estadísticas por Programa', fecha: 'Hace 1 día', tipo: 'PDF' },
                { nombre: 'Reporte de Aprendices - Ficha 2558496', fecha: 'Hace 3 días', tipo: 'PDF' },
              ].map((reporte, index) => (
                <div key={index} className="flex items-center justify-between p-4 rounded-lg border border-border hover:bg-muted/30 transition-colors">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                      <svg className="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                    <div>
                      <p className="font-medium">{reporte.nombre}</p>
                      <p className="text-xs text-muted-foreground">{reporte.fecha}</p>
                    </div>
                  </div>
                  <Button variant="outline" size="sm" className="hover:bg-primary/10 hover:text-primary hover:border-primary">
                    <svg className="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Descargar
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
