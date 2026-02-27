import sqlite3
import os

DB_PATH = 'database/Asistnet.db'

def reset_schema():
    if not os.path.exists(DB_PATH):
        print(f"Database not found at {DB_PATH}")
        return

    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()

    # Drop existing tables
    tables = ['aprendices', 'fichas', 'programas_formacion']
    for table in tables:
        cursor.execute(f"DROP TABLE IF EXISTS {table}")
        print(f"Dropped table {table}")

    # Create programas_formacion
    cursor.execute('''
    CREATE TABLE "programas_formacion" (
        "id_programa" INTEGER PRIMARY KEY AUTOINCREMENT,
        "nombre_programa" TEXT NOT NULL,
        "nivel_formacion" TEXT
    );
    ''')
    print("Created table programas_formacion")

    # Create fichas
    cursor.execute('''
    CREATE TABLE "fichas" (
        "id_ficha" INTEGER PRIMARY KEY AUTOINCREMENT,
        "numero_ficha" INTEGER NOT NULL UNIQUE,
        "programa" INTEGER NOT NULL,
        "jornada" TEXT NOT NULL,
        "estado" TEXT DEFAULT 'ACTIVO',
        FOREIGN KEY("programa") REFERENCES "programas_formacion"("id_programa")
    );
    ''')
    print("Created table fichas")

    # Create aprendices
    cursor.execute('''
    CREATE TABLE "aprendices" (
        "id_aprendiz" INTEGER PRIMARY KEY AUTOINCREMENT,
        "tipo_identificacion" TEXT NOT NULL,
        "documento" INTEGER NOT NULL UNIQUE,
        "nombre" TEXT NOT NULL,
        "apellido" TEXT NOT NULL,
        "correo" TEXT,
        "celular" INTEGER,
        "id_ficha" INTEGER NOT NULL,
        "estado" TEXT DEFAULT 'EN FORMACION',
        FOREIGN KEY("id_ficha") REFERENCES "fichas"("id_ficha")
    );
    ''')
    print("Created table aprendices")

    conn.commit()
    conn.close()
    print("Schema reset successfully.")

if __name__ == "__main__":
    reset_schema()
