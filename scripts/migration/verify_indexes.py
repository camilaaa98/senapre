import sqlite3

try:
    conn = sqlite3.connect('database/Asistnet.db')
    cursor = conn.cursor()
    cursor.execute("PRAGMA index_list(aprendices)")
    indexes = cursor.fetchall()
    print("Índices en aprendices:")
    for idx in indexes:
        print(idx)
        # Check columns of the index
        cursor.execute(f"PRAGMA index_info({idx[1]})")
        cols = cursor.fetchall()
        print(f"  Columnas: {[c[2] for c in cols]}")
        
    conn.close()
except Exception as e:
    print(f"Error: {e}")
