import sqlite3
import os

db_path = 'database/Asistnet.db'

def fix_unique():
    if not os.path.exists(db_path):
        print(f"DB not found at {db_path}")
        return

    conn = sqlite3.connect(db_path)
    cursor = conn.cursor()
    cursor.execute("PRAGMA foreign_keys=OFF")
    
    try:
        conn.execute("BEGIN TRANSACTION")
        
        print("Updating aprendices table constraints...")
        
        # 1. Rename existing table
        cursor.execute("ALTER TABLE aprendices RENAME TO aprendices_temp_unique")
        
        # 2. Create new table with correct constraints
        # Removed UNIQUE from documento
        # Added UNIQUE(documento, id_ficha)
        create_table_sql = """
        CREATE TABLE "aprendices" (
            "id_aprendiz" INTEGER PRIMARY KEY AUTOINCREMENT,
            "tipo_identificacion" TEXT,
            "documento" TEXT NOT NULL,
            "nombre" TEXT NOT NULL,
            "apellido" TEXT NOT NULL,
            "correo" TEXT,
            "id_ficha" INTEGER NOT NULL,
            "estado" INTEGER DEFAULT 1,
            "celular" TEXT,
            FOREIGN KEY("id_ficha") REFERENCES "fichas"("id_ficha"),
            UNIQUE("documento", "id_ficha")
        )
        """
        cursor.execute(create_table_sql)
        
        # 3. Copy data
        cursor.execute("INSERT INTO aprendices (id_aprendiz, tipo_identificacion, documento, nombre, apellido, correo, id_ficha, estado, celular) SELECT id_aprendiz, tipo_identificacion, documento, nombre, apellido, correo, id_ficha, estado, celular FROM aprendices_temp_unique")
        
        # 4. Drop old table
        cursor.execute("DROP TABLE aprendices_temp_unique")
        
        conn.commit()
        print("Tabla aprendices actualizada (UNIQUE(documento, id_ficha)).")
        
    except Exception as e:
        conn.rollback()
        print(f"Error actualizando aprendices: {e}")
    finally:
        conn.close()

if __name__ == "__main__":
    fix_unique()
