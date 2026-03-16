<!DOCTYPE html>
<html>
<head>
    <title>Test Población</title>
</head>
<body>
    <h1>Test de Funcionalidad Población</h1>
    
    <div id="results"></div>
    
    <script>
        async function testAPI() {
            const results = document.getElementById('results');
            
            try {
                // Test 1: Cargar estadísticas
                console.log('Test 1: Cargando estadísticas...');
                const res = await fetch('api/liderazgo.php?action=getPoblacionStats');
                const data = await res.json();
                
                results.innerHTML += '<h2>✅ API Stats funcionando</h2>';
                results.innerHTML += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                
                // Test 2: Cargar aprendices
                console.log('Test 2: Cargando aprendices...');
                const res2 = await fetch('api/aprendices.php?estado=LECTIVA&limit=5');
                const data2 = await res2.json();
                
                results.innerHTML += '<h2>✅ API Aprendices funcionando</h2>';
                results.innerHTML += '<pre>' + JSON.stringify(data2, null, 2) + '</pre>';
                
                // Test 3: Cargar JavaScript
                console.log('Test 3: Cargando JavaScript...');
                const script = document.createElement('script');
                script.src = 'js/poblacion.js';
                script.onload = () => {
                    results.innerHTML += '<h2>✅ JavaScript cargado</h2>';
                    results.innerHTML += '<p>poblacionManager disponible: ' + (typeof poblacionManager !== 'undefined') + '</p>';
                };
                document.head.appendChild(script);
                
            } catch (error) {
                results.innerHTML += '<h2>❌ Error</h2>';
                results.innerHTML += '<p>' + error.message + '</p>';
            }
        }
        
        testAPI();
    </script>
</body>
</html>
