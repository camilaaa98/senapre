import sqlite3
import os

db_path = 'database/Asistnet.db'

def fix():
    if not os.path.exists(db_path):
        print(f"DB not found at {db_path}")
        return

    conn = sqlite3.connect(db_path)
    cursor = conn.cursor()
    cursor.execute("PRAGMA foreign_keys=OFF")
    
    try:
        conn.execute("BEGIN TRANSACTION")
        
        # 1. Fix Asistencias
        print("Fixing asistencias...")
        cursor.execute("CREATE TABLE IF NOT EXISTS asistencias_new (id_asistencia INTEGER PRIMARY KEY AUTOINCREMENT, id_aprendiz INTEGER NOT NULL, id_usuario INTEGER NOT NULL, fecha TEXT NOT NULL, hora_entrada TEXT NOT NULL, hora_salida TEXT, tipo TEXT NOT NULL CHECK (tipo IN ('entrada','salida','completa')), observaciones TEXT, FOREIGN KEY (id_aprendiz) REFERENCES aprendices(id_aprendiz), FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario))")
        
        # Copy data if asistencias exists
        cursor.execute("SELECT name FROM sqlite_master WHERE type='table' AND name='asistencias'")
        if cursor.fetchone():
            # Check columns to match
            cursor.execute("PRAGMA table_info(asistencias)")
            old_cols = [c[1] for c in cursor.fetchall()]
            new_cols = ['id_asistencia', 'id_aprendiz', 'id_usuario', 'fecha', 'hora_entrada', 'hora_salida', 'tipo', 'observaciones']
            common = [c for c in old_cols if c in new_cols]
            cols_str = ", ".join(common)
            
            cursor.execute(f"INSERT INTO asistencias_new ({cols_str}) SELECT {cols_str} FROM asistencias")
            cursor.execute("DROP TABLE asistencias")
            
        cursor.execute("ALTER TABLE asistencias_new RENAME TO asistencias")
        
        # 2. Fix Aprendices
        print("Fixing aprendices...")
        # Check if aprendices exists
        cursor.execute("SELECT name FROM sqlite_master WHERE type='table' AND name='aprendices'")
        if cursor.fetchone():
             # Rename to temp
             cursor.execute("ALTER TABLE aprendices RENAME TO aprendices_temp")
        
        # Create correct table
        cursor.execute("""
        CREATE TABLE "aprendices" (
            "id_aprendiz" INTEGER PRIMARY KEY AUTOINCREMENT,
            "tipo_identificacion" TEXT,
            "documento" TEXT NOT NULL UNIQUE,
            "nombre" TEXT NOT NULL,
            "apellido" TEXT NOT NULL,
            "correo" TEXT,
            "id_ficha" INTEGER NOT NULL,
            "estado" INTEGER DEFAULT 1,
            "celular" TEXT,
            FOREIGN KEY("id_ficha") REFERENCES "fichas"("id_ficha")
        )
        """)
        
        # Copy data
        cursor.execute("SELECT name FROM sqlite_master WHERE type='table' AND name='aprendices_temp'")
        if cursor.fetchone():
             # Get columns from temp
             cursor.execute("PRAGMA table_info(aprendices_temp)")
             cols = [c[1] for c in cursor.fetchall()]
             # Intersect with new columns
             new_cols = ['id_aprendiz', 'tipo_identificacion', 'documento', 'nombre', 'apellido', 'correo', 'id_ficha', 'estado', 'celular']
             common_cols = [c for c in cols if c in new_cols]
             cols_str = ", ".join(common_cols)
             
             if common_cols:
                cursor.execute(f"INSERT INTO aprendices ({cols_str}) SELECT {cols_str} FROM aprendices_temp")
             
             cursor.execute("DROP TABLE aprendices_temp")

        conn.commit()
        print("Schema fixed.")
        
    except Exception as e:
        conn.rollback()
        print(f"Error: {e}")
    finally:
        conn.close()

if __name__ == "__main__":
    fix()
