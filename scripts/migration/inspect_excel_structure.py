import pandas as pd
import sys

try:
    xl = pd.ExcelFile('database/Aprendices.xlsx')
    with open('excel_structure.txt', 'w', encoding='utf-8') as f:
        f.write(f"Sheets: {xl.sheet_names}\n\n")
        for sheet in xl.sheet_names:
            df = pd.read_excel(xl, sheet, nrows=1)
            f.write(f"Sheet: {sheet}\n")
            f.write(f"Columns: {df.columns.tolist()}\n")
            if not df.empty:
                f.write(f"First row: {df.iloc[0].to_dict()}\n")
            f.write("-" * 20 + "\n")
    print("Structure saved to excel_structure.txt")
except Exception as e:
    print(f"Error: {e}")
