'use client'

import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import ParticlesBackground from '@/components/particles-background'

interface DashboardLayoutProps {
  children: React.ReactNode
  role: 'admin' | 'instructor'
  userName: string
}

export default function DashboardLayout({ children, role, userName }: DashboardLayoutProps) {
  const [isSidebarOpen, setIsSidebarOpen] = useState(true)

  const handleLogout = () => {
    window.location.href = '/'
  }

  const adminMenuItems = [
    { icon: '👥', label: 'Gestionar Aprendices', href: '/admin/aprendices' },
    { icon: '📚', label: 'Gestionar Programas', href: '/admin/programas' },
    { icon: '📋', label: 'Gestionar Fichas', href: '/admin/fichas' },
    { icon: '👤', label: 'Registrar Usuario', href: '/admin/usuarios' },
    { icon: '📊', label: 'Consultar Asistencias', href: '/admin/asistencias' },
    { icon: '📈', label: 'Generar Reportes', href: '/admin/reportes' },
  ]

  const instructorMenuItems = [
    { icon: '✅', label: 'Registrar Asistencia', href: '/instructor/registrar' },
    { icon: '📊', label: 'Consultar Asistencias', href: '/instructor/asistencias' },
    { icon: '📈', label: 'Generar Reportes', href: '/instructor/reportes' },
  ]

  const menuItems = role === 'admin' ? adminMenuItems : instructorMenuItems

  return (
    <div className="min-h-screen relative bg-gradient-to-br from-background via-muted/20 to-background">
      <ParticlesBackground />
      
      {/* Header */}
      <header className="sticky top-0 z-50 w-full border-b border-border/50 bg-card/95 backdrop-blur supports-[backdrop-filter]:bg-card/80 shadow-sm">
        <div className="container flex h-16 items-center justify-between px-4">
          <div className="flex items-center gap-4">
            <Button
              variant="ghost"
              size="icon"
              onClick={() => setIsSidebarOpen(!isSidebarOpen)}
              className="lg:hidden"
            >
              <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </Button>
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-primary to-secondary flex items-center justify-center shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" className="w-6 h-6 text-primary-foreground">
                  <path fill="currentColor" d="M100 40L160 70V130L100 160L40 130V70L100 40Z"/>
                </svg>
              </div>
              <div>
                <h1 className="text-xl font-bold bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">
                  AsistNet
                </h1>
                <p className="text-xs text-muted-foreground">
                  {role === 'admin' ? 'Panel de Administrador' : 'Panel de Instructor'}
                </p>
              </div>
            </div>
          </div>

          <div className="flex items-center gap-4">
            <div className="hidden md:flex items-center gap-2 px-4 py-2 rounded-lg bg-muted/50">
              <svg className="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <span className="text-sm font-medium">{userName}</span>
            </div>
            <Button
              variant="outline"
              size="sm"
              onClick={handleLogout}
              className="gap-2 hover:bg-destructive hover:text-destructive-foreground hover:border-destructive transition-colors"
            >
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              Cerrar Sesión
            </Button>
          </div>
        </div>
      </header>

      <div className="container flex gap-6 p-4 lg:p-6">
        {/* Sidebar */}
        <aside
          className={`${
            isSidebarOpen ? 'translate-x-0' : '-translate-x-full'
          } fixed lg:sticky lg:translate-x-0 top-16 left-0 z-40 h-[calc(100vh-4rem)] w-64 transition-transform duration-300 lg:top-[4.5rem]`}
        >
          <Card className="h-full p-4 bg-card/95 backdrop-blur shadow-lg border-border/50">
            <nav className="space-y-2">
              {menuItems.map((item, index) => (
                <a
                  key={index}
                  href={item.href}
                  className="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all duration-200 hover:bg-primary/10 hover:text-primary hover:shadow-md group"
                >
                  <span className="text-xl group-hover:scale-110 transition-transform">{item.icon}</span>
                  <span>{item.label}</span>
                </a>
              ))}
            </nav>
          </Card>
        </aside>

        {/* Main Content */}
        <main className="flex-1 z-10 lg:ml-0 ml-0">
          {children}
        </main>
      </div>

      {/* Overlay para móvil */}
      {isSidebarOpen && (
        <div
          className="fixed inset-0 bg-background/80 backdrop-blur-sm z-30 lg:hidden"
          onClick={() => setIsSidebarOpen(false)}
        />
      )}
    </div>
  )
}
