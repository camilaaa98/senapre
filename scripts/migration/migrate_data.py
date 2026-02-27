import pandas as pd
import sqlite3
import os

# Configuración
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
EXCEL_FILE = os.path.join(BASE_DIR, 'database', 'Aprendices.xlsx')
DB_FILE = os.path.join(BASE_DIR, 'database', 'Asistnet.db')

def connect_db():
    return sqlite3.connect(DB_FILE)

def migrate():
    import sys
    log_file = open('migration_debug.log', 'w', encoding='utf-8')
    sys.stdout = log_file
    print(f"Iniciando migración desde {EXCEL_FILE} a {DB_FILE}...")
    
    conn = connect_db()
    cursor = conn.cursor()
    
    # 1. Migrar Programas de Formación
    try:
        print("Migrando Programas de Formación...")
        df_prog = pd.read_excel(EXCEL_FILE, sheet_name='programas_formacion')
        df_prog.columns = [c.strip() for c in df_prog.columns]
        
        for index, row in df_prog.iterrows():
            # Asumimos que el Excel puede tener id_programa o no. Si lo tiene, intentamos preservarlo o dejar que autoincrement
            # Estrategia: Buscar por nombre y nivel.
            nombre = row.get('nombre_programa', 'NO DEFINIDO')
            nivel = row.get('nivel_formacion', 'NO DEFINIDO')
            
            cursor.execute("SELECT id_programa FROM programas_formacion WHERE nombre_programa = ? AND nivel_formacion = ?", (nombre, nivel))
            if not cursor.fetchone():
                cursor.execute("INSERT INTO programas_formacion (nombre_programa, nivel_formacion) VALUES (?, ?)", (nombre, nivel))
        conn.commit()
    except ValueError:
        print("Hoja 'programas_formacion' no encontrada. Saltando...")
    except Exception as e:
        print(f"Error migrando programas: {e}")

    # 2. Migrar Fichas
    try:
        print("Migrando Fichas...")
        df_fichas = pd.read_excel(EXCEL_FILE, sheet_name='fichas')
        df_fichas.columns = [c.strip() for c in df_fichas.columns]
        
        for index, row in df_fichas.iterrows():
            numero_ficha = str(row.get('numero_ficha')).strip()
            if numero_ficha.endswith('.0'): numero_ficha = numero_ficha[:-2]
            jornada = row.get('jornada')
            if pd.isna(jornada):
                jornada = 'NO DEFINIDA'
            estado = row.get('estado', 'ACTIVO')
            
            # Necesitamos el id_programa. El excel probablemente tenga id_programa O nombre_programa.
            # Si tiene id_programa, verificamos si coincide. Si no, intentamos buscar.
            # Asumiremos que si viene del mismo sistema, los IDs podrían coincidir, pero es arriesgado confiar en IDs de excel.
            # Vamos a intentar buscar el programa por nombre si está disponible, o confiar en el ID si no.
            
            id_programa = row.get('id_programa')
            
            # Validar si existe el programa con ese ID
            if id_programa:
                try:
                    id_programa = int(float(id_programa))
                except:
                    print(f"Advertencia: ID Programa inválido en ficha {numero_ficha}: {id_programa}")
                    continue

                cursor.execute("SELECT id_programa FROM programas_formacion WHERE id_programa = ?", (id_programa,))
                if not cursor.fetchone():
                    print(f"Advertencia: Ficha {numero_ficha} referencia id_programa {id_programa} que no existe en DB. Insertando con ID 1 (Default) para evitar pérdida.")
                    # Fallback temporal para asegurar que la ficha se cree
                    id_programa = 1 
            else:
                id_programa = 1 # Default

            cursor.execute("SELECT id_ficha FROM fichas WHERE numero_ficha = ?", (numero_ficha,))
            if not cursor.fetchone():
                cursor.execute("INSERT INTO fichas (numero_ficha, id_programa, jornada, estado) VALUES (?, ?, ?, ?)", (numero_ficha, id_programa, jornada, estado))
        conn.commit()
    except ValueError:
        print("Hoja 'fichas' no encontrada. Saltando...")
    except Exception as e:
        print(f"Error migrando fichas: {e}")

    # 3. Migrar Aprendices
    try:
        print("Migrando Aprendices...")
        df_aprendices = pd.read_excel(EXCEL_FILE, sheet_name='aprendices')
        df_aprendices.columns = [c.strip() for c in df_aprendices.columns]
        
        registros_insertados = 0
        
        for index, row in df_aprendices.iterrows():
            try:
                documento = str(row.get('documento')).strip()
                # Limpieza de documento
                if 'E' in documento or '.' in documento:
                     try: documento = str(int(float(documento)))
                     except: pass

                nombre = row.get('nombre', '').strip()
                apellido = row.get('apellido', '').strip()
                correo = row.get('correo', '').strip()
                
                # Manejo de nulos para celular y tipo - acceso directo a columnas
                try:
                    celular_val = row['celular']
                    if pd.isna(celular_val):
                        celular = None
                    else:
                        celular = str(celular_val).strip()
                        if celular.endswith('.0'): celular = celular[:-2]
                except (KeyError, IndexError):
                    celular = None

                try:
                    tipo_val = row['tipo_identificacion']
                    if pd.isna(tipo_val):
                        tipo_identificacion = 'CC'  # Default
                    else:
                        tipo_identificacion = str(tipo_val).strip()
                except (KeyError, IndexError):
                    tipo_identificacion = 'CC'
                
                # El Excel tiene 'id_ficha' que corresponde al NUMERO DE FICHA
                numero_ficha_excel = row.get('id_ficha')
                if pd.isna(numero_ficha_excel):
                    print(f"Advertencia: Aprendiz {documento} sin ficha asignada.")
                    continue
                
                numero_ficha_excel = str(numero_ficha_excel).strip()
                if numero_ficha_excel.endswith('.0'): numero_ficha_excel = numero_ficha_excel[:-2]

                # Buscar el id_ficha (PK) usando el numero_ficha
                cursor.execute("SELECT id_ficha FROM fichas WHERE numero_ficha = ?", (numero_ficha_excel,))
                res = cursor.fetchone()
                if not res:
                    print(f"Advertencia: Aprendiz {documento} referencia ficha {numero_ficha_excel} que no existe en DB.")
                    continue
                
                id_ficha_db = res[0]

                estado_val = row.get('estado', 1)
                
                # DEBUG: Imprimir los primeros 3 registros
                if registros_insertados < 3:
                    print(f"\n[DEBUG] Registro {registros_insertados + 1}:")
                    print(f"  Documento: {documento}")
                    print(f"  Nombre: {nombre}")
                    print(f"  Tipo ID: {tipo_identificacion}")
                    print(f"  Celular: {celular}")
                
                # Insertar/Update usando id_ficha_db
                cursor.execute("SELECT id_aprendiz FROM aprendices WHERE documento = ? AND id_ficha = ?", (documento, id_ficha_db))
                if cursor.fetchone():
                    cursor.execute("""
                        UPDATE aprendices SET 
                        nombre=?, apellido=?, correo=?, estado=?, celular=?, tipo_identificacion=?
                        WHERE documento=? AND id_ficha=?
                    """, (nombre, apellido, correo, estado_val, celular, tipo_identificacion, documento, id_ficha_db))
                else:
                    cursor.execute("""
                        INSERT INTO aprendices (documento, nombre, apellido, correo, id_ficha, estado, celular, tipo_identificacion)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    """, (documento, nombre, apellido, correo, id_ficha_db, estado_val, celular, tipo_identificacion))
                
                registros_insertados += 1
            except Exception as e:
                print(f"Error en fila {index} (Aprendiz): {e}")

        conn.commit()
        print(f"Aprendices procesados: {registros_insertados}")
        
    except ValueError:
        print("Hoja 'aprendices' no encontrada.")
    except Exception as e:
        print(f"Error migrando aprendices: {e}")

    conn.close()
    print("Migración finalizada.")

if __name__ == "__main__":
    migrate()
