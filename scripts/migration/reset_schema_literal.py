import sqlite3
import os

DB_PATH = 'database/Asistnet.db'

def reset_schema_literal():
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

    # Create programas_formacion (Match Excel)
    cursor.execute('''
    CREATE TABLE "programas_formacion" (
        "id_programa" INTEGER PRIMARY KEY,
        "nombre_programa" TEXT,
        "nivel_formacion" TEXT
    );
    ''')
    print("Created table programas_formacion")

    # Create fichas (Match Excel: nombre_programa as TEXT)
    cursor.execute('''
    CREATE TABLE "fichas" (
        "id_ficha" INTEGER PRIMARY KEY,
        "numero_ficha" INTEGER,
        "nombre_programa" TEXT,
        "jornada" TEXT,
        "estado" TEXT
    );
    ''')
    print("Created table fichas")

    # Create aprendices (Match Excel: id_ficha as actual number, plus extra columns)
    cursor.execute('''
    CREATE TABLE "aprendices" (
        "id_aprendiz" INTEGER PRIMARY KEY,
        "tipo_identificacion" TEXT,
        "documento" INTEGER UNIQUE,
        "nombre" TEXT,
        "apellido" TEXT,
        "correo" TEXT,
        "id_ficha" INTEGER,
        "estado" TEXT,
        "celular" INTEGER,
        "nombre_programa" TEXT,
        "nivel_formacion" TEXT
    );
    ''')
    print("Created table aprendices")

    conn.commit()
    conn.close()
    print("Schema reset to Literal Mode successfully.")

if __name__ == "__main__":
    reset_schema_literal()
