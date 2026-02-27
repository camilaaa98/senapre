import sqlite3
import os

db_path = 'database/Asistnet.db'

def limpiar_base_datos():
    """Elimina todos los registros de las tablas principales"""
    if not os.path.exists(db_path):
        print(f"Base de datos no encontrada: {db_path}")
        return
    
    conn = sqlite3.connect(db_path)
    cursor = conn.cursor()
    
    try:
        # Desactivar foreign keys temporalmente para poder borrar
        cursor.execute("PRAGMA foreign_keys=OFF")
        
        print("Limpiando tablas...")
        
        # Borrar en orden inverso de dependencias
        cursor.execute("DELETE FROM asistencias")
        print("✓ Tabla 'asistencias' limpiada")
        
        cursor.execute("DELETE FROM aprendices")
        print("✓ Tabla 'aprendices' limpiada")
        
        cursor.execute("DELETE FROM fichas")
        print("✓ Tabla 'fichas' limpiada")
        
        cursor.execute("DELETE FROM programas_formacion")
        print("✓ Tabla 'programas_formacion' limpiada")
        
        # Resetear los autoincrement
        cursor.execute("DELETE FROM sqlite_sequence WHERE name IN ('asistencias', 'aprendices', 'fichas', 'programas_formacion')")
        print("✓ Contadores de autoincremento reseteados")
        
        conn.commit()
        
        # Reactivar foreign keys
        cursor.execute("PRAGMA foreign_keys=ON")
        
        print("\n¡Base de datos limpiada exitosamente!")
        print("Ahora puedes ejecutar: python migrate_data.py")
        
    except Exception as e:
        conn.rollback()
        print(f"Error al limpiar la base de datos: {e}")
    finally:
        conn.close()

if __name__ == "__main__":
    limpiar_base_datos()
