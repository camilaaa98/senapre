import pandas as pd

excel_path = 'database/Aprendices.xlsx'

# Leer la hoja de aprendices
df = pd.read_excel(excel_path, sheet_name='aprendices')

print("=== COLUMNAS EN EL EXCEL (hoja 'aprendices') ===\n")
for i, col in enumerate(df.columns):
    print(f"{i+1}. '{col}'")

print(f"\n=== PRIMERA FILA DE DATOS ===\n")
primera_fila = df.iloc[0]
for col in df.columns:
    valor = primera_fila[col]
    print(f"{col}: {valor}")
