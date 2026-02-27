import pandas as pd

df = pd.read_excel('database/Aprendices.xlsx', sheet_name='aprendices')

print("=== VERIFICACIÓN COLUMNA CELULAR ===")
print(f"Total filas: {len(df)}")
print(f"Celulares no nulos: {df['celular'].notna().sum()}")
print(f"Celulares nulos: {df['celular'].isna().sum()}")

print("\n=== PRIMEROS 10 CELULARES ===")
for i, cel in enumerate(df['celular'].head(10)):
    print(f"{i+1}. {cel} (tipo: {type(cel).__name__})")

print("\n=== VERIFICACIÓN COLUMNA id_ficha ===")
print(f"\nPrimeros 5 id_ficha:")
for i, ficha in enumerate(df['id_ficha'].head(5)):
    print(f"{i+1}. {ficha} (tipo: {type(ficha).__name__})")
