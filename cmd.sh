#!/bin/bash

case "$1" in
    "up")
        docker-compose up -d web
    ;;
    "down")
        docker-compose down
    ;;
    "js")
        docker-compose run --rm nodejs make js
    ;;
    "css")
        docker-compose run --rm nodejs make css
    ;;
    *)
        echo -e "Usage: $0 COMMAND\n" \
            "\nCommands:\n" \
            " up    Start service\n" \
            " down  Stop service\n" \
            " js    Compile JS\n" \
            " css   Compile CSS"
    ;;
esac
