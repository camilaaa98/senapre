import pandas as pd
import os
import sys

# Redirigir stdout a un archivo
sys.stdout = open('inspect_programs_report.txt', 'w', encoding='utf-8')

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
EXCEL_FILE = os.path.join(BASE_DIR, 'database', 'Aprendices.xlsx')

try:
    print(f"Leyendo archivo: {EXCEL_FILE}")
    print("Hoja: programas_formacion")
    df = pd.read_excel(EXCEL_FILE, sheet_name='programas_formacion')
    
    print("\n=== COLUMNAS ===")
    for col in df.columns:
        print(f"'{col}'")
        
    print("\n=== MUESTRA DE DATOS (Primeras 5 filas) ===")
    print(df.head(5))

except Exception as e:
    print(f"Error: {e}")
