import sqlite3

try:
    conn = sqlite3.connect('database/Asistnet.db')
    cursor = conn.cursor()
    cursor.execute("PRAGMA table_info(aprendices)")
    columns = cursor.fetchall()
    with open('columns_check.txt', 'w') as f:
        for col in columns:
            f.write(str(col) + "\n")
            
    # Check asistencias FK
    cursor.execute("PRAGMA foreign_key_list(asistencias)")
    fks = cursor.fetchall()
    with open('fks_check.txt', 'w') as f:
        for fk in fks:
            f.write(str(fk) + "\n")
            
    conn.close()
except Exception as e:
    print(f"Error: {e}")
