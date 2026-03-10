"""
Script unificado: Limpia la BD y ejecuta la migración en un solo paso
"""
import pandas as pd
import sqlite3
import os

# Configuración
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
EXCEL_FILE = os.path.join(BASE_DIR, 'database', 'Aprendices.xlsx')
DB_FILE = os.path.join(BASE_DIR, 'database', 'Asistnet.db')

def limpiar_y_migrar():
    print("="*60)
    print("LIMPIEZA Y MIGRACIÓN COMPLETA")
    print("="*60)
    
    conn = sqlite3.connect(DB_FILE)
    cursor = conn.cursor()
    
    try:
        # PASO 1: Limpiar tablas
        print("\n[1/4] Limpiando tablas...")
        cursor.execute("PRAGMA foreign_keys=OFF")
        cursor.execute("DELETE FROM asistencias")
        cursor.execute("DELETE FROM aprendices")
        cursor.execute("DELETE FROM fichas")
        cursor.execute("DELETE FROM programas_formacion")
        cursor.execute("DELETE FROM sqlite_sequence WHERE name IN ('asistencias', 'aprendices', 'fichas', 'programas_formacion')")
        conn.commit()
        print("✓ Tablas limpiadas")
        
        # PASO 2: Migrar Programas
        print("\n[2/4] Migrando Programas de Formación...")
        df_prog = pd.read_excel(EXCEL_FILE, sheet_name='programas_formacion')
        df_prog.columns = [c.strip() for c in df_prog.columns]
        
        for _, row in df_prog.iterrows():
            nombre = row.get('nombre_programa', 'NO DEFINIDO')
            nivel = row.get('nivel_formacion', 'NO DEFINIDO')
            cursor.execute("SELECT id_programa FROM programas_formacion WHERE nombre_programa = ? AND nivel_formacion = ?", (nombre, nivel))
            if not cursor.fetchone():
                cursor.execute("INSERT INTO programas_formacion (nombre_programa, nivel_formacion) VALUES (?, ?)", (nombre, nivel))
        conn.commit()
        cursor.execute("SELECT COUNT(*) FROM programas_formacion")
        print(f"✓ {cursor.fetchone()[0]} programas migrados")
        
        # PASO 3: Migrar Fichas
        print("\n[3/4] Migrando Fichas...")
        df_fichas = pd.read_excel(EXCEL_FILE, sheet_name='fichas')
        df_fichas.columns = [c.strip() for c in df_fichas.columns]
        
        for _, row in df_fichas.iterrows():
            numero_ficha = str(row.get('numero_ficha')).strip()
            if numero_ficha.endswith('.0'): numero_ficha = numero_ficha[:-2]
            
            jornada = row.get('jornada')
            if pd.isna(jornada):
                jornada = 'NO DEFINIDA'
            estado = row.get('estado', 'ACTIVO')
            
            id_programa = row.get('id_programa')
            if id_programa:
                try:
                    id_programa = int(float(id_programa))
                except:
                    id_programa = 1
                cursor.execute("SELECT id_programa FROM programas_formacion WHERE id_programa = ?", (id_programa,))
                if not cursor.fetchone():
                    id_programa = 1
            else:
                id_programa = 1
            
            # Intentar usar el número de ficha como ID
            try:
                id_ficha_real = int(numero_ficha)
            except:
                id_ficha_real = None
            
            cursor.execute("SELECT id_ficha FROM fichas WHERE numero_ficha = ?", (numero_ficha,))
            if not cursor.fetchone():
                if id_ficha_real:
                    cursor.execute("INSERT INTO fichas (id_ficha, numero_ficha, id_programa, jornada, estado) VALUES (?, ?, ?, ?, ?)", 
                                 (id_ficha_real, numero_ficha, id_programa, jornada, estado))
                else:
                    cursor.execute("INSERT INTO fichas (numero_ficha, id_programa, jornada, estado) VALUES (?, ?, ?, ?)", 
                                 (numero_ficha, id_programa, jornada, estado))
        conn.commit()
        cursor.execute("SELECT COUNT(*) FROM fichas")
        print(f"✓ {cursor.fetchone()[0]} fichas migradas")
        
        # PASO 4: Migrar Aprendices
        print("\n[4/4] Migrando Aprendices...")
        df_aprendices = pd.read_excel(EXCEL_FILE, sheet_name='aprendices')
        df_aprendices.columns = [c.strip() for c in df_aprendices.columns]
        
        registros_insertados = 0
        
        for index, row in df_aprendices.iterrows():
            try:
                documento = str(row.get('documento')).strip()
                if 'E' in documento or '.' in documento:
                    try: documento = str(int(float(documento)))
                    except: pass
                
                nombre = row.get('nombre', '').strip()
                apellido = row.get('apellido', '').strip()
                correo = row.get('correo', '').strip()
                
                # Leer celular
                try:
                    celular_val = row['celular']
                    if pd.isna(celular_val):
                        celular = None
                    else:
                        celular = str(celular_val).strip()
                        if celular.endswith('.0'): celular = celular[:-2]
                except (KeyError, IndexError):
                    celular = None
                
                # Leer tipo_identificacion
                try:
                    tipo_val = row['tipo_identificacion']
                    if pd.isna(tipo_val):
                        tipo_identificacion = 'CC'
                    else:
                        tipo_identificacion = str(tipo_val).strip()
                except (KeyError, IndexError):
                    tipo_identificacion = 'CC'
                
                # DEBUG: Primeros 3 registros
                if registros_insertados < 3:
                    print(f"\n  [DEBUG] Registro {registros_insertados + 1}:")
                    print(f"    Doc: {documento}, Nombre: {nombre}")
                    print(f"    Tipo ID: {tipo_identificacion}, Celular: {celular}")
                
                # Buscar ficha
                numero_ficha_excel = row.get('id_ficha')
                if pd.isna(numero_ficha_excel):
                    continue
                
                numero_ficha_excel = str(numero_ficha_excel).strip()
                if numero_ficha_excel.endswith('.0'): numero_ficha_excel = numero_ficha_excel[:-2]
                
                cursor.execute("SELECT id_ficha FROM fichas WHERE numero_ficha = ?", (numero_ficha_excel,))
                res = cursor.fetchone()
                if not res:
                    continue
                
                id_ficha_db = res[0]
                estado_val = row.get('estado', 1)
                
                # Insertar
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
                print(f"  Error en fila {index}: {e}")
        
        conn.commit()
        cursor.execute("SELECT COUNT(*) FROM aprendices")
        print(f"\n✓ {cursor.fetchone()[0]} aprendices migrados")
        
        # Verificar columnas
        print("\n" + "="*60)
        print("VERIFICACIÓN")
        print("="*60)
        cursor.execute("SELECT COUNT(*) FROM aprendices WHERE tipo_identificacion IS NOT NULL")
        con_tipo = cursor.fetchone()[0]
        cursor.execute("SELECT COUNT(*) FROM aprendices WHERE celular IS NOT NULL")
        con_celular = cursor.fetchone()[0]
        cursor.execute("SELECT COUNT(*) FROM aprendices")
        total = cursor.fetchone()[0]
        
        print(f"✓ Aprendices con tipo_identificacion: {con_tipo}/{total}")
        print(f"✓ Aprendices con celular: {con_celular}/{total}")
        
        print("\n✅ MIGRACIÓN COMPLETADA EXITOSAMENTE")
        
    except Exception as e:
        conn.rollback()
        print(f"\n❌ Error: {e}")
        import traceback
        traceback.print_exc()
    finally:
        cursor.execute("PRAGMA foreign_keys=ON")
        conn.close()

if __name__ == "__main__":
    limpiar_y_migrar()
