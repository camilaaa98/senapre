import pandas as pd
import os
import sys

# Redirigir stdout a un archivo
sys.stdout = open('columns_report.txt', 'w', encoding='utf-8')

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
EXCEL_FILE = os.path.join(BASE_DIR, 'database', 'Aprendices.xlsx')

try:
    print(f"Leyendo archivo: {EXCEL_FILE}")
    df = pd.read_excel(EXCEL_FILE, sheet_name='aprendices')
    
    print("\n=== COLUMNAS ENCONTRADAS ===")
    for col in df.columns:
        print(f"'{col}'")
        
    print("\n=== MUESTRA DE DATOS (Primeras 10 filas) ===")
    print(df[['id_ficha', 'celular']].head(10))
    
    print("\n=== TIPOS DE DATOS ===")
    print(df.dtypes)

except Exception as e:
    print(f"Error: {e}")
