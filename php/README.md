# DenisBank API

API de l'application DenisBank (ancêtre de [CandleVault](https://github.com/Mathieu2301/CandleVault)).

## Développement

1. Lancer un conteneur MariaDB sur le réseau 'mariadb'. Exemple de docker-compose:

    ```yml
    version: '3.1'

    services:
      mariadb:
        image: mariadb:latest
        restart: always
        environment:
          MARIADB_ROOT_PASSWORD: example
        hostname: dev.mysql
        volumes:
          - data:/var/lib/mysql
      phpmyadmin:
        image: phpmyadmin
        restart: always
        environment:
          PMA_HOST: dev.mysql
          UPLOAD_LIMIT: 50000000
          MEMORY_LIMIT: 50000000
        ports:
          - 3333:80

    networks:
      default:
        name: mariadb
        attachable: true

    volumes:
      data:
    ```

2. (conseillé) Créer une base de données `denisbank` et un utilisateur du même nom avec des droits SELECT, INSERT, UPDATE et DELETE.
3. Créer un fichier `.env` et le remplir avec ces informations:

    ```env
    MYSQL_HOST=dev.mysql
    MYSQL_USER=denisbank
    MYSQL_PASS=denisbank
    MYSQL_DB=denisbank
    FCM_KEY=AAAA...
    ```

    La variable `FCM_KEY` contient la clé d'API Firebase Cloud Messaging.

4. Lancer le script `dev.sh`:

    ```bash
    bash dev.sh
    ```

## Production

```yml
version: '3'

services:
  denisbank-api:
    image: ghcr.io/mathieu2301/denisbank-api:latest
    restart: always
    environment:
      - MYSQL_HOST=${MYSQL_HOST}
      - MYSQL_USER=${MYSQL_USER}
      - MYSQL_PASS=${MYSQL_PASS}
      - MYSQL_DB=${MYSQL_DB}
      - FCM_KEY=${FCM_KEY}
    labels:
      - 'traefik.enable=true'
      - 'traefik.http.routers.denisbank-api.rule=Host(`${SERVER_URL}`)'
      - 'traefik.http.routers.denisbank-api.entrypoints=https'
    networks:
      - default
      - mariadb

networks:
  default:
    name: public
    external: true
  mariadb:
    external: true
```
