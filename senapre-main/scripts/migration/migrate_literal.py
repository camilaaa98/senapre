import pandas as pd
import sqlite3
import os

EXCEL_PATH = 'database/Aprendices.xlsx'
DB_PATH = 'database/Asistnet.db'

def migrate_literal():
    if not os.path.exists(EXCEL_PATH):
        print(f"Excel file not found at {EXCEL_PATH}")
        return

    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()

    try:
        xl = pd.ExcelFile(EXCEL_PATH)
        
        # --- 1. Programas de Formacion ---
        print("Migrating Programas de Formacion (Literal)...")
        df_programas = pd.read_excel(xl, 'programas_formacion')
        df_programas.columns = df_programas.columns.str.strip() # Clean columns
        
        for _, row in df_programas.iterrows():
            cursor.execute("INSERT INTO programas_formacion (id_programa, nombre_programa, nivel_formacion) VALUES (?, ?, ?)", 
                           (row['id_programa'], row['nombre_programa'], row['nivel_formacion']))
        print(f"Inserted {len(df_programas)} programs.")

        # --- 2. Fichas ---
        print("Migrating Fichas (Literal)...")
        df_fichas = pd.read_excel(xl, 'fichas')
        df_fichas.columns = df_fichas.columns.str.strip() # Clean columns
        
        # Debug: Print columns if error persists
        # print(f"Fichas columns: {df_fichas.columns.tolist()}")

        for _, row in df_fichas.iterrows():
            # Excel column is 'programa' (based on recent inspection), DB expects 'nombre_programa'
            # Check which column exists to be robust
            prog_col = 'nombre_programa' if 'nombre_programa' in df_fichas.columns else 'programa'
            
            cursor.execute("INSERT INTO fichas (id_ficha, numero_ficha, nombre_programa, jornada, estado) VALUES (?, ?, ?, ?, ?)", 
                           (row['id_ficha'], row['numero_ficha'], row[prog_col], row['jornada'], row['estado']))
        print(f"Inserted {len(df_fichas)} fichas.")

        # --- 3. Aprendices ---
        print("Migrating Aprendices (Literal)...")
        df_aprendices = pd.read_excel(xl, 'aprendices')
        df_aprendices.columns = df_aprendices.columns.str.strip() # Clean columns
        
        df_aprendices = df_aprendices.where(pd.notnull(df_aprendices), None)

        count = 0
        # Check column name for program in aprendices
        prog_col_apr = 'nombre_programa' if 'nombre_programa' in df_aprendices.columns else ('programa' if 'programa' in df_aprendices.columns else None)
        nivel_col_apr = 'nivel_formacion' if 'nivel_formacion' in df_aprendices.columns else None

        for _, row in df_aprendices.iterrows():
            try:
                val_prog = row[prog_col_apr] if prog_col_apr else None
                val_nivel = row[nivel_col_apr] if nivel_col_apr else None
                
                cursor.execute('''
                    INSERT INTO aprendices (
                        id_aprendiz, tipo_identificacion, documento, nombre, apellido, correo, 
                        id_ficha, estado, celular, nombre_programa, nivel_formacion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ''', (
                    row['id_aprendiz'], row['tipo_identificacion'], row['documento'], row['nombre'], row['apellido'], row['correo'],
                    row['id_ficha'], row['estado'], row['celular'], val_prog, val_nivel
                ))
                count += 1
            except sqlite3.IntegrityError as e:
                print(f"Skipping duplicate/error for apprentice {row['documento']}: {e}")
        
        print(f"Inserted {count} aprendices.")
        
        conn.commit()
        print("Literal Migration completed successfully.")

    except Exception as e:
        print(f"Migration failed: {e}")
        import traceback
        traceback.print_exc()
        conn.rollback()
    finally:
        conn.close()

if __name__ == "__main__":
    migrate_literal()
