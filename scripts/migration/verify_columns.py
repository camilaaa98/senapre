import sqlite3

try:
    conn = sqlite3.connect('database/Asistnet.db')
    cursor = conn.cursor()
    cursor.execute("PRAGMA table_info(aprendices)")
    columns = cursor.fetchall()
    print("Columnas en aprendices:")
    for col in columns:
        print(col)
        
    print("\nTablas:")
    cursor.execute("SELECT name FROM sqlite_master WHERE type='table'")
    for row in cursor.fetchall():
        print(row[0])
        
    conn.close()
except Exception as e:
    print(f"Error: {e}")
