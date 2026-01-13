#!/bin/bash

GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}=== Instalador del Demonio de Monitoreo (EuroTech) ===${NC}"

ARG_ALIAS=$1
ARG_TOKEN=$2
ARG_URL=$3

# 1. Solicitar datos al usuario o usar argumentos
if [[ -n "$ARG_ALIAS" && -n "$ARG_TOKEN" && -n "$ARG_URL" ]]; then
    SERVER_ALIAS="$ARG_ALIAS"
    SERVER_TOKEN="$ARG_TOKEN"
    SERVER_URL="$ARG_URL"
    echo "Usando configuración proporcionada por argumentos."
else
    read -p "Introduce el Alias del servidor (debe ser el mismo que en el panel): " SERVER_ALIAS
    read -p "Introduce la URL del servidor central (ej: http://tu-ip/public): " SERVER_URL
    read -p "Introduce el Token del servidor: " SERVER_TOKEN
fi

if [[ -z "$SERVER_ALIAS" || -z "$SERVER_URL" || -z "$SERVER_TOKEN" ]]; then
    echo -e "${RED}Error: El Alias, la URL y el Token son obligatorios.${NC}"
    exit 1
fi

# Eliminar barra final de la URL si existe
SERVER_URL=$(echo "$SERVER_URL" | sed 's:/*$::')

echo "Configurando archivos para el servidor: $SERVER_ALIAS"

# 2. Crear el script del demonio
CAT_DAEMON_PATH="/usr/local/bin/eurotech-daemon.sh"
sudo bash -c "cat > $CAT_DAEMON_PATH" <<EOF
#!/bin/bash
# Script de monitoreo keep-alive

URL="$SERVER_URL/api/keepalive"
TOKEN="$SERVER_TOKEN"
ALIAS="$SERVER_ALIAS"

echo "Iniciando monitoreo para el servidor: \$ALIAS"

while true; do
    # Enviar peticion POST y capturar respuesta
    FECHA=\$(date '+%Y-%m-%d %H:%M:%S')
    RESPUESTA=\$(curl -s -w "%{http_code}" -X POST -d "token=\$TOKEN" -d "alias=\$ALIAS" "\$URL")
    HTTP_CODE=\${RESPUESTA: -3}
    
    echo "[\$FECHA] Señal enviada. Servidor respondió con HTTP \$HTTP_CODE"
    
    # Enviar cada 30 segundos
    sleep 30
done
EOF

# 3. Crear el script de apagado
CAT_SHUTDOWN_PATH="/usr/local/bin/eurotech-shutdown.sh"
sudo bash -c "cat > $CAT_SHUTDOWN_PATH" <<EOF
#!/bin/bash
# Script de aviso de apagado

URL="$SERVER_URL/api/shutdown"
TOKEN="$SERVER_TOKEN"
ALIAS="$SERVER_ALIAS"

curl -s -X POST -d "token=\$TOKEN" -d "alias=\$ALIAS" "\$URL" > /dev/null
EOF

# Dar permisos de ejecución
sudo chmod +x $CAT_DAEMON_PATH
sudo chmod +x $CAT_SHUTDOWN_PATH

# 4. Crear el servicio de systemd
SERVICE_FILE="/etc/systemd/system/eurotech-monitor.service"
sudo bash -c "cat > $SERVICE_FILE" <<EOF
[Unit]
Description=Demonio de Monitoreo EuroTech ($SERVER_ALIAS)
After=network.target

[Service]
Type=simple
ExecStart=$CAT_DAEMON_PATH
ExecStop=$CAT_SHUTDOWN_PATH
RemainAfterExit=yes
Restart=always

[Install]
WantedBy=multi-user.target
EOF

# 5. Iniciar el servicio
echo "Generando y activando servicio en systemd..."
sudo systemctl daemon-reload
sudo systemctl enable eurotech-monitor
sudo systemctl restart eurotech-monitor

echo -e "${GREEN}Instalación completada correctamente.${NC}"
echo "El servidor '$SERVER_ALIAS' ahora está reportando su estado cada 30 segundos."
echo "Puedes ver el log con: journalctl -u eurotech-monitor -f"



