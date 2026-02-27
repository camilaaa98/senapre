import pandas as pd
import os
import sys

# Redirigir stdout a un archivo
sys.stdout = open('raw_excel_report.txt', 'w', encoding='utf-8')

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
EXCEL_FILE = os.path.join(BASE_DIR, 'database', 'Aprendices.xlsx')

try:
    print(f"Leyendo archivo (header=None): {EXCEL_FILE}")
    df = pd.read_excel(EXCEL_FILE, sheet_name='aprendices', header=None)
    
    print("\n=== PRIMERAS 5 FILAS (RAW) ===")
    print(df.head(5))
    
    print("\n=== ANÁLISIS DE POSIBLES ENCABEZADOS ===")
    print("Fila 0:", df.iloc[0].tolist())
    print("Fila 1:", df.iloc[1].tolist())

except Exception as e:
    print(f"Error: {e}")
