import sqlite3
import pandas as pd
import sys

DB_PATH = 'database/Asistnet.db'
EXCEL_PATH = 'database/Aprendices.xlsx'
REPORT_PATH = 'verification_report.txt'

def verify_migration():
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()
    
    xl = pd.ExcelFile(EXCEL_PATH)
    
    with open(REPORT_PATH, 'w') as f:
        f.write("--- Verification Report ---\n")
        
        # 1. Check Programas
        excel_programs = len(pd.read_excel(xl, 'programas_formacion'))
        cursor.execute("SELECT COUNT(*) FROM programas_formacion")
        db_programs = cursor.fetchone()[0]
        f.write(f"Programas: Excel={excel_programs}, DB={db_programs} -> {'OK' if excel_programs == db_programs else 'MISMATCH'}\n")

        # 2. Check Fichas
        excel_fichas = len(pd.read_excel(xl, 'fichas'))
        cursor.execute("SELECT COUNT(*) FROM fichas")
        db_fichas = cursor.fetchone()[0]
        f.write(f"Fichas: Excel={excel_fichas}, DB={db_fichas} -> {'OK' if excel_fichas == db_fichas else 'MISMATCH'}\n")

        # 3. Check Aprendices
        excel_aprendices = len(pd.read_excel(xl, 'aprendices'))
        cursor.execute("SELECT COUNT(*) FROM aprendices")
        db_aprendices = cursor.fetchone()[0]
        f.write(f"Aprendices: Excel={excel_aprendices}, DB={db_aprendices} -> {'OK' if excel_aprendices == db_aprendices else 'MISMATCH'}\n")

        # 4. Check Relationships
        cursor.execute("SELECT COUNT(*) FROM fichas WHERE programa IS NULL")
        orphaned_fichas = cursor.fetchone()[0]
        f.write(f"Fichas without Program: {orphaned_fichas} -> {'OK' if orphaned_fichas == 0 else 'FAIL'}\n")

        cursor.execute("SELECT COUNT(*) FROM aprendices WHERE id_ficha IS NULL")
        orphaned_aprendices = cursor.fetchone()[0]
        f.write(f"Aprendices without Ficha: {orphaned_aprendices} -> {'OK' if orphaned_aprendices == 0 else 'FAIL'}\n")

    conn.close()
    print(f"Verification report saved to {REPORT_PATH}")

if __name__ == "__main__":
    verify_migration()
