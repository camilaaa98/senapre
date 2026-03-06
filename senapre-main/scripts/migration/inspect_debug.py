import pandas as pd
import sqlite3
import os

excel_path = 'database/Aprendices.xlsx'
db_path = 'database/Asistnet.db'

def inspect():
    print("--- INSPECCIÓN DE EXCEL ---")
    if not os.path.exists(excel_path):
        print(f"Archivo {excel_path} no encontrado.")
        return

    try:
        xls = pd.ExcelFile(excel_path)
        print(f"Hojas encontradas: {xls.sheet_names}")
        
        for sheet in xls.sheet_names:
            print(f"\n--- Hoja: {sheet} ---")
            df = pd.read_excel(xls, sheet_name=sheet)
            print(f"Total filas en Excel: {len(df)}")
            print("Columnas:", list(df.columns))
            print("Primeras filas:")
            print(df.head(3))
            
    except Exception as e:
        print(f"Error leyendo Excel: {e}")

    print("\n--- INSPECCIÓN DE BASE DE DATOS ---")
    if not os.path.exists(db_path):
        print(f"DB {db_path} no encontrada.")
        return
        
    try:
        conn = sqlite3.connect(db_path)
        cursor = conn.cursor()
        
        tables = ['programas_formacion', 'fichas', 'aprendices']
        for table in tables:
            cursor.execute(f"SELECT count(*) FROM {table}")
            count = cursor.fetchone()[0]
            print(f"Tabla '{table}': {count} registros")
            
            if count > 0:
                cursor.execute(f"SELECT * FROM {table} LIMIT 3")
                cols = [description[0] for description in cursor.description]
                print(f"Columnas DB: {cols}")
                rows = cursor.fetchall()
                for row in rows:
                    print(row)
        
        conn.close()
    except Exception as e:
        print(f"Error leyendo DB: {e}")

if __name__ == "__main__":
    import sys
    with open('debug_output_utf8.txt', 'w', encoding='utf-8') as f:
        sys.stdout = f
        inspect()
