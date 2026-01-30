#!/bin/bash

# Script de corrección rápida para problemas de carga de archivos
# Ejecutar como: sudo bash fix_upload_issues.sh

echo "=================================================="
echo "  Script de Corrección: Problemas de Carga"
echo "=================================================="
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Detectar directorio del proyecto
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
UPLOAD_DIR="$SCRIPT_DIR/public/assets/uploads"
EVIDENCIAS_DIR="$UPLOAD_DIR/evidencias"

echo "Directorio del proyecto: $SCRIPT_DIR"
echo ""

# 1. Crear directorios si no existen
echo "📁 1. Verificando directorios..."
if [ ! -d "$UPLOAD_DIR" ]; then
    echo -e "${YELLOW}   Creando directorio: $UPLOAD_DIR${NC}"
    mkdir -p "$UPLOAD_DIR"
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}   ✓ Directorio creado${NC}"
    else
        echo -e "${RED}   ✗ Error al crear directorio${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}   ✓ Directorio uploads existe${NC}"
fi

if [ ! -d "$EVIDENCIAS_DIR" ]; then
    echo -e "${YELLOW}   Creando directorio: $EVIDENCIAS_DIR${NC}"
    mkdir -p "$EVIDENCIAS_DIR"
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}   ✓ Directorio creado${NC}"
    else
        echo -e "${RED}   ✗ Error al crear directorio${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}   ✓ Directorio evidencias existe${NC}"
fi

echo ""

# 2. Establecer permisos
echo "🔐 2. Configurando permisos..."
chmod -R 0755 "$UPLOAD_DIR"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}   ✓ Permisos 0755 establecidos${NC}"
else
    echo -e "${YELLOW}   ⚠ Intento con permisos 0777...${NC}"
    chmod -R 0777 "$UPLOAD_DIR"
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}   ✓ Permisos 0777 establecidos${NC}"
    else
        echo -e "${RED}   ✗ Error al establecer permisos${NC}"
        exit 1
    fi
fi

echo ""

# 3. Establecer propietario (www-data para Apache/Nginx)
echo "👤 3. Configurando propietario..."
WEB_USER="www-data"

# Detectar usuario del servidor web
if id "www-data" &>/dev/null; then
    WEB_USER="www-data"
elif id "apache" &>/dev/null; then
    WEB_USER="apache"
elif id "nginx" &>/dev/null; then
    WEB_USER="nginx"
fi

echo "   Usuario del servidor web detectado: $WEB_USER"
chown -R $WEB_USER:$WEB_USER "$UPLOAD_DIR"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}   ✓ Propietario establecido a $WEB_USER${NC}"
else
    echo -e "${YELLOW}   ⚠ No se pudo cambiar propietario (puede requerir sudo)${NC}"
fi

echo ""

# 4. Verificar PHP
echo "🔧 4. Verificando configuración PHP..."

PHP_INI=$(php --ini | grep "Loaded Configuration File" | cut -d ":" -f 2 | xargs)
if [ -n "$PHP_INI" ]; then
    echo "   Archivo php.ini: $PHP_INI"
    
    UPLOAD_MAX=$(php -r "echo ini_get('upload_max_filesize');")
    POST_MAX=$(php -r "echo ini_get('post_max_size');")
    FILE_UPLOADS=$(php -r "echo ini_get('file_uploads') ? 'On' : 'Off';")
    
    echo "   - file_uploads: $FILE_UPLOADS"
    echo "   - upload_max_filesize: $UPLOAD_MAX"
    echo "   - post_max_size: $POST_MAX"
    
    if [ "$FILE_UPLOADS" = "Off" ]; then
        echo -e "${RED}   ✗ CRÍTICO: file_uploads está deshabilitado${NC}"
        echo "   Edita $PHP_INI y cambia: file_uploads = On"
    else
        echo -e "${GREEN}   ✓ file_uploads habilitado${NC}"
    fi
    
    # Convertir a MB para comparación
    UPLOAD_MB=$(echo $UPLOAD_MAX | sed 's/[^0-9]//g')
    POST_MB=$(echo $POST_MAX | sed 's/[^0-9]//g')
    
    if [ "$UPLOAD_MB" -lt 20 ]; then
        echo -e "${YELLOW}   ⚠ upload_max_filesize es menor a 20M (actual: $UPLOAD_MAX)${NC}"
        echo "   Recomendado: upload_max_filesize = 20M"
    else
        echo -e "${GREEN}   ✓ upload_max_filesize adecuado${NC}"
    fi
    
    if [ "$POST_MB" -lt 25 ]; then
        echo -e "${YELLOW}   ⚠ post_max_size es menor a 25M (actual: $POST_MAX)${NC}"
        echo "   Recomendado: post_max_size = 25M"
    else
        echo -e "${GREEN}   ✓ post_max_size adecuado${NC}"
    fi
else
    echo -e "${YELLOW}   ⚠ No se pudo detectar php.ini${NC}"
fi

echo ""

# 5. Verificar directorio temporal
echo "📂 5. Verificando directorio temporal..."
TMP_DIR=$(php -r "echo sys_get_temp_dir();")
echo "   Ubicación: $TMP_DIR"

if [ -w "$TMP_DIR" ]; then
    echo -e "${GREEN}   ✓ Directorio temporal escribible${NC}"
else
    echo -e "${RED}   ✗ Directorio temporal NO escribible${NC}"
    echo "   Ejecuta: sudo chmod 1777 $TMP_DIR"
fi

echo ""

# 6. Estado final
echo "📊 6. Verificación final..."
ls -la "$UPLOAD_DIR" | head -5
echo ""
ls -la "$EVIDENCIAS_DIR" | head -5
echo ""

# 7. Reiniciar servidor web
echo "🔄 7. Reiniciando servidor web..."
echo "   Selecciona tu servidor web:"
echo "   1) Apache"
echo "   2) Nginx"
echo "   3) Ambos (Apache + Nginx)"
echo "   4) Omitir (reiniciar manualmente)"
read -p "   Opción [1-4]: " SERVER_OPTION

case $SERVER_OPTION in
    1)
        systemctl restart apache2
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}   ✓ Apache reiniciado${NC}"
        else
            service apache2 restart
            echo -e "${GREEN}   ✓ Apache reiniciado (service)${NC}"
        fi
        ;;
    2)
        systemctl restart nginx
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}   ✓ Nginx reiniciado${NC}"
        fi
        # Reiniciar PHP-FPM también
        PHP_FPM_SERVICE=$(systemctl list-units --type=service | grep php.*fpm | awk '{print $1}' | head -1)
        if [ -n "$PHP_FPM_SERVICE" ]; then
            systemctl restart $PHP_FPM_SERVICE
            echo -e "${GREEN}   ✓ PHP-FPM reiniciado${NC}"
        fi
        ;;
    3)
        systemctl restart apache2
        systemctl restart nginx
        PHP_FPM_SERVICE=$(systemctl list-units --type=service | grep php.*fpm | awk '{print $1}' | head -1)
        if [ -n "$PHP_FPM_SERVICE" ]; then
            systemctl restart $PHP_FPM_SERVICE
        fi
        echo -e "${GREEN}   ✓ Servidores reiniciados${NC}"
        ;;
    4)
        echo -e "${YELLOW}   ⚠ Recuerda reiniciar el servidor manualmente${NC}"
        ;;
esac

echo ""
echo "=================================================="
echo -e "${GREEN}✓ Corrección completada${NC}"
echo "=================================================="
echo ""
echo "Próximos pasos:"
echo "1. Accede a: https://tudominio.com/sistema/public/test_upload.php"
echo "2. Verifica que todo esté en verde"
echo "3. Prueba subir un archivo de prueba"
echo ""
echo "Si el problema persiste:"
echo "- Revisa los logs: tail -f /var/log/apache2/error.log"
echo "- Edita php.ini manualmente si es necesario"
echo "- Contacta al administrador del servidor"
echo ""
