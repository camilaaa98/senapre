import sqlite3

db_path = 'database/Asistnet.db'

conn = sqlite3.connect(db_path)
cursor = conn.cursor()

print("=== VERIFICACIÓN DE MIGRACIÓN ===\n")

# Contar registros
cursor.execute("SELECT COUNT(*) FROM programas_formacion")
print(f"Programas de formación: {cursor.fetchone()[0]}")

cursor.execute("SELECT COUNT(*) FROM fichas")
print(f"Fichas: {cursor.fetchone()[0]}")

cursor.execute("SELECT COUNT(*) FROM aprendices")
total_aprendices = cursor.fetchone()[0]
print(f"Aprendices: {total_aprendices}")

print("\n=== VERIFICACIÓN DE COLUMNAS tipo_identificacion y celular ===\n")

# Verificar tipo_identificacion
cursor.execute("SELECT COUNT(*) FROM aprendices WHERE tipo_identificacion IS NOT NULL AND tipo_identificacion != ''")
con_tipo = cursor.fetchone()[0]
print(f"Aprendices CON tipo_identificacion: {con_tipo} ({con_tipo*100//total_aprendices}%)")

cursor.execute("SELECT COUNT(*) FROM aprendices WHERE tipo_identificacion IS NULL OR tipo_identificacion = ''")
sin_tipo = cursor.fetchone()[0]
print(f"Aprendices SIN tipo_identificacion: {sin_tipo}")

# Verificar celular
cursor.execute("SELECT COUNT(*) FROM aprendices WHERE celular IS NOT NULL AND celular != ''")
con_celular = cursor.fetchone()[0]
print(f"\nAprendices CON celular: {con_celular} ({con_celular*100//total_aprendices}%)")

cursor.execute("SELECT COUNT(*) FROM aprendices WHERE celular IS NULL OR celular = ''")
sin_celular = cursor.fetchone()[0]
print(f"Aprendices SIN celular: {sin_celular}")

print("\n=== MUESTRA DE DATOS (primeros 10 registros) ===\n")
cursor.execute("""
    SELECT documento, nombre, apellido, tipo_identificacion, celular, estado 
    FROM aprendices 
    LIMIT 10
""")

print(f"{'Documento':<15} {'Nombre':<20} {'Apellido':<20} {'Tipo':<6} {'Celular':<15} {'Estado'}")
print("-" * 100)
for row in cursor.fetchall():
    doc, nom, ape, tipo, cel, est = row
    tipo_str = tipo if tipo else "NULL"
    cel_str = cel if cel else "NULL"
    print(f"{doc:<15} {nom:<20} {ape:<20} {tipo_str:<6} {cel_str:<15} {est}")

conn.close()
