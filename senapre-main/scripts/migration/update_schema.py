import sqlite3
import os

db_path = 'database/Asistnet.db'

def update_schema():
    conn = sqlite3.connect(db_path)
    cursor = conn.cursor()

    try:
        # 1. Check if table needs update
        cursor.execute("PRAGMA table_info(aprendices)")
        columns = cursor.fetchall()
        
        has_celular = False
        apellido_is_text = False
        
        for col in columns:
            # col structure: (cid, name, type, notnull, dflt_value, pk)
            name = col[1]
            dtype = col[2]
            
            if name == 'celular':
                has_celular = True
            if name == 'apellido' and 'TEXT' in dtype.upper():
                apellido_is_text = True
                
        if has_celular and apellido_is_text:
            print("El esquema ya está actualizado.")
            return

        print("Actualizando esquema...")
        
        # 2. Rename existing table
        cursor.execute("ALTER TABLE aprendices RENAME TO aprendices_old")
        
        # 3. Create new table
        create_table_sql = """
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
        """
        cursor.execute(create_table_sql)
        
        # 4. Copy data
        # We need to list columns common to both to copy
        # Old columns: id_aprendiz, tipo_identificacion, documento, nombre, apellido, correo, id_ficha, estado
        # New columns: same + celular
        
        cursor.execute("INSERT INTO aprendices (id_aprendiz, tipo_identificacion, documento, nombre, apellido, correo, id_ficha, estado) SELECT id_aprendiz, tipo_identificacion, documento, nombre, apellido, correo, id_ficha, estado FROM aprendices_old")
        
        # 5. Drop old table
        cursor.execute("DROP TABLE aprendices_old")
        
        conn.commit()
        print("Esquema actualizado correctamente.")
        
    except Exception as e:
        conn.rollback()
        print(f"Error actualizando el esquema: {e}")
    finally:
        conn.close()

if __name__ == "__main__":
    if os.path.exists(db_path):
        update_schema()
    else:
        print(f"No se encontró la base de datos en {db_path}")
