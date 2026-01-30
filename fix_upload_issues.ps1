# Script de corrección rápida para problemas de carga de archivos en Windows
# Ejecutar como Administrador: powershell -ExecutionPolicy Bypass -File fix_upload_issues.ps1

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "  Script de Corrección: Problemas de Carga (Windows)" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host ""

# Detectar directorio del proyecto
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$UploadDir = Join-Path $ScriptDir "public\assets\uploads"
$EvidenciasDir = Join-Path $UploadDir "evidencias"

Write-Host "Directorio del proyecto: $ScriptDir"
Write-Host ""

# 1. Crear directorios si no existen
Write-Host "📁 1. Verificando directorios..." -ForegroundColor Yellow
if (-not (Test-Path $UploadDir)) {
    Write-Host "   Creando directorio: $UploadDir" -ForegroundColor Yellow
    New-Item -ItemType Directory -Path $UploadDir -Force | Out-Null
    if ($?) {
        Write-Host "   ✓ Directorio creado" -ForegroundColor Green
    } else {
        Write-Host "   ✗ Error al crear directorio" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "   ✓ Directorio uploads existe" -ForegroundColor Green
}

if (-not (Test-Path $EvidenciasDir)) {
    Write-Host "   Creando directorio: $EvidenciasDir" -ForegroundColor Yellow
    New-Item -ItemType Directory -Path $EvidenciasDir -Force | Out-Null
    if ($?) {
        Write-Host "   ✓ Directorio creado" -ForegroundColor Green
    } else {
        Write-Host "   ✗ Error al crear directorio" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "   ✓ Directorio evidencias existe" -ForegroundColor Green
}

Write-Host ""

# 2. Establecer permisos (Windows)
Write-Host "🔐 2. Configurando permisos..." -ForegroundColor Yellow
try {
    # Dar permisos completos al usuario actual
    $acl = Get-Acl $UploadDir
    $currentUser = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
    $accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule(
        $currentUser,
        "FullControl",
        "ContainerInherit,ObjectInherit",
        "None",
        "Allow"
    )
    $acl.SetAccessRule($accessRule)
    
    # Agregar permisos para IIS_IUSRS si existe (IIS)
    try {
        $iisUser = "BUILTIN\IIS_IUSRS"
        $iisAccessRule = New-Object System.Security.AccessControl.FileSystemAccessRule(
            $iisUser,
            "Modify",
            "ContainerInherit,ObjectInherit",
            "None",
            "Allow"
        )
        $acl.SetAccessRule($iisAccessRule)
        Write-Host "   ✓ Permisos para IIS_IUSRS configurados" -ForegroundColor Green
    } catch {
        Write-Host "   ⚠ No se pudo configurar IIS_IUSRS (normal si no usas IIS)" -ForegroundColor Yellow
    }
    
    # Agregar permisos para IUSR si existe (IIS)
    try {
        $iusr = "BUILTIN\IUSR"
        $iusrAccessRule = New-Object System.Security.AccessControl.FileSystemAccessRule(
            $iusr,
            "Modify",
            "ContainerInherit,ObjectInherit",
            "None",
            "Allow"
        )
        $acl.SetAccessRule($iusrAccessRule)
        Write-Host "   ✓ Permisos para IUSR configurados" -ForegroundColor Green
    } catch {
        Write-Host "   ⚠ No se pudo configurar IUSR (normal si no usas IIS)" -ForegroundColor Yellow
    }
    
    Set-Acl $UploadDir $acl
    Write-Host "   ✓ Permisos establecidos correctamente" -ForegroundColor Green
} catch {
    Write-Host "   ✗ Error al establecer permisos: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# 3. Verificar PHP
Write-Host "🔧 3. Verificando configuración PHP..." -ForegroundColor Yellow

try {
    $phpPath = (Get-Command php -ErrorAction Stop).Source
    Write-Host "   PHP encontrado: $phpPath" -ForegroundColor Green
    
    $uploadMax = php -r "echo ini_get('upload_max_filesize');"
    $postMax = php -r "echo ini_get('post_max_size');"
    $fileUploads = php -r "echo ini_get('file_uploads') ? 'On' : 'Off';"
    
    Write-Host "   - file_uploads: $fileUploads"
    Write-Host "   - upload_max_filesize: $uploadMax"
    Write-Host "   - post_max_size: $postMax"
    
    if ($fileUploads -eq "Off") {
        Write-Host "   ✗ CRÍTICO: file_uploads está deshabilitado" -ForegroundColor Red
        Write-Host "   Edita php.ini y cambia: file_uploads = On" -ForegroundColor Yellow
    } else {
        Write-Host "   ✓ file_uploads habilitado" -ForegroundColor Green
    }
    
    # Convertir a MB para comparación
    $uploadMB = [int]($uploadMax -replace '[^0-9]','')
    $postMB = [int]($postMax -replace '[^0-9]','')
    
    if ($uploadMB -lt 20) {
        Write-Host "   ⚠ upload_max_filesize es menor a 20M (actual: $uploadMax)" -ForegroundColor Yellow
        Write-Host "   Recomendado: upload_max_filesize = 20M" -ForegroundColor Yellow
    } else {
        Write-Host "   ✓ upload_max_filesize adecuado" -ForegroundColor Green
    }
    
    if ($postMB -lt 25) {
        Write-Host "   ⚠ post_max_size es menor a 25M (actual: $postMax)" -ForegroundColor Yellow
        Write-Host "   Recomendado: post_max_size = 25M" -ForegroundColor Yellow
    } else {
        Write-Host "   ✓ post_max_size adecuado" -ForegroundColor Green
    }
    
    # Mostrar ubicación de php.ini
    $phpIni = php --ini | Select-String "Loaded Configuration File" | ForEach-Object { $_.Line -replace ".*:\s*", "" }
    if ($phpIni) {
        Write-Host "   Archivo php.ini: $phpIni" -ForegroundColor Cyan
    }
    
} catch {
    Write-Host "   ✗ PHP no encontrado o no está en PATH" -ForegroundColor Red
    Write-Host "   Asegúrate de tener PHP instalado y agregado al PATH" -ForegroundColor Yellow
}

Write-Host ""

# 4. Verificar directorio temporal
Write-Host "📂 4. Verificando directorio temporal..." -ForegroundColor Yellow
try {
    $tmpDir = php -r "echo sys_get_temp_dir();"
    Write-Host "   Ubicación: $tmpDir"
    
    if (Test-Path $tmpDir) {
        try {
            $testFile = Join-Path $tmpDir "test_write_$(Get-Random).tmp"
            "test" | Out-File -FilePath $testFile -ErrorAction Stop
            Remove-Item $testFile -ErrorAction SilentlyContinue
            Write-Host "   ✓ Directorio temporal escribible" -ForegroundColor Green
        } catch {
            Write-Host "   ✗ Directorio temporal NO escribible" -ForegroundColor Red
        }
    } else {
        Write-Host "   ✗ Directorio temporal no existe" -ForegroundColor Red
    }
} catch {
    Write-Host "   ⚠ No se pudo verificar directorio temporal" -ForegroundColor Yellow
}

Write-Host ""

# 5. Estado final
Write-Host "📊 5. Verificación final..." -ForegroundColor Yellow
Get-ChildItem $UploadDir | Select-Object Mode, LastWriteTime, Length, Name | Format-Table -AutoSize
Write-Host ""

# 6. Reiniciar IIS si está disponible
Write-Host "🔄 6. Reiniciando servidor web..." -ForegroundColor Yellow
$restartOption = Read-Host "¿Reiniciar IIS? (S/N)"
if ($restartOption -eq "S" -or $restartOption -eq "s") {
    try {
        iisreset
        Write-Host "   ✓ IIS reiniciado" -ForegroundColor Green
    } catch {
        Write-Host "   ⚠ No se pudo reiniciar IIS o no está instalado" -ForegroundColor Yellow
        Write-Host "   Si usas otro servidor (XAMPP, WAMP), reinícialo manualmente" -ForegroundColor Yellow
    }
} else {
    Write-Host "   ⚠ Recuerda reiniciar el servidor web manualmente" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "✓ Corrección completada" -ForegroundColor Green
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Próximos pasos:"
Write-Host "1. Accede a: http://localhost/activistas/test_upload.php"
Write-Host "   (ajusta la URL según tu configuración)"
Write-Host "2. Verifica que todo esté en verde"
Write-Host "3. Prueba subir un archivo de prueba"
Write-Host ""
Write-Host "Si el problema persiste:"
Write-Host "- Revisa los logs del servidor web"
Write-Host "- Edita php.ini manualmente si es necesario:"
Write-Host "  * upload_max_filesize = 20M"
Write-Host "  * post_max_size = 25M"
Write-Host "  * file_uploads = On"
Write-Host ""
Write-Host "Presiona cualquier tecla para salir..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
