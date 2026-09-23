#!/bin/sh
set -e

tentativas=0
until php bin/migrate.php; do
    tentativas=$((tentativas + 1))
    if [ "$tentativas" -ge 20 ]; then
        echo "Banco de dados indisponível."
        exit 1
    fi
    echo "Aguardando o banco de dados..."
    sleep 3
done

exec "$@"
