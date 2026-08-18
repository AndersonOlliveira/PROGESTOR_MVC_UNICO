#!/bin/bash
# /home/proscore/progestor_cli_php
NOME_PROCESSO="/home/proscore/progestor_cli_php/cli.php"
DIRETORIO_EXECUCAO="/home/proscore/progestor_cli_php"
ARQUIVO_LOG="/home/proscore/progestor_cli_php/cli_php.out"
ARQUIVO_LOG_MONITOR="/home/proscore/progestor_cli_php/cron_monitor.out"
# /home/proscore/progestor_cli_php
PHP="/usr/bin/php"


if pgrep -f "$NOME_PROCESSO" > /dev/null 2>&1
then

    echo "[$(date '+%Y-%m-%d %H:%M:%S')] O processo '$NOME_PROCESSO' está em execução."

else

    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ALERTA: O processo '$NOME_PROCESSO' parou! Reiniciando..." >> "$ARQUIVO_LOG"

    cd "$DIRETORIO_EXECUCAO" || exit 1

     nohup env processo_cli_php=1 "$PHP" "$DIRETORIO_EXECUCAO/cli.php" >> "$ARQUIVO_LOG" 2>&1 &

    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Processo reiniciado. PID: $!" >> "$ARQUIVO_LOG_MONITOR"
    sleep 3
    if pgrep -f "$NOME_PROCESSO" > /dev/null
    then

        $ECHO "[$($DATE '+%d/%m/%Y %H:%M:%S')] Processo reiniciado com sucesso."

        $PHP "$ARQUIVO_ALERTA" reiniciado \
        "O processo '$NOME_PROCESSO' estava parado e foi reiniciado automaticamente."

    else

        $ECHO "[$($DATE '+%d/%m/%Y %H:%M:%S')] ERRO: Não foi possível reiniciar o processo."

       
        $PHP "$ARQUIVO_ALERTA" erro \
        "O processo '$NOME_PROCESSO' estava parado, mas não foi possível reiniciá-lo."

    fi

fi
