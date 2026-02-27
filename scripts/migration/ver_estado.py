import sqlite3

db_path = 'database/Asistnet.db'
conn = sqlite3.connect(db_path)
cursor = conn.cursor()

print("=== ESTADO ACTUAL DE LA BASE DE DATOS ===\n")

# Contar registros en cada tabla
cursor.execute("SELECT COUNT(*) FROM programas_formacion")
print(f"✓ Programas de formación: {cursor.fetchone()[0]} registros")

cursor.execute("SELECT COUNT(*) FROM fichas")
print(f"✓ Fichas: {cursor.fetchone()[0]} registros")

cursor.execute("SELECT COUNT(*) FROM aprendices")
total = cursor.fetchone()[0]
print(f"✓ Aprendices: {total} registros")

# Verificar tipo_identificacion y celular
cursor.execute("SELECT COUNT(*) FROM aprendices WHERE tipo_identificacion IS NOT NULL")
con_tipo = cursor.fetchone()[0]

cursor.execute("SELECT COUNT(*) FROM aprendices WHERE celular IS NOT NULL")
con_celular = cursor.fetchone()[0]

print(f"\n=== VERIFICACIÓN DE COLUMNAS ===")
print(f"✓ Con tipo_identificacion: {con_tipo}/{total}")
print(f"✓ Con celular: {con_celular}/{total}")

# Mostrar algunos ejemplos
print(f"\n=== EJEMPLOS DE DATOS (primeros 5) ===\n")
cursor.execute("""
    SELECT documento, nombre, tipo_identificacion, celular 
    FROM aprendices 
    LIMIT 5
""")

for doc, nom, tipo, cel in cursor.fetchall():
    print(f"Doc: {doc} | Nombre: {nom}")
    print(f"  Tipo ID: {tipo if tipo else 'NULL'}")
    print(f"  Celular: {cel if cel else 'NULL'}")
    print()

conn.close()
