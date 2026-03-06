import sqlite3
import pandas as pd

DB_PATH = 'database/Asistnet.db'

def verify_literal():
    conn = sqlite3.connect(DB_PATH)
    
    print("--- Muestra de Fichas (Literal) ---")
    query_fichas = "SELECT id_ficha, numero_ficha, nombre_programa, jornada FROM fichas LIMIT 5"
    df_fichas = pd.read_sql_query(query_fichas, conn)
    print(df_fichas.to_string(index=False))
    
    print("\n--- Muestra de Aprendices (Literal) ---")
    query_aprendices = "SELECT documento, nombre, apellido, id_ficha, nombre_programa FROM aprendices LIMIT 5"
    df_aprendices = pd.read_sql_query(query_aprendices, conn)
    print(df_aprendices.to_string(index=False))
    
    print("\n--- Totales ---")
    cursor = conn.cursor()
    cursor.execute("SELECT COUNT(*) FROM programas_formacion")
    print(f"Programas: {cursor.fetchone()[0]}")
    cursor.execute("SELECT COUNT(*) FROM fichas")
    print(f"Fichas: {cursor.fetchone()[0]}")
    cursor.execute("SELECT COUNT(*) FROM aprendices")
    print(f"Aprendices: {cursor.fetchone()[0]}")
    
    conn.close()

if __name__ == "__main__":
    verify_literal()
