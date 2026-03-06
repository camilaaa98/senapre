import pandas as pd
import sqlite3
import os

EXCEL_PATH = 'database/Aprendices.xlsx'
DB_PATH = 'database/Asistnet.db'
LOG_PATH = 'migration_errors.txt'

def migrate_data():
    if not os.path.exists(EXCEL_PATH):
        print(f"Excel file not found at {EXCEL_PATH}")
        return

    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()
    
    # Drop tables
    cursor.execute("DROP TABLE IF EXISTS aprendices")
    cursor.execute("DROP TABLE IF EXISTS fichas")
    cursor.execute("DROP TABLE IF EXISTS programas_formacion")
    
    # Recreate Schema
    cursor.execute('''CREATE TABLE "programas_formacion" ("id_programa" INTEGER PRIMARY KEY AUTOINCREMENT, "nombre_programa" TEXT NOT NULL, "nivel_formacion" TEXT)''')
    cursor.execute('''CREATE TABLE "fichas" ("id_ficha" INTEGER PRIMARY KEY AUTOINCREMENT, "numero_ficha" INTEGER NOT NULL UNIQUE, "programa" INTEGER NOT NULL, "jornada" TEXT NOT NULL, "estado" TEXT DEFAULT 'ACTIVO', FOREIGN KEY("programa") REFERENCES "programas_formacion"("id_programa"))''')
    cursor.execute('''CREATE TABLE "aprendices" ("id_aprendiz" INTEGER PRIMARY KEY AUTOINCREMENT, "tipo_identificacion" TEXT NOT NULL, "documento" INTEGER NOT NULL UNIQUE, "nombre" TEXT NOT NULL, "apellido" TEXT NOT NULL, "correo" TEXT, "celular" INTEGER, "id_ficha" INTEGER NOT NULL, "estado" TEXT DEFAULT 'EN FORMACION', FOREIGN KEY("id_ficha") REFERENCES "fichas"("id_ficha"))''')

    log_file = open(LOG_PATH, 'w', encoding='utf-8')

    try:
        xl = pd.ExcelFile(EXCEL_PATH)
        
        # --- 1. Programas de Formacion ---
        print("Migrating Programas de Formacion...")
        df_programas = pd.read_excel(xl, 'programas_formacion')
        program_map = {} # Name -> ID

        for _, row in df_programas.iterrows():
            nombre = row['nombre_programa'].strip()
            nivel = row['nivel_formacion']
            
            cursor.execute("INSERT INTO programas_formacion (nombre_programa, nivel_formacion) VALUES (?, ?)", (nombre, nivel))
            program_id = cursor.lastrowid
            program_map[nombre] = program_id
        
        # Ensure a fallback program exists
        fallback_program_name = "PROGRAMA NO DEFINIDO"
        if fallback_program_name not in program_map:
            cursor.execute("INSERT INTO programas_formacion (nombre_programa, nivel_formacion) VALUES (?, ?)", (fallback_program_name, "NO DEFINIDO"))
            program_map[fallback_program_name] = cursor.lastrowid
        
        fallback_program_id = program_map[fallback_program_name]
        print(f"Inserted {len(program_map)} programs.")

        # --- 2. Fichas ---
        print("Migrating Fichas...")
        df_fichas = pd.read_excel(xl, 'fichas')
        ficha_map = {} # Numero Ficha -> ID Ficha (DB)

        for _, row in df_fichas.iterrows():
            numero_ficha = row['numero_ficha']
            nombre_programa = row['nombre_programa'].strip()
            jornada = row['jornada']
            estado = row['estado']

            program_id = program_map.get(nombre_programa)
            if not program_id:
                msg = f"Ficha Warning: Program '{nombre_programa}' not found for ficha {numero_ficha}. Using fallback."
                print(msg)
                log_file.write(msg + "\n")
                program_id = fallback_program_id

            try:
                cursor.execute("INSERT INTO fichas (numero_ficha, programa, jornada, estado) VALUES (?, ?, ?, ?)", 
                               (numero_ficha, program_id, jornada, estado))
                ficha_id = cursor.lastrowid
                ficha_map[numero_ficha] = ficha_id
            except sqlite3.IntegrityError:
                # Handle duplicate ficha: fetch existing ID
                cursor.execute("SELECT id_ficha FROM fichas WHERE numero_ficha = ?", (numero_ficha,))
                existing_id = cursor.fetchone()
                if existing_id:
                    ficha_map[numero_ficha] = existing_id[0]
                    log_file.write(f"Ficha Info: Duplicate ficha {numero_ficha} handled.\n")

        print(f"Inserted/Mapped {len(ficha_map)} fichas.")

        # --- 3. Aprendices ---
        print("Migrating Aprendices...")
        df_aprendices = pd.read_excel(xl, 'aprendices')
        aprendices_count = 0

        for _, row in df_aprendices.iterrows():
            ficha_number_excel = row['id_ficha']
            
            real_ficha_id = ficha_map.get(ficha_number_excel)
            
            # If ficha not found, create it dynamically
            if not real_ficha_id:
                msg = f"Aprendiz Info: Ficha {ficha_number_excel} missing. Creating placeholder."
                # print(msg)
                log_file.write(msg + "\n")
                
                try:
                    cursor.execute("INSERT INTO fichas (numero_ficha, programa, jornada, estado) VALUES (?, ?, ?, ?)", 
                                   (ficha_number_excel, fallback_program_id, "NO DEFINIDA", "EN FORMACION"))
                    real_ficha_id = cursor.lastrowid
                    ficha_map[ficha_number_excel] = real_ficha_id
                except sqlite3.IntegrityError:
                     # Should not happen if logic is correct, but safety net
                    cursor.execute("SELECT id_ficha FROM fichas WHERE numero_ficha = ?", (ficha_number_excel,))
                    res = cursor.fetchone()
                    if res:
                        real_ficha_id = res[0]
                        ficha_map[ficha_number_excel] = real_ficha_id

            try:
                cursor.execute('''
                    INSERT INTO aprendices (tipo_identificacion, documento, nombre, apellido, correo, celular, id_ficha, estado)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ''', (
                    row['tipo_identificacion'],
                    row['documento'],
                    row['nombre'],
                    row['apellido'],
                    row['correo'],
                    row['celular'],
                    real_ficha_id,
                    row['estado']
                ))
                aprendices_count += 1
            except sqlite3.IntegrityError as e:
                msg = f"Aprendiz Error: IntegrityError for apprentice {row['documento']}: {e}"
                print(msg)
                log_file.write(msg + "\n")

        print(f"Inserted {aprendices_count} aprendices.")
        
        conn.commit()
        print("Migration completed successfully.")

    except Exception as e:
        print(f"Migration failed: {e}")
        conn.rollback()
    finally:
        conn.close()
        log_file.close()

if __name__ == "__main__":
    migrate_data()
