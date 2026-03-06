import pandas as pd

excel_path = 'database/Aprendices.xlsx'

# Leer solo la hoja de aprendices
df = pd.read_excel(excel_path, sheet_name='aprendices')

print("=== COLUMNAS EN LA HOJA 'aprendices' ===")
for i, col in enumerate(df.columns):
    print(f"{i}: '{col}' (tipo: {type(col).__name__})")

print("\n=== PRIMERAS 5 FILAS ===")
print(df.head())

print("\n=== VALORES DE tipo_identificacion ===")
print(df['tipo_identificacion'].head(10))

print("\n=== VALORES DE celular ===")
print(df['celular'].head(10))
