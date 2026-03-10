import sqlite3
import pandas as pd

DB_PATH = 'database/Asistnet.db'

def show_sample():
    conn = sqlite3.connect(DB_PATH)
    
    query = '''
    SELECT 
        a.documento,
        a.nombre || ' ' || a.apellido as aprendiz,
        f.numero_ficha,
        p.nombre_programa,
        f.jornada,
        a.estado
    FROM aprendices a
    JOIN fichas f ON a.id_ficha = f.id_ficha
    JOIN programas_formacion p ON f.programa = p.id_programa
    ORDER BY RANDOM()
    LIMIT 10;
    '''
    
    df = pd.read_sql_query(query, conn)
    print(df.to_string(index=False))
    
    print("\n--- Totales ---")
    cursor = conn.cursor()
    cursor.execute("SELECT COUNT(*) FROM aprendices")
    print(f"Total Aprendices: {cursor.fetchone()[0]}")
    cursor.execute("SELECT COUNT(*) FROM fichas")
    print(f"Total Fichas: {cursor.fetchone()[0]}")
    cursor.execute("SELECT COUNT(*) FROM programas_formacion")
    print(f"Total Programas: {cursor.fetchone()[0]}")
    
    conn.close()

if __name__ == "__main__":
    show_sample()
