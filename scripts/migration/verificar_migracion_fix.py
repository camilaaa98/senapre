import sqlite3
import pandas as pd

DB_FILE = 'database/Asistnet.db'

def verificar():
    conn = sqlite3.connect(DB_FILE)
    cursor = conn.cursor()
    
    print("="*60)
    print("VERIFICACIÓN DE MIGRACIÓN")
    print("="*60)
    
    # 1. Verificar Celulares
    cursor.execute("SELECT COUNT(*) FROM aprendices WHERE celular IS NOT NULL")
    con_celular = cursor.fetchone()[0]
    cursor.execute("SELECT COUNT(*) FROM aprendices")
    total = cursor.fetchone()[0]
    print(f"\n[CELULARES] Aprendices con celular: {con_celular} de {total}")
    
    # 2. Verificar Fichas (Explicación para el usuario)
    print("\n[FICHAS] Verificando relación Aprendiz -> Ficha")
    print("El 'id_ficha' en la tabla aprendices es un enlace interno.")
    print("A continuación se muestra cómo se traduce ese número interno al número real de la ficha:")
    
    query = """
    SELECT 
        a.nombre, 
        a.apellido, 
        a.id_ficha as 'ID Interno (BD)', 
        f.numero_ficha as 'Número Real (Ficha)'
    FROM aprendices a
    JOIN fichas f ON a.id_ficha = f.id_ficha
    LIMIT 5
    """
    
    df = pd.read_sql_query(query, conn)
    print(df.to_string(index=False))
    
    # Verificar si hay alguna ficha mal asignada (ej. id_ficha=1 pero numero_ficha!=2277866 si fuera el caso)
    # En este caso, solo mostramos la evidencia de que el enlace funciona.

    conn.close()

if __name__ == "__main__":
    verificar()
