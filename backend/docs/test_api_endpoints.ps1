# Script de Pruebas de API - Sistema de Ventas e Inventario
# Base URL: http://localhost:8000/api/admin

$baseUrl = "http://localhost:8000/api/admin"
$results = @()
$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

function Test-Endpoint {
    param(
        [string]$TestName,
        [string]$Method,
        [string]$Endpoint,
        [object]$Body = $null,
        [hashtable]$Headers = $null
    )
    
    $url = "$baseUrl$Endpoint"
    $testResult = @{
        Test = $TestName
        Method = $Method
        Endpoint = $Endpoint
        Status = $null
        Success = $false
        Response = $null
        Error = $null
        Timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    }
    
    try {
        # Clonar headers para no modificar el original
        if ($Headers) {
            $requestHeaders = @{}
            foreach ($key in $Headers.Keys) {
                $requestHeaders[$key] = $Headers[$key]
            }
        } else {
            $requestHeaders = @{}
            foreach ($key in $headers.Keys) {
                $requestHeaders[$key] = $headers[$key]
            }
        }
        
        # Asegurar que siempre estén los headers correctos
        $requestHeaders["Accept"] = "application/json"
        if ($Method -eq "POST" -or $Method -eq "PUT") {
            $requestHeaders["Content-Type"] = "application/json"
        }
        
        # Preparar el body si existe
        $jsonBody = $null
        if ($Body -and ($Method -eq "POST" -or $Method -eq "PUT")) {
            $jsonBody = ($Body | ConvertTo-Json -Depth 10 -Compress)
        }
        
        # Usar Invoke-WebRequest para tener más control sobre la respuesta
        $webParams = @{
            Uri = $url
            Method = $Method
            Headers = $requestHeaders
            ErrorAction = "Stop"
        }
        
        if ($jsonBody) {
            $webParams["Body"] = $jsonBody
            $webParams["ContentType"] = "application/json"
        }
        
        $webResponse = Invoke-WebRequest @webParams
        $statusCode = [int]$webResponse.StatusCode
        $testResult.Status = $statusCode.ToString()
        
        # Verificar que la respuesta sea JSON
        $contentType = $webResponse.Headers["Content-Type"]
        $isHtml = $webResponse.Content -match "<!DOCTYPE html" -or $webResponse.Content -match "<html"
        
        if ($isHtml) {
            # Es HTML, no debería pasar si el header Accept está correcto
            $testResult.Error = "El servidor devolvio HTML en lugar de JSON (Content-Type: $contentType). Verifique que el endpoint este funcionando correctamente."
            $testResult.Response = $webResponse.Content.Substring(0, [Math]::Min(500, $webResponse.Content.Length))
            $testResult.Success = $false
            Write-Host "[FAIL] $TestName - Devolvio HTML en lugar de JSON" -ForegroundColor Red
        }
        elseif ($contentType -and $contentType -like "*application/json*") {
            try {
                $testResult.Response = $webResponse.Content | ConvertFrom-Json
                if ($statusCode -ge 200 -and $statusCode -lt 300) {
                    $testResult.Success = $true
                    Write-Host "[OK] $TestName" -ForegroundColor Green
                } else {
                    Write-Host "[FAIL] $TestName - Status: $statusCode" -ForegroundColor Yellow
                }
            }
            catch {
                $testResult.Error = "Error al parsear JSON: $($_.Exception.Message)"
                $testResult.Response = $webResponse.Content
                $testResult.Success = $false
                Write-Host "[FAIL] $TestName - Error al parsear JSON" -ForegroundColor Red
            }
        }
        elseif ($webResponse.Content -match "^[\s]*\{") {
            # Parece JSON aunque no tenga el header correcto
            try {
                $testResult.Response = $webResponse.Content | ConvertFrom-Json
                if ($statusCode -ge 200 -and $statusCode -lt 300) {
                    $testResult.Success = $true
                    Write-Host "[OK] $TestName" -ForegroundColor Green
                } else {
                    Write-Host "[FAIL] $TestName - Status: $statusCode" -ForegroundColor Yellow
                }
            }
            catch {
                $testResult.Error = "La respuesta parece JSON pero no se pudo parsear: $($_.Exception.Message)"
                $testResult.Response = $webResponse.Content
                $testResult.Success = $false
                Write-Host "[FAIL] $TestName - Error al parsear JSON" -ForegroundColor Red
            }
        }
        else {
            # Formato desconocido
            $testResult.Error = "Formato de respuesta desconocido (Content-Type: $contentType)"
            $testResult.Response = $webResponse.Content.Substring(0, [Math]::Min(500, $webResponse.Content.Length))
            $testResult.Success = $false
            Write-Host "[FAIL] $TestName - Formato de respuesta desconocido" -ForegroundColor Red
        }
    }
    catch {
        $httpError = $_.Exception.Response
        
        if ($httpError) {
            $statusCode = [int]$httpError.StatusCode
            $testResult.Status = $statusCode.ToString()
            
            try {
                $stream = $httpError.GetResponseStream()
                $reader = New-Object System.IO.StreamReader($stream)
                $responseBody = $reader.ReadToEnd()
                $reader.Close()
                $stream.Close()
                
                # Intentar parsear como JSON
                try {
                    $testResult.Response = $responseBody | ConvertFrom-Json -ErrorAction Stop
                }
                catch {
                    # Si no es JSON, verificar si es HTML (error de validación)
                    if ($responseBody -match "<!DOCTYPE html" -or $responseBody -match "<html") {
                        # Es HTML, probablemente un error de validación o redirección
                        $testResult.Error = "El servidor devolvio HTML en lugar de JSON. Puede ser un error de validacion."
                        $testResult.Response = $null
                    }
                    else {
                        # Es texto plano o error
                        $testResult.Response = $responseBody
                    }
                }
            }
            catch {
                $testResult.Response = $null
                $testResult.Error = "Error al leer respuesta: $($_.Exception.Message)"
            }
            
            if ($statusCode -ge 200 -and $statusCode -lt 300) {
                $testResult.Success = $true
                Write-Host "[OK] $TestName - Status: $statusCode" -ForegroundColor Green
            }
            elseif ($statusCode -eq 404 -and $TestName -like "*404*") {
                $testResult.Success = $true
                Write-Host "[OK] $TestName - Status: 404 (esperado)" -ForegroundColor Green
            }
            elseif ($statusCode -eq 422) {
                # 422 es un error de validación esperado
                Write-Host "[FAIL] $TestName - Status: 422 (Validacion)" -ForegroundColor Yellow
            }
            else {
                Write-Host "[FAIL] $TestName - Status: $statusCode" -ForegroundColor Yellow
            }
        }
        else {
            $testResult.Status = "ERROR"
            $testResult.Error = $_.Exception.Message
            Write-Host "[ERROR] $TestName - $($_.Exception.Message)" -ForegroundColor Red
        }
    }
    
    return $testResult
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "INICIANDO PRUEBAS DE API" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# ============================================
# A. PRODUCTOS
# ============================================
Write-Host "[A] PRODUCTOS" -ForegroundColor Yellow

# Test 1: Listar productos
$result = Test-Endpoint -TestName "Test 1: Listar productos" -Method "GET" -Endpoint "/productos"
$results += $result
$productos = if ($result.Success -and $result.Response.data) { $result.Response.data } else { @() }
$productoId = if ($productos.Count -gt 0) { $productos[0].id } else { 1 }

# Test 2: Ver producto especifico
$result = Test-Endpoint -TestName "Test 2: Ver producto especifico" -Method "GET" -Endpoint "/productos/$productoId"
$results += $result

# Test 2b: Producto inexistente (debe dar 404)
$result = Test-Endpoint -TestName "Test 2b: Producto inexistente (404)" -Method "GET" -Endpoint "/productos/999"
$results += $result

# Test 3: Crear producto
$newProducto = @{
    codigo = "PROD_TEST_$(Get-Date -Format 'yyyyMMddHHmmss')"
    nombre = "Acondicionador Hidratante"
    categoria_id = 1
    precio = 280.00
    costo = 170.00
    inventario_actual = 40
    inventario_minimo = 10
}
$result = Test-Endpoint -TestName "Test 3: Crear producto" -Method "POST" -Endpoint "/productos" -Body $newProducto
$results += $result
$nuevoProductoId = if ($result.Success -and $result.Response.data) { $result.Response.data.id } else { $null }

# Test 4: Actualizar producto (si se creo correctamente)
if ($nuevoProductoId) {
    $updateData = @{
        precio = 270.00
        nombre = "Acondicionador Hidratante Premium"
    }
    $result = Test-Endpoint -TestName "Test 4: Actualizar producto" -Method "PUT" -Endpoint "/productos/$nuevoProductoId" -Body $updateData
    $results += $result
}

# Test 5: Buscar productos
$result = Test-Endpoint -TestName "Test 5: Buscar productos" -Method "GET" -Endpoint "/productos/buscar?q=PROD"
$results += $result

# ============================================
# B. INVENTARIO
# ============================================
Write-Host ""
Write-Host "[B] INVENTARIO" -ForegroundColor Yellow

# Test 6: Entrada manual de inventario
$entrada = @{
    producto_id = $productoId
    cantidad = 10
    motivo = "Compra a proveedor"
    notas = "Compra de reposicion de stock"
}
$result = Test-Endpoint -TestName "Test 6: Entrada manual de inventario" -Method "POST" -Endpoint "/inventario/entrada" -Body $entrada
$results += $result

# Test 7: Salida manual de inventario
$salida = @{
    producto_id = $productoId
    cantidad = 5
    motivo = "Productos danados"
    notas = "Productos vencidos que se retiran del inventario"
}
$result = Test-Endpoint -TestName "Test 7: Salida manual de inventario" -Method "POST" -Endpoint "/inventario/salida" -Body $salida
$results += $result

# Test 8: Ajuste de inventario
$ajuste = @{
    producto_id = $productoId
    cantidad_final = 60
    motivo = "Ajuste por conteo fisico"
    notas = "Ajuste despues de inventario fisico"
}
$result = Test-Endpoint -TestName "Test 8: Ajuste de inventario" -Method "POST" -Endpoint "/inventario/ajuste" -Body $ajuste
$results += $result

# Test 9: Kardex de producto
$result = Test-Endpoint -TestName "Test 9: Kardex de producto" -Method "GET" -Endpoint "/inventario/kardex/$productoId"
$results += $result

# Test 10: Listar movimientos de inventario
$result = Test-Endpoint -TestName "Test 10: Listar movimientos de inventario" -Method "GET" -Endpoint "/inventario/movimientos"
$results += $result

# ============================================
# C. METODOS DE PAGO
# ============================================
Write-Host ""
Write-Host "[C] METODOS DE PAGO" -ForegroundColor Yellow

# Test 11: Listar metodos de pago
$result = Test-Endpoint -TestName "Test 11: Listar metodos de pago" -Method "GET" -Endpoint "/metodos-pago"
$results += $result
$metodosPago = if ($result.Success -and $result.Response.data) { $result.Response.data } else { @() }
$metodoPagoId = if ($metodosPago.Count -gt 0) { $metodosPago[0].id } else { 1 }

# Test 12: Crear metodo de pago
$newMetodo = @{
    nombre = "Tarjeta de Debito"
    codigo = "TARJETA_DEBITO_TEST_$(Get-Date -Format 'yyyyMMddHHmmss')"
    es_efectivo = $false
    activo = $true
    orden = 2
}
$result = Test-Endpoint -TestName "Test 12: Crear metodo de pago" -Method "POST" -Endpoint "/metodos-pago" -Body $newMetodo
$results += $result
$nuevoMetodoId = if ($result.Success -and $result.Response.data) { $result.Response.data.id } else { $null }

# Test 13: Ver metodo de pago
if ($metodoPagoId) {
    $result = Test-Endpoint -TestName "Test 13: Ver metodo de pago" -Method "GET" -Endpoint "/metodos-pago/$metodoPagoId"
    $results += $result
}

# Test 14: Actualizar metodo de pago
if ($metodoPagoId) {
    $updateMetodo = @{
        nombre = "Efectivo Actualizado"
        orden = 1
    }
    $result = Test-Endpoint -TestName "Test 14: Actualizar metodo de pago" -Method "PUT" -Endpoint "/metodos-pago/$metodoPagoId" -Body $updateMetodo
    $results += $result
}

# Test 15: Eliminar metodo de pago (solo si creamos uno nuevo)
if ($nuevoMetodoId) {
    $result = Test-Endpoint -TestName "Test 15: Eliminar metodo de pago" -Method "DELETE" -Endpoint "/metodos-pago/$nuevoMetodoId"
    $results += $result
}

# ============================================
# D. ANTICIPOS
# ============================================
Write-Host ""
Write-Host "[D] ANTICIPOS" -ForegroundColor Yellow

# Test 16: Listar reglas de anticipo
$result = Test-Endpoint -TestName "Test 16: Listar reglas de anticipo" -Method "GET" -Endpoint "/anticipos/reglas"
$results += $result
$reglas = if ($result.Success -and $result.Response.data) { $result.Response.data } else { @() }
$reglaId = if ($reglas.Count -gt 0) { $reglas[0].id } else { 1 }

# Test 17: Crear regla de anticipo
$newRegla = @{
    nombre = "Anticipo ventas altas"
    descripcion = "Ventas mayores a $1000 requieren 50% de anticipo"
    tipo_regla = "monto"
    tipo_calculo = "porcentaje"
    valor_calculo = 50.00
    prioridad = 2
    activo = $true
    regla_monto = @{
        monto_minimo = 1000.00
        monto_maximo = $null
    }
}
$result = Test-Endpoint -TestName "Test 17: Crear regla de anticipo" -Method "POST" -Endpoint "/anticipos/reglas" -Body $newRegla
$results += $result
$nuevaReglaId = if ($result.Success -and $result.Response.data) { $result.Response.data.id } else { $null }

# Test 18: Ver regla de anticipo
if ($reglaId) {
    $result = Test-Endpoint -TestName "Test 18: Ver regla de anticipo" -Method "GET" -Endpoint "/anticipos/reglas/$reglaId"
    $results += $result
}

# Test 19: Actualizar regla de anticipo
if ($reglaId) {
    $updateRegla = @{
        valor_calculo = 35.00
        activo = $true
    }
    $result = Test-Endpoint -TestName "Test 19: Actualizar regla de anticipo" -Method "PUT" -Endpoint "/anticipos/reglas/$reglaId" -Body $updateRegla
    $results += $result
}

# Test 20: Calcular anticipo
$calcularAnticipo = @{
    total = 600.00
    fecha_venta = "2025-01-15"
}
$result = Test-Endpoint -TestName "Test 20: Calcular anticipo (total 600)" -Method "POST" -Endpoint "/anticipos/calcular" -Body $calcularAnticipo
$results += $result

# Test 20b: Calcular anticipo (total menor a 500)
$calcularAnticipo2 = @{
    total = 400.00
    fecha_venta = "2025-01-15"
}
$result = Test-Endpoint -TestName "Test 20b: Calcular anticipo (total 400)" -Method "POST" -Endpoint "/anticipos/calcular" -Body $calcularAnticipo2
$results += $result

# Test 21: Evaluar reglas aplicables
$evaluarReglas = @{
    total = 600.00
    fecha_venta = "2025-01-15"
    servicios = @()
}
$result = Test-Endpoint -TestName "Test 21: Evaluar reglas aplicables" -Method "POST" -Endpoint "/anticipos/evaluar" -Body $evaluarReglas
$results += $result

# ============================================
# E. VENTAS
# ============================================
Write-Host ""
Write-Host "[E] VENTAS" -ForegroundColor Yellow

# Test 22: Listar ventas
$result = Test-Endpoint -TestName "Test 22: Listar ventas" -Method "GET" -Endpoint "/ventas"
$results += $result
$ventas = if ($result.Success -and $result.Response.data) { $result.Response.data } else { @() }
$ventaId = if ($ventas.Count -gt 0) { $ventas[0].id } else { 1 }

# Test 23: Crear venta
$newVenta = @{
    cliente_id = 1
    detalles = @(
        @{
            tipo = "producto"
            producto_id = $productoId
            cantidad = 2
            precio_unitario = 250.00
            descuento = 0
            impuesto = 0
        }
    )
    descuento_general = 0
    impuesto_total = 0
    notas = "Venta de prueba desde API"
}
$result = Test-Endpoint -TestName "Test 23: Crear venta" -Method "POST" -Endpoint "/ventas" -Body $newVenta
$results += $result
$nuevaVentaId = if ($result.Success -and $result.Response.data) { $result.Response.data.id } else { $null }

# Test 24: Ver venta especifica
if ($ventaId) {
    $result = Test-Endpoint -TestName "Test 24: Ver venta especifica" -Method "GET" -Endpoint "/ventas/$ventaId"
    $results += $result
}

# Test 25: Calcular totales de venta
$calcularTotales = @{
    detalles = @(
        @{
            precio_unitario = 250.00
            cantidad = 2
            descuento = 10.00
            impuesto = 0
        },
        @{
            precio_unitario = 350.00
            cantidad = 1
            descuento = 0
            impuesto = 0
        }
    )
    descuento_general = 20.00
}
$result = Test-Endpoint -TestName "Test 25: Calcular totales de venta" -Method "POST" -Endpoint "/ventas/calcular-totales" -Body $calcularTotales
$results += $result

# Test 26: Buscar productos para venta
$result = Test-Endpoint -TestName "Test 26: Buscar productos para venta" -Method "GET" -Endpoint "/ventas/productos/buscar?q=Shampoo"
$results += $result

# Test 27: Actualizar venta
if ($ventaId) {
    $updateVenta = @{
        notas = "Notas actualizadas"
        descuento_general = 50.00
    }
    $result = Test-Endpoint -TestName "Test 27: Actualizar venta" -Method "PUT" -Endpoint "/ventas/$ventaId" -Body $updateVenta
    $results += $result
}

# ============================================
# F. PAGOS
# ============================================
Write-Host ""
Write-Host "[F] PAGOS" -ForegroundColor Yellow

$pagoId = $null

# Test 28: Registrar pago
if ($ventaId -and $metodoPagoId) {
    $registrarPago = @{
        venta_id = $ventaId
        metodo_pago_id = $metodoPagoId
        monto = 180.00
        monto_recibido = 200.00
        notas = "Pago de anticipo"
    }
    $result = Test-Endpoint -TestName "Test 28: Registrar pago" -Method "POST" -Endpoint "/pagos/registrar" -Body $registrarPago
    $results += $result
    if ($result.Success -and $result.Response.data) {
        if ($result.Response.data.pago) {
            $pagoId = $result.Response.data.pago.id
        }
    }
}

# Test 29: Listar pagos de una venta
if ($ventaId) {
    $result = Test-Endpoint -TestName "Test 29: Listar pagos de una venta" -Method "GET" -Endpoint "/pagos/venta/$ventaId"
    $results += $result
}

# Test 30: Reembolsar pago
if ($pagoId) {
    $reembolsarPago = @{
        motivo = "Reembolso solicitado por cliente"
        notas = "Cliente cambio de opinion"
    }
    $result = Test-Endpoint -TestName "Test 30: Reembolsar pago" -Method "POST" -Endpoint "/pagos/$pagoId/reembolsar" -Body $reembolsarPago
    $results += $result
}

# ============================================
# GENERAR REPORTE
# ============================================
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "GENERANDO REPORTE" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$reporte = "# Reporte de Pruebas de API - Sistema de Ventas e Inventario`n`n"
$reporte += "**Fecha de ejecucion:** $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')`n`n"
$reporte += "**Base URL:** $baseUrl`n`n"

$totalTests = $results.Count
$exitosos = ($results | Where-Object { $_.Success }).Count
$fallidos = $totalTests - $exitosos
$porcentaje = if ($totalTests -gt 0) { [math]::Round(($exitosos / $totalTests) * 100, 2) } else { 0 }

$reporte += "## Resumen`n`n"
$reporte += "| Metrica | Cantidad |`n"
$reporte += "|---------|----------|`n"
$reporte += "| Total de pruebas | $totalTests |`n"
$reporte += "| Exitosas | $exitosos |`n"
$reporte += "| Fallidas | $fallidos |`n"
$reporte += "| Porcentaje de exito | ${porcentaje}% |`n`n"

$reporte += "---`n`n"

$reporte += "## Detalle de Pruebas`n`n"

foreach ($test in $results) {
    $statusIcon = if ($test.Success) { "[OK]" } else { "[FAIL]" }
    
    $reporte += "### $statusIcon $($test.Test)`n`n"
    $reporte += "- **Metodo:** $($test.Method)`n"
    $reporte += "- **Endpoint:** $($test.Endpoint)`n"
    $reporte += "- **Status Code:** $($test.Status)`n"
    $reporte += "- **Estado:** $(if ($test.Success) { 'Exitoso' } else { 'Fallido' })`n"
    $reporte += "- **Timestamp:** $($test.Timestamp)`n`n"
    
    if ($test.Error) {
        $reporte += "**Error:**`n```\n$($test.Error)\n```\n`n"
    }
    
    if ($test.Response) {
        try {
            $responseJson = $test.Response | ConvertTo-Json -Depth 5 -Compress
            if ($responseJson.Length -gt 1000) {
                $responseJson = $responseJson.Substring(0, 1000) + "... (truncado)"
            }
            $reporte += "**Respuesta:**`n```json`n$responseJson`n```\n`n"
        }
        catch {
            $reporte += "**Respuesta:** (no se pudo serializar)`n`n"
        }
    }
    
    $reporte += "---`n`n"
}

# Guardar reporte
$reportePath = Join-Path $PSScriptRoot "RESULTADOS_PRUEBAS_API.md"
[System.IO.File]::WriteAllText($reportePath, $reporte, [System.Text.Encoding]::UTF8)

Write-Host "Reporte generado: $reportePath" -ForegroundColor Green
Write-Host ""
Write-Host "Resumen: $exitosos/$totalTests pruebas exitosas ($porcentaje%)" -ForegroundColor $(if ($exitosos -eq $totalTests) { "Green" } else { "Yellow" })
Write-Host ""
