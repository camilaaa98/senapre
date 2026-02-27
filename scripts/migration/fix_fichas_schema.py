import sqlite3
import os

db_path = 'database/Asistnet.db'

def fix_fichas():
    if not os.path.exists(db_path):
        print(f"DB not found at {db_path}")
        return

    conn = sqlite3.connect(db_path)
    cursor = conn.cursor()
    cursor.execute("PRAGMA foreign_keys=OFF")
    
    try:
        conn.execute("BEGIN TRANSACTION")
        
        print("Updating fichas table...")
        
        # 1. Rename existing table
        cursor.execute("ALTER TABLE fichas RENAME TO fichas_old")
        
        # 2. Create new table with estado as TEXT
        # Note: Added DEFAULT 'ACTIVO' for estado if not specified
        create_table_sql = """
        CREATE TABLE fichas (
            id_ficha INTEGER PRIMARY KEY AUTOINCREMENT,
            numero_ficha TEXT NOT NULL UNIQUE,
            id_programa INTEGER NOT NULL,
            jornada TEXT NOT NULL,
            estado TEXT DEFAULT 'ACTIVO',
            FOREIGN KEY (id_programa) REFERENCES programas_formacion(id_programa)
        )
        """
        cursor.execute(create_table_sql)
        
        # 3. Copy data
        # We need to cast old integer state to text if needed, or map 1->'ACTIVO'
        # Let's just cast to text for now to preserve value, or default to 'ACTIVO'
        
        cursor.execute("INSERT INTO fichas (id_ficha, numero_ficha, id_programa, jornada, estado) SELECT id_ficha, numero_ficha, id_programa, jornada, CAST(estado AS TEXT) FROM fichas_old")
        
        # 4. Drop old table
        cursor.execute("DROP TABLE fichas_old")
        
        conn.commit()
        print("Tabla fichas actualizada (estado -> TEXT).")
        
    except Exception as e:
        conn.rollback()
        print(f"Error actualizando fichas: {e}")
    finally:
        conn.close()

if __name__ == "__main__":
    fix_fichas()
