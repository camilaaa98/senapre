import sqlite3

try:
    conn = sqlite3.connect('database/Asistnet.db')
    cursor = conn.cursor()
    cursor.execute("PRAGMA table_info(fichas)")
    columns = cursor.fetchall()
    print("Columnas en fichas:")
    for col in columns:
        print(col)
    conn.close()
except Exception as e:
    print(f"Error: {e}")
