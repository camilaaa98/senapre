'use client'

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import DashboardLayout from '@/components/dashboard-layout'

export default function AdminDashboard() {
  const stats = [
    { icon: '👥', label: 'Aprendices Registrados', value: '1,234', color: 'from-blue-500 to-blue-600' },
    { icon: '📚', label: 'Programas Activos', value: '45', color: 'from-purple-500 to-purple-600' },
    { icon: '📋', label: 'Fichas', value: '128', color: 'from-green-500 to-green-600' },
    { icon: '📊', label: 'Asistencias Hoy', value: '892', color: 'from-orange-500 to-orange-600' },
  ]

  const recentActivities = [
    { action: 'Nuevo aprendice registrado', name: 'Juan Pérez', time: 'Hace 5 minutos' },
    { action: 'Asistencia registrada', name: 'Ficha 2558496', time: 'Hace 15 minutos' },
    { action: 'Programa actualizado', name: 'Desarrollo de Software', time: 'Hace 1 hora' },
    { action: 'Reporte generado', name: 'Asistencias Mensuales', time: 'Hace 2 horas' },
  ]

  return (
    <DashboardLayout role="admin" userName="Administrador SENA">
      <div className="space-y-6 animate-slide-in">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Panel de Administrador</h2>
          <p className="text-muted-foreground mt-1">
            Bienvenido al sistema de gestión de asistencias del SENA
          </p>
        </div>

        {/* Stats Grid */}
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          {stats.map((stat, index) => (
            <Card key={index} className="overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border-border/50">
              <CardHeader className="pb-3">
                <div className={`w-12 h-12 rounded-lg bg-gradient-to-br ${stat.color} flex items-center justify-center shadow-md mb-2`}>
                  <span className="text-2xl">{stat.icon}</span>
                </div>
                <CardDescription>{stat.label}</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-bold">{stat.value}</div>
              </CardContent>
            </Card>
          ))}
        </div>

        {/* Quick Actions */}
        <Card className="shadow-lg border-border/50">
          <CardHeader>
            <CardTitle>Acciones Rápidas</CardTitle>
            <CardDescription>Funciones más utilizadas</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid gap-3 md:grid-cols-3">
              <a
                href="/admin/aprendices"
                className="flex items-center gap-3 p-4 rounded-lg border border-border hover:bg-primary/5 hover:border-primary transition-all duration-200 group"
              >
                <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
                  <span className="text-xl">👥</span>
                </div>
                <div>
                  <p className="font-semibold text-sm">Gestionar Aprendices</p>
                  <p className="text-xs text-muted-foreground">Agregar o editar</p>
                </div>
              </a>
              <a
                href="/admin/fichas"
                className="flex items-center gap-3 p-4 rounded-lg border border-border hover:bg-primary/5 hover:border-primary transition-all duration-200 group"
              >
                <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
                  <span className="text-xl">📋</span>
                </div>
                <div>
                  <p className="font-semibold text-sm">Gestionar Fichas</p>
                  <p className="text-xs text-muted-foreground">Administrar grupos</p>
                </div>
              </a>
              <a
                href="/admin/reportes"
                className="flex items-center gap-3 p-4 rounded-lg border border-border hover:bg-primary/5 hover:border-primary transition-all duration-200 group"
              >
                <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
                  <span className="text-xl">📈</span>
                </div>
                <div>
                  <p className="font-semibold text-sm">Generar Reportes</p>
                  <p className="text-xs text-muted-foreground">Estadísticas</p>
                </div>
              </a>
            </div>
          </CardContent>
        </Card>

        {/* Recent Activity */}
        <Card className="shadow-lg border-border/50">
          <CardHeader>
            <CardTitle>Actividad Reciente</CardTitle>
            <CardDescription>Últimas acciones en el sistema</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {recentActivities.map((activity, index) => (
                <div key={index} className="flex items-center gap-4 p-3 rounded-lg hover:bg-muted/50 transition-colors">
                  <div className="w-2 h-2 rounded-full bg-primary"></div>
                  <div className="flex-1">
                    <p className="text-sm font-medium">{activity.action}</p>
                    <p className="text-xs text-muted-foreground">{activity.name}</p>
                  </div>
                  <span className="text-xs text-muted-foreground">{activity.time}</span>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  )
}
