import sqlite3

try:
    conn = sqlite3.connect('database/Asistnet.db')
    cursor = conn.cursor()
    cursor.execute("SELECT sql FROM sqlite_master WHERE type='table'")
    tables = cursor.fetchall()
    with open('schema_dump.txt', 'w') as f:
        for table in tables:
            f.write(table[0] + "\n\n")
    conn.close()
except Exception as e:
    print(f"Error: {e}")
