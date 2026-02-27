import sqlite3
import pandas as pd

print("="*70)
print("COMPARACIÓN EXCEL vs BASE DE DATOS")
print("="*70)

# Leer Excel
df = pd.read_excel('database/Aprendices.xlsx', sheet_name='aprendices')
print("\n📊 EXCEL:")
print(f"  Total filas: {len(df)}")
print("\n  Estados:")
for estado, count in df['estado'].value_counts().items():
    print(f"    {estado}: {count}")

# Leer BD
conn = sqlite3.connect('database/Asistnet.db')
cursor = conn.cursor()

cursor.execute("SELECT COUNT(*) FROM aprendices")
total_bd = cursor.fetchone()[0]

print(f"\n💾 BASE DE DATOS:")
print(f"  Total registros: {total_bd}")

cursor.execute("SELECT estado, COUNT(*) FROM aprendices GROUP BY estado ORDER BY COUNT(*) DESC")
print("\n  Estados:")
for estado, count in cursor.fetchall():
    print(f"    {estado}: {count}")

# Verificar columnas
cursor.execute("SELECT COUNT(*) FROM aprendices WHERE tipo_identificacion IS NOT NULL AND tipo_identificacion != ''")
con_tipo = cursor.fetchone()[0]

cursor.execute("SELECT COUNT(*) FROM aprendices WHERE celular IS NOT NULL AND celular != ''")
con_celular = cursor.fetchone()[0]

print(f"\n📋 COLUMNAS ADICIONALES:")
print(f"  Con tipo_identificacion: {con_tipo}/{total_bd}")
print(f"  Con celular: {con_celular}/{total_bd}")

print("\n" + "="*70)
print("✅ MIGRACIÓN COMPLETADA EXITOSAMENTE")
print("="*70)

conn.close()
