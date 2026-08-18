#!/bin/bash

# ============================================================
# CONFIGURAÇÕES GERAIS
# ============================================================

PHP="/usr/bin/php"

ARQUIVO_LOG_MONITOR="/home/proscore/progestor_cli_php/cron_monitor.out"

# PHP responsável pelo envio do alerta
ARQUIVO_ALERTA="/home/proscore/progestor_cli_php/mail_notification.php"


# ============================================================
# FUNÇÃO DE LOG
# ============================================================

log_monitor()
{
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$ARQUIVO_LOG_MONITOR"
}


# ============================================================
# FUNÇÃO PARA ENVIAR E-MAIL
# ============================================================

enviar_alerta()
{
    ACAO="$1"
    MENSAGEM="$2"

    if [ -f "$ARQUIVO_ALERTA" ]
    then

        "$PHP" "$ARQUIVO_ALERTA" "$ACAO" "$MENSAGEM"

    else

        log_monitor "ERRO: Arquivo de alerta não encontrado: $ARQUIVO_ALERTA"

    fi
}


# ============================================================
# 1 - MONITORAMENTO DO PHP CLI
# ============================================================

NOME_PROCESSO_PHP_CLI="/home/proscore/progestor_cli_php/cli.php"

DIRETORIO_PHP_CLI="/home/proscore/progestor_cli_php"

ARQUIVO_LOG_PHP_CLI="/home/proscore/progestor_cli_php/cli_php.out"


if pgrep -f "$NOME_PROCESSO_PHP_CLI" > /dev/null 2>&1
then

    log_monitor "PHP CLI está em execução."

else

    log_monitor "ALERTA: PHP CLI parou! Reiniciando..."

    cd "$DIRETORIO_PHP_CLI" || {

        log_monitor "ERRO: Não foi possível acessar $DIRETORIO_PHP_CLI"

        enviar_alerta "erro" \
        "O PHP CLI parou e não foi possível acessar o diretório para reiniciá-lo."

    }

    nohup env processo_cli_php=1 \
        "$PHP" "$DIRETORIO_PHP_CLI/cli.php" \
        >> "$ARQUIVO_LOG_PHP_CLI" 2>&1 &

    PID_PHP_CLI=$!

    log_monitor "PHP CLI reiniciado. PID: $PID_PHP_CLI"

    sleep 3

    if pgrep -f "$NOME_PROCESSO_PHP_CLI" > /dev/null 2>&1
    then

        log_monitor "PHP CLI reiniciado com sucesso."

        enviar_alerta "reiniciado" \
        "O processo PHP CLI foi identificado como parado e foi reiniciado automaticamente. PID: $PID_PHP_CLI"

    else

        log_monitor "ERRO: PHP CLI não iniciou após tentativa de reinicialização."

        enviar_alerta "erro" \
        "O processo PHP CLI estava parado, mas não foi possível reiniciá-lo."

    fi

fi


# ============================================================
# 2 - MONITORAMENTO DO SERVIDOR PHP - PORTA 9081
# ============================================================

NOME_PROCESSO_PHP_SERVER="0.0.0.0:9081"

DIRETORIO_PHP_SERVER="/home/proscore/page_teste/PROJETO-MONGODB-PHP-POSTGRESQL"

ARQUIVO_LOG_PHP_SERVER="/home/proscore/page_teste/PROJETO-MONGODB-PHP-POSTGRESQL/php_server.out"


if pgrep -f "$NOME_PROCESSO_PHP_SERVER" > /dev/null 2>&1
then

    log_monitor "Servidor PHP porta 9081 está em execução."

else

    log_monitor "ALERTA: Servidor PHP porta 9081 parou! Reiniciando..."

    cd "$DIRETORIO_PHP_SERVER" || {

        log_monitor "ERRO: Não foi possível acessar $DIRETORIO_PHP_SERVER"

        enviar_alerta "erro" \
        "O servidor PHP da porta 9081 parou e não foi possível acessar o diretório para reiniciá-lo."

    }

    nohup "$PHP" \
        -S 0.0.0.0:9081 \
        -t "$DIRETORIO_PHP_SERVER" \
        >> "$ARQUIVO_LOG_PHP_SERVER" 2>&1 &

    PID_PHP_SERVER=$!

    log_monitor "Servidor PHP porta 9081 reiniciado. PID: $PID_PHP_SERVER"

    sleep 3

    if pgrep -f "$NOME_PROCESSO_PHP_SERVER" > /dev/null 2>&1
    then

        log_monitor "Servidor PHP porta 9081 reiniciado com sucesso."

        enviar_alerta "reiniciado" \
        "O servidor PHP da porta 9081 foi identificado como parado e foi reiniciado automaticamente. PID: $PID_PHP_SERVER"

    else

        log_monitor "ERRO: Servidor PHP porta 9081 não iniciou."

        enviar_alerta "erro" \
        "O servidor PHP da porta 9081 estava parado, mas não foi possível reiniciá-lo."

    fi

fi


# ============================================================
# 3 - MONITORAMENTO DO PYTHON
# ============================================================

DIRETORIO_PYTHON="/home/proscore/new_projeto_progestor"

PYTHON="$DIRETORIO_PYTHON/.venv/bin/python"

ARQUIVO_PYTHON="$DIRETORIO_PYTHON/arquivo_initil.py"

ARQUIVO_LOG_PYTHON="$DIRETORIO_PYTHON/NewConectionLogs323.log"

NOME_PROCESSO_PYTHON=".venv/bin/python arquivo_initil.py"


if pgrep -f "$NOME_PROCESSO_PYTHON" > /dev/null 2>&1
then

    log_monitor "Python arquivo_initil.py está em execução."

else

    log_monitor "ALERTA: Python arquivo_initil.py parou! Reiniciando..."

    cd "$DIRETORIO_PYTHON" || {

        log_monitor "ERRO: Não foi possível acessar $DIRETORIO_PYTHON"

        enviar_alerta "erro" \
        "O processo Python arquivo_initil.py parou e não foi possível acessar o diretório para reiniciá-lo."

        return
    }

    nohup "$PYTHON" "$ARQUIVO_PYTHON" \
        >> "$ARQUIVO_LOG_PYTHON" 2>&1 &

    PID_PYTHON=$!

    log_monitor "Python arquivo_initil.py reiniciado. PID: $PID_PYTHON"

    sleep 3

    if pgrep -f "$NOME_PROCESSO_PYTHON" > /dev/null 2>&1
    then

        log_monitor "Python arquivo_initil.py reiniciado com sucesso."

        enviar_alerta "reiniciado" \
        "O processo Python arquivo_initil.py foi identificado como parado e foi reiniciado automaticamente. PID: $PID_PYTHON"

    else

        log_monitor "ERRO: Python arquivo_initil.py não iniciou."

        enviar_alerta "erro" \
        "O processo Python arquivo_initil.py estava parado, mas não foi possível reiniciá-lo."

    fi

fi