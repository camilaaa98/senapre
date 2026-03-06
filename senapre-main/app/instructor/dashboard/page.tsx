'use client'

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import DashboardLayout from '@/components/dashboard-layout'

export default function InstructorDashboard() {
  const stats = [
    { icon: '📋', label: 'Fichas Asignadas', value: '3', color: 'from-blue-500 to-blue-600' },
    { icon: '👥', label: 'Total Aprendices', value: '87', color: 'from-purple-500 to-purple-600' },
    { icon: '✅', label: 'Asistencias Hoy', value: '78', color: 'from-green-500 to-green-600' },
    { icon: '⚠️', label: 'Ausencias Hoy', value: '9', color: 'from-orange-500 to-orange-600' },
  ]

  const fichasAsignadas = [
    { numero: '2558496', programa: 'Desarrollo de Software', aprendices: 30, asistenciaHoy: 28 },
    { numero: '2558501', programa: 'Desarrollo de Software', aprendices: 28, asistenciaHoy: 25 },
    { numero: '2558502', programa: 'Programación Web', aprendices: 29, asistenciaHoy: 25 },
  ]

  return (
    <DashboardLayout role="instructor" userName="Instructor SENA">
      <div className="space-y-6 animate-slide-in">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Panel de Instructor</h2>
          <p className="text-muted-foreground mt-1">
            Bienvenido al sistema de gestión de asistencias
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
            <CardDescription>Funciones principales</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid gap-3 md:grid-cols-3">
              <a
                href="/instructor/registrar"
                className="flex items-center gap-3 p-4 rounded-lg border border-border hover:bg-primary/5 hover:border-primary transition-all duration-200 group"
              >
                <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
                  <span className="text-xl">✅</span>
                </div>
                <div>
                  <p className="font-semibold text-sm">Registrar Asistencia</p>
                  <p className="text-xs text-muted-foreground">Marcar presentes</p>
                </div>
              </a>
              <a
                href="/instructor/asistencias"
                className="flex items-center gap-3 p-4 rounded-lg border border-border hover:bg-primary/5 hover:border-primary transition-all duration-200 group"
              >
                <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
                  <span className="text-xl">📊</span>
                </div>
                <div>
                  <p className="font-semibold text-sm">Consultar Asistencias</p>
                  <p className="text-xs text-muted-foreground">Ver registros</p>
                </div>
              </a>
              <a
                href="/instructor/reportes"
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

        {/* Fichas Asignadas */}
        <Card className="shadow-lg border-border/50">
          <CardHeader>
            <CardTitle>Mis Fichas Asignadas</CardTitle>
            <CardDescription>Fichas bajo tu responsabilidad</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {fichasAsignadas.map((ficha, index) => (
                <div key={index} className="p-4 rounded-lg border border-border hover:bg-muted/30 transition-colors">
                  <div className="flex items-center justify-between">
                    <div>
                      <h3 className="text-lg font-bold">Ficha {ficha.numero}</h3>
                      <p className="text-sm text-muted-foreground">{ficha.programa}</p>
                      <div className="flex items-center gap-4 mt-2 text-sm">
                        <span className="flex items-center gap-1">
                          <svg className="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                          </svg>
                          {ficha.aprendices} aprendices
                        </span>
                        <span className="flex items-center gap-1">
                          <svg className="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                          {ficha.asistenciaHoy} presentes hoy
                        </span>
                      </div>
                    </div>
                    <div className="text-right">
                      <div className="text-3xl font-bold text-primary">
                        {Math.round((ficha.asistenciaHoy / ficha.aprendices) * 100)}%
                      </div>
                      <p className="text-xs text-muted-foreground">Asistencia</p>
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
