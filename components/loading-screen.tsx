'use client'

export default function LoadingScreen() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-background via-muted/30 to-background">
      <div className="text-center space-y-6 animate-slide-in">
        <div className="mx-auto w-24 h-24 rounded-2xl bg-gradient-to-br from-primary to-secondary flex items-center justify-center shadow-2xl animate-pulse-glow">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" className="w-16 h-16 text-primary-foreground">
            <path fill="currentColor" d="M100 20L180 60V140L100 180L20 140V60L100 20Z" opacity="0.3"/>
            <path fill="currentColor" d="M100 40L160 70V130L100 160L40 130V70L100 40Z"/>
            <circle cx="100" cy="100" r="25" fill="currentColor"/>
          </svg>
        </div>
        <div>
          <h2 className="text-2xl font-bold text-foreground">AsistNet</h2>
          <p className="text-muted-foreground mt-2">Cargando sistema...</p>
        </div>
        <div className="spinner mx-auto"></div>
      </div>
    </div>
  )
}
