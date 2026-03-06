import sqlite3

conn = sqlite3.connect('database/Asistnet.db')
cursor = conn.cursor()

print("=== VERIFICACIÓN RÁPIDA DE LA BD ===\n")

cursor.execute("SELECT COUNT(*) FROM programas_formacion")
print(f"Programas: {cursor.fetchone()[0]}")

cursor.execute("SELECT COUNT(*) FROM fichas")
print(f"Fichas: {cursor.fetchone()[0]}")

cursor.execute("SELECT COUNT(*) FROM aprendices")
total = cursor.fetchone()[0]
print(f"Aprendices: {total}")

if total > 0:
    print("\n=== ESTADOS EN BD ===")
    cursor.execute("SELECT estado, COUNT(*) FROM aprendices GROUP BY estado ORDER BY COUNT(*) DESC")
    for estado, count in cursor.fetchall():
        print(f"  {estado}: {count}")
    
    print("\n=== MUESTRA DE 3 REGISTROS ===")
    cursor.execute("SELECT documento, nombre, estado, tipo_identificacion, celular FROM aprendices LIMIT 3")
    for doc, nom, est, tipo, cel in cursor.fetchall():
        print(f"Doc: {doc}, Nombre: {nom}")
        print(f"  Estado: {est}, Tipo: {tipo}, Celular: {cel}")
else:
    print("\n⚠️ NO HAY DATOS EN LA TABLA APRENDICES")

conn.close()
