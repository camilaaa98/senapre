import pandas as pd
import sqlite3

# Leer Excel
df = pd.read_excel('database/Aprendices.xlsx', sheet_name='aprendices')
df.columns = [c.strip() for c in df.columns]

print("=== PRUEBA DE LECTURA ===\n")
print(f"Total filas en Excel: {len(df)}")
print(f"\nColumnas después de strip: {list(df.columns)}\n")

# Tomar la primera fila
primera_fila = df.iloc[0]

print("=== PRIMERA FILA ===")
print(f"Documento: {primera_fila['documento']}")
print(f"Nombre: {primera_fila['nombre']}")

# Intentar leer tipo_identificacion
try:
    tipo = primera_fila['tipo_identificacion']
    print(f"tipo_identificacion: '{tipo}' (tipo: {type(tipo)})")
    if pd.isna(tipo):
        print("  -> Es NaN")
    else:
        print(f"  -> Valor limpio: '{str(tipo).strip()}'")
except KeyError as e:
    print(f"ERROR: No se encontró la columna 'tipo_identificacion': {e}")

# Intentar leer celular
try:
    cel = primera_fila['celular']
    print(f"celular: '{cel}' (tipo: {type(cel)})")
    if pd.isna(cel):
        print("  -> Es NaN")
    else:
        cel_str = str(cel).strip()
        if cel_str.endswith('.0'):
            cel_str = cel_str[:-2]
        print(f"  -> Valor limpio: '{cel_str}'")
except KeyError as e:
    print(f"ERROR: No se encontró la columna 'celular': {e}")

print("\n=== SIMULACIÓN DE INSERCIÓN ===")
# Simular lo que hace el script
row = primera_fila
try:
    celular_val = row['celular']
    if pd.isna(celular_val):
        celular = None
    else:
        celular = str(celular_val).strip()
        if celular.endswith('.0'): celular = celular[:-2]
except (KeyError, IndexError):
    celular = None

try:
    tipo_val = row['tipo_identificacion']
    if pd.isna(tipo_val):
        tipo_identificacion = 'CC'
    else:
        tipo_identificacion = str(tipo_val).strip()
except (KeyError, IndexError):
    tipo_identificacion = 'CC'

print(f"Celular que se insertaría: {celular}")
print(f"Tipo ID que se insertaría: {tipo_identificacion}")
