import pandas as pd
import os

excel_file = 'database/Aprendices.xlsx'

try:
    df = pd.read_excel(excel_file)
    print("Columnas:", df.columns.tolist())
    print("Primera fila:")
    print(df.iloc[0])
    print("\nTipos de datos:")
    print(df.dtypes)
except Exception as e:
    print(f"Error: {e}")
