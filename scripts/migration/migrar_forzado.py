"""
Script de migración con cierre forzado de conexiones
"""
import pandas as pd
import sqlite3
import os
import time

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
EXCEL_FILE = os.path.join(BASE_DIR, 'database', 'Aprendices.xlsx')
DB_FILE = os.path.join(BASE_DIR, 'database', 'Asistnet.db')

def migrar_forzado():
    print("="*70)
    print("MIGRACIÓN CON CIERRE FORZADO DE CONEXIONES")
    print("="*70)
    
    # Intentar cerrar conexiones existentes
    print("\n[0/4] Cerrando conexiones existentes...")
    try:
        # Crear una conexión temporal para forzar el cierre
        temp_conn = sqlite3.connect(DB_FILE, timeout=1)
        temp_conn.close()
        print("✓ Conexiones cerradas")
    except Exception as e:
        print(f"⚠️  Advertencia: {e}")
        print("Intentando continuar de todas formas...")
    
    time.sleep(0.5)
    
    # Abrir con timeout más largo
    conn = sqlite3.connect(DB_FILE, timeout=30.0)
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
        df_prog.columns = [str(c).strip() if c is not None else '' for c in df_prog.columns]
        
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
        df_fichas.columns = [str(c).strip() if c is not None else '' for c in df_fichas.columns]
        
        for _, row in df_fichas.iterrows():
            numero_ficha = str(row.get('numero_ficha', '')).strip()
            if numero_ficha.endswith('.0'): numero_ficha = numero_ficha[:-2]
            
            # Convertir jornada a string
            jornada_val = row.get('jornada')
            if pd.isna(jornada_val):
                jornada = 'NO DEFINIDA'
            else:
                jornada = str(jornada_val)
            
            # Convertir estado a string
            estado_val = row.get('estado', 'ACTIVO')
            if pd.isna(estado_val):
                estado = 'ACTIVO'
            else:
                estado = str(estado_val)
            
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
            
            cursor.execute("SELECT id_ficha FROM fichas WHERE numero_ficha = ?", (numero_ficha,))
            if not cursor.fetchone():
                cursor.execute("INSERT INTO fichas (numero_ficha, id_programa, jornada, estado) VALUES (?, ?, ?, ?)", 
                             (numero_ficha, id_programa, jornada, estado))
        conn.commit()
        cursor.execute("SELECT COUNT(*) FROM fichas")
        print(f"✓ {cursor.fetchone()[0]} fichas migradas")
        
        # PASO 4: Migrar Aprendices
        print("\n[4/4] Migrando Aprendices...")
        df_aprendices = pd.read_excel(EXCEL_FILE, sheet_name='aprendices')
        df_aprendices.columns = [str(c).strip() if c is not None else '' for c in df_aprendices.columns]
        
        registros_insertados = 0
        estados_migrados = {}
        
        for index, row in df_aprendices.iterrows():
            try:
                # Convertir a string de forma segura
                documento = str(row.get('documento', '')).strip()
                if 'E' in documento or '.' in documento:
                    try: documento = str(int(float(documento)))
                    except: pass
                
                nombre = str(row.get('nombre', '')).strip()
                apellido = str(row.get('apellido', '')).strip()
                correo = str(row.get('correo', '')).strip()
                
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
                estado_val = row.get('estado', 'EN FORMACION')
                
                # Contar estados
                if estado_val not in estados_migrados:
                    estados_migrados[estado_val] = 0
                estados_migrados[estado_val] += 1
                
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
        total_aprendices = cursor.fetchone()[0]
        print(f"✓ {total_aprendices} aprendices migrados")
        
        # Verificar estados en BD
        print("\n" + "="*70)
        print("VERIFICACIÓN DE ESTADOS")
        print("="*70)
        cursor.execute("SELECT estado, COUNT(*) FROM aprendices GROUP BY estado ORDER BY COUNT(*) DESC")
        print("\nEstados en la base de datos:")
        for estado, count in cursor.fetchall():
            print(f"  {estado}: {count}")
        
        # Verificar columnas
        print("\n" + "="*70)
        print("VERIFICACIÓN DE COLUMNAS")
        print("="*70)
        cursor.execute("SELECT COUNT(*) FROM aprendices WHERE tipo_identificacion IS NOT NULL")
        con_tipo = cursor.fetchone()[0]
        cursor.execute("SELECT COUNT(*) FROM aprendices WHERE celular IS NOT NULL")
        con_celular = cursor.fetchone()[0]
        
        print(f"✓ Aprendices con tipo_identificacion: {con_tipo}/{total_aprendices}")
        print(f"✓ Aprendices con celular: {con_celular}/{total_aprendices}")
        
        print("\n" + "="*70)
        print("✅ MIGRACIÓN COMPLETADA EXITOSAMENTE")
        print("="*70)
        
    except Exception as e:
        conn.rollback()
        print(f"\n❌ Error: {e}")
        import traceback
        traceback.print_exc()
    finally:
        cursor.execute("PRAGMA foreign_keys=ON")
        conn.close()

if __name__ == "__main__":
    migrar_forzado()
