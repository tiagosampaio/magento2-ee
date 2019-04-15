#!/bin/bash
set -e

export VERSION=`jq .[0].release SHOPVERSIONS`

curl -s https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-linux-amd64.zip > ngrok.zip
unzip ngrok.zip
chmod +x $PWD/ngrok

curl -sO http://stedolan.github.io/jq/download/linux64/jq
chmod +x $PWD/jq

$PWD/ngrok authtoken $NGROK_TOKEN
TIMESTAMP=$(date +%s)
$PWD/ngrok http 9090 -subdomain="${TIMESTAMP}-magento2-${GATEWAY}" > /dev/null &

NGROK_URL_S=$(curl -s localhost:4040/api/tunnels/command_line | jq --raw-output .public_url)

while [ ! ${NGROK_URL_S} ] || [ ${NGROK_URL_S} = 'null' ];  do
    echo "Waiting for ngrok to initialize"
    NGROK_URL_S=$(curl -s localhost:4040/api/tunnels/command_line | jq --raw-output .public_url)
    export NGROK_URL = ${NGROK_URL_S/https/http}
    echo $NGROK_URL
    sleep 1
done

bash .bin/start-shopsystem.sh
#vendor/bin/codecept run acceptance --debug --html --xml
