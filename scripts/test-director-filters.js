/**
 * Script de Testing para Filtros del Panel del Director
 * SenApre - Testing Automatizado
 */

const testCases = [
    {
        name: 'API Reportes - Endpoint Principal',
        url: 'api/reportes.php',
        method: 'GET',
        expectedFields: ['success', 'data', 'resumen', 'aprendices_estado', 'fichas_programa', 'asistencias_trend'],
        description: 'Verificar que el endpoint principal devuelva todos los campos esperados'
    },
    {
        name: 'API Reportes - Filtro por Ficha',
        url: 'api/reportes.php?ficha=123456',
        method: 'GET',
        expectedFields: ['success', 'data'],
        description: 'Verificar filtrado por número de ficha'
    },
    {
        name: 'API Reportes - Filtro por Población Mujer',
        url: 'api/reportes.php?tabla_poblacion=mujer',
        method: 'GET',
        expectedFields: ['success', 'data'],
        description: 'Verificar filtrado por población vulnerable (mujer)'
    },
    {
        name: 'API Reportes - Filtro por Población Indígena',
        url: 'api/reportes.php?tabla_poblacion=indigena',
        method: 'GET',
        expectedFields: ['success', 'data'],
        description: 'Verificar filtrado por población vulnerable (indígena)'
    },
    {
        name: 'API Reportes - Filtro por Población NARP',
        url: 'api/reportes.php?tabla_poblacion=narp',
        method: 'GET',
        expectedFields: ['success', 'data'],
        description: 'Verificar filtrado por población vulnerable (NARP)'
    },
    {
        name: 'API Reportes - Filtro por Población Campesino',
        url: 'api/reportes.php?tabla_poblacion=campesino',
        method: 'GET',
        expectedFields: ['success', 'data'],
        description: 'Verificar filtrado por población vulnerable (campesino)'
    },
    {
        name: 'API Reportes - Filtro por Población LGBTIQ+',
        url: 'api/reportes.php?tabla_poblacion=lgbtiq',
        method: 'GET',
        expectedFields: ['success', 'data'],
        description: 'Verificar filtrado por población vulnerable (LGBTIQ+)'
    },
    {
        name: 'API Reportes - Filtro por Población Discapacidad',
        url: 'api/reportes.php?tabla_poblacion=discapacidad',
        method: 'GET',
        expectedFields: ['success', 'data'],
        description: 'Verificar filtrado por población vulnerable (discapacidad)'
    }
];

class DirectorFiltersTester {
    constructor() {
        this.results = [];
        this.passedTests = 0;
        this.failedTests = 0;
    }

    async runTest(testCase) {
        console.log(`🧪 Ejecutando: ${testCase.name}`);
        
        try {
            const startTime = performance.now();
            const response = await fetch(testCase.url);
            const endTime = performance.now();
            const responseTime = Math.round(endTime - startTime);
            
            const data = await response.json();
            
            const testResult = {
                name: testCase.name,
                description: testCase.description,
                url: testCase.url,
                status: response.ok ? 'PASS' : 'FAIL',
                statusCode: response.status,
                responseTime: `${responseTime}ms`,
                hasExpectedFields: this.checkExpectedFields(data, testCase.expectedFields),
                data: data
            };
            
            if (data.success && testResult.hasExpectedFields) {
                testResult.status = 'PASS';
                this.passedTests++;
                console.log(`✅ ${testCase.name} - PASÓ (${responseTime}ms)`);
            } else {
                testResult.status = 'FAIL';
                this.failedTests++;
                console.log(`❌ ${testCase.name} - FALLÓ`);
                console.log(`   Status: ${response.status}`);
                console.log(`   Success: ${data.success}`);
                console.log(`   Fields: ${testResult.hasExpectedFields ? 'OK' : 'MISSING'}`);
            }
            
            this.results.push(testResult);
            
        } catch (error) {
            const testResult = {
                name: testCase.name,
                description: testCase.description,
                url: testCase.url,
                status: 'ERROR',
                error: error.message,
                data: null
            };
            
            this.failedTests++;
            this.results.push(testResult);
            console.log(`💥 ${testCase.name} - ERROR: ${error.message}`);
        }
    }

    checkExpectedFields(data, expectedFields) {
        if (!expectedFields || expectedFields.length === 0) return true;
        
        return expectedFields.every(field => {
            const keys = field.split('.');
            let current = data;
            
            for (const key of keys) {
                if (current && typeof current === 'object' && key in current) {
                    current = current[key];
                } else {
                    return false;
                }
            }
            
            return true;
        });
    }

    async runAllTests() {
        console.log('🚀 Iniciando Testing de Filtros del Panel del Director');
        console.log('=' .repeat(60));
        
        for (const testCase of testCases) {
            await this.runTest(testCase);
            // Pequeña pausa entre pruebas
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        
        this.generateReport();
    }

    generateReport() {
        console.log('\n📊 REPORTE DE TESTING');
        console.log('=' .repeat(60));
        
        console.log(`✅ Tests Pasados: ${this.passedTests}`);
        console.log(`❌ Tests Fallidos: ${this.failedTests}`);
        console.log(`📈 Tasa de Éxito: ${((this.passedTests / (this.passedTests + this.failedTests)) * 100).toFixed(1)}%`);
        
        console.log('\n📋 Detalle de Resultados:');
        this.results.forEach(result => {
            const icon = result.status === 'PASS' ? '✅' : result.status === 'FAIL' ? '❌' : '💥';
            console.log(`${icon} ${result.name} - ${result.status}`);
            if (result.responseTime) console.log(`   ⏱️  Tiempo: ${result.responseTime}`);
            if (result.statusCode) console.log(`   🌐 Status: ${result.statusCode}`);
            if (result.error) console.log(`   💥 Error: ${result.error}`);
        });
        
        // Verificación específica de filtros
        this.verifyFilters();
    }

    verifyFilters() {
        console.log('\n🔍 VERIFICACIÓN ESPECÍFICA DE FILTROS');
        console.log('=' .repeat(60));
        
        const mainReport = this.results.find(r => r.url === 'api/reportes.php');
        if (mainReport && mainReport.data && mainReport.data.success) {
            const resumen = mainReport.data.data.resumen;
            
            console.log('📈 Métricas Principales:');
            console.log(`   👥 Aprendices: ${resumen.aprendices || 0}`);
            console.log(`   📚 Fichas: ${resumen.fichas || 0}`);
            console.log(`   👨‍🏫 Usuarios: ${resumen.usuarios || 0}`);
            console.log(`   🎯 Programas: ${resumen.programas || 0}`);
            
            console.log('\n👥 Detalle de Aprendices por Estado:');
            if (mainReport.data.data.aprendices_estado) {
                mainReport.data.data.aprendices_estado.forEach(estado => {
                    console.log(`   ${estado.estado}: ${estado.cantidad}`);
                });
            }
            
            console.log('\n🌍 Población Vulnerable:');
            const poblacionFields = ['mujer', 'indigena', 'narp', 'campesino', 'lgbtiq', 'discapacidad'];
            poblacionFields.forEach(field => {
                const count = resumen[field] || 0;
                console.log(`   ${field.toUpperCase()}: ${count}`);
            });
            
            console.log(`   TOTAL VULNERABLES: ${resumen.total_vulnerables || 0}`);
            
            // Verificar que los filtros de población funcionen
            this.verifyPopulationFilters();
        } else {
            console.log('❌ No se pudo verificar el reporte principal');
        }
    }

    verifyPopulationFilters() {
        console.log('\n🎯 Verificación de Filtros de Población:');
        
        const populationTests = this.results.filter(r => 
            r.url.includes('tabla_poblacion=') && r.status === 'PASS'
        );
        
        if (populationTests.length === 6) {
            console.log('✅ Todos los filtros de población vulnerable funcionan correctamente');
        } else {
            console.log(`⚠️  Solo ${populationTests.length}/6 filtros de población funcionan`);
        }
        
        // Verificar que los filtros den resultados diferentes
        const results = populationTests.map(t => t.data?.data?.resumen?.aprendices || 0);
        const uniqueResults = [...new Set(results)];
        
        if (uniqueResults.length > 1) {
            console.log('✅ Los filtros de población devuelven resultados diferentes (correcto)');
        } else {
            console.log('⚠️  Los filtros de población devuelven el mismo resultado (revisar)');
        }
    }
}

// Ejecutar testing si estamos en el navegador
if (typeof window !== 'undefined') {
    window.DirectorFiltersTester = DirectorFiltersTester;
    
    // Función para ejecutar testing desde la consola
    window.testDirectorFilters = async function() {
        const tester = new DirectorFiltersTester();
        await tester.runAllTests();
    };
    
    console.log('🧪 Testing de Filtros del Director cargado');
    console.log('💡 Ejecuta: testDirectorFilters() para iniciar las pruebas');
}

// Exportar para Node.js si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { DirectorFiltersTester, testCases };
}
