import pandas as pd
import sqlite3
import os

# --- Configuracion ---
# Obtener la ruta absoluta del directorio del script
base_dir = os.path.dirname(os.path.abspath(__file__))
excel_file = os.path.join(base_dir, 'database', 'Aprendices.xlsx')
db_file = os.path.join(base_dir, 'database', 'Asistnet.db')

# --- Lectura del archivo Excel ---
print("Asegúrate de que el archivo 'Aprendices.xlsx' no esté abierto en Excel.")
try:
    # Leemos la columna 'documento' como texto para evitar problemas con notación científica
    df = pd.read_excel(excel_file, dtype={'documento': str})
    print(f"Se encontraron {len(df)} registros en el archivo Excel.")
except FileNotFoundError:
    print(f"Error: No se pudo encontrar el archivo '{excel_file}'.")
    exit()
except Exception as e:
    print(f"Error al leer el archivo Excel: {e}")
    print("Asegúrate de que el archivo no esté dañado y que Excel esté cerrado.")
    exit()

# --- Conexión a la base de datos ---
try:
    conn = sqlite3.connect(db_file)
    cursor = conn.cursor()
except sqlite3.Error as e:
    print(f"Error al conectar a la base de datos: {e}")
    exit()

# --- Inserción de datos ---
# Las columnas en el Excel deben coincidir con estos nombres.
column_map = {
    'documento': 'documento',
    'nombre': 'nombre',
    'apellido': 'apellido',
    'correo': 'correo',
    'id_ficha': 'id_ficha',
    'estado': 'estado'
}

# Limpiar la tabla antes de insertar para evitar duplicados en ejecuciones repetidas
try:
    print("Limpiando la tabla 'aprendices' antes de la importación...")
    cursor.execute("DELETE FROM aprendices;")
    # Reiniciar el contador de autoincremento para evitar que los IDs crezcan en cada importación
    cursor.execute("DELETE FROM sqlite_sequence WHERE name='aprendices';")
    print("Tabla 'aprendices' limpiada y reiniciada.")
except sqlite3.Error as e:
    print(f"Advertencia: No se pudo limpiar la tabla 'aprendices'. Puede que no exista todavía. ({e})")


insert_query = """
INSERT INTO aprendices (documento, nombre, apellido, correo, id_ficha, estado)
VALUES (?, ?, ?, ?, ?, ?);
"""

registros_insertados = 0
registros_fallidos = 0

for index, row in df.iterrows():
    try:
        # Obtener el estado como texto. Si está vacío, usar un valor por defecto.
        estado_texto = row.get(column_map['estado'])
        if pd.isna(estado_texto) or str(estado_texto).strip() == '':
            estado_texto = 'EN FORMACION' # Valor por defecto si la celda está vacía

        # Convertir id_ficha a entero, manejando posibles valores nulos o incorrectos
        id_ficha = row.get(column_map['id_ficha'])
        if pd.isna(id_ficha):
            id_ficha = None
        else:
            id_ficha = int(id_ficha)

        data_tuple = (
            str(row[column_map['documento']]),
            row[column_map['nombre']],
            row[column_map['apellido']],
            row.get(column_map['correo']),
            id_ficha,
            str(estado_texto).strip() # Usar el estado como texto y quitar espacios
        )
        cursor.execute(insert_query, data_tuple)
        registros_insertados += 1
    except sqlite3.IntegrityError as e:
        print(f"Error en fila {index + 2}: No se pudo insertar el registro para el documento {row[column_map['documento']]}. Es posible que ya exista o falte un dato obligatorio. ({e})")
        registros_fallidos += 1
    except KeyError as e:
        print(f"Error: La columna {e} no se encuentra en el archivo Excel. Por favor, verifica los nombres de las columnas.")
        conn.close()
        exit()
    except (ValueError, TypeError) as e:
        print(f"Error de tipo de dato en fila {index + 2} para el documento {row[column_map['documento']]}. Verifica que los datos sean correctos (ej. id_ficha debe ser un número). ({e})")
        registros_fallidos += 1
    except Exception as e:
        print(f"Error en fila {index + 2}: Ocurrió un error inesperado insertando el registro. ({e})")
        registros_fallidos += 1

# --- Finalización ---
conn.commit()
conn.close()

print("\n--- Resumen de la importación ---")
print(f"Registros insertados correctamente: {registros_insertados}")
print(f"Registros fallidos: {registros_fallidos}")
print("------------------------------------")

if registros_fallidos == 0 and registros_insertados > 0:
    print("¡La importación de datos ha finalizado con éxito!")
elif registros_insertados > 0:
    print("La importación de datos ha finalizado con algunos errores.")
else:
    print("La importación no insertó ningún registro. Revisa los errores reportados.")
