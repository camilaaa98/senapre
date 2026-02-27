import sqlite3
import pandas as pd

DB_FILE = 'database/Asistnet.db'

def verificar():
    conn = sqlite3.connect(DB_FILE)
    cursor = conn.cursor()
    
    print("="*60)
    print("VERIFICACIÓN DE IDs DE FICHAS")
    print("="*60)
    
    # Verificar que los IDs en la tabla fichas sean los números reales
    print("\n[FICHAS] Muestra de la tabla 'fichas':")
    df_fichas = pd.read_sql_query("SELECT id_ficha, numero_ficha FROM fichas LIMIT 5", conn)
    print(df_fichas.to_string(index=False))
    
    # Verificar que los IDs en la tabla aprendices sean los números reales
    print("\n[APRENDICES] Muestra de la tabla 'aprendices':")
    df_aprendices = pd.read_sql_query("SELECT nombre, id_ficha FROM aprendices LIMIT 5", conn)
    print(df_aprendices.to_string(index=False))
    
    # Verificar consistencia
    cursor.execute("SELECT COUNT(*) FROM aprendices a LEFT JOIN fichas f ON a.id_ficha = f.id_ficha WHERE f.id_ficha IS NULL")
    huérfanos = cursor.fetchone()[0]
    if huérfanos == 0:
        print("\n✅ INTEGRIDAD REFERENCIAL CORRECTA: Todos los aprendices tienen una ficha válida.")
    else:
        print(f"\n❌ ERROR: Hay {huérfanos} aprendices sin ficha válida.")

    conn.close()

if __name__ == "__main__":
    verificar()
