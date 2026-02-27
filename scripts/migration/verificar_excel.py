import pandas as pd
import os
import time

print("=== VERIFICACIÓN DEL EXCEL ===")
print(f"Última modificación: {time.ctime(os.path.getmtime('database/Aprendices.xlsx'))}")
print()

df = pd.read_excel('database/Aprendices.xlsx', sheet_name='aprendices')
print(f"Total filas: {len(df)}")
print()

print("Estados en Excel:")
print(df['estado'].value_counts())
