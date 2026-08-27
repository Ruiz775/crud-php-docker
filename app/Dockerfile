# Dockerfile do serviço "app" (aplicação PHP)
#
# Partimos da imagem OFICIAL e pronta do PHP com o servidor Apache já embutido,
# disponível no Docker Hub. A tag 8.2-apache fixa a versão do PHP em 8.2.
FROM php:8.2-apache

# A imagem oficial do PHP NÃO vem com os drivers de banco de dados instalados.
# Para conversar com o PostgreSQL precisamos:
#   1. instalar a biblioteca de sistema "libpq-dev" (cliente do PostgreSQL);
#   2. compilar/instalar a extensão "pdo_pgsql", usada pelo nosso código PHP (via PDO).
# Ao final limpamos o cache do apt para deixar a imagem menor.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

# O código-fonte não é copiado aqui de propósito: ele é montado em tempo de execução
# pelo volume declarado no docker-compose.yml (./app/src -> /var/www/html).
# Assim, qualquer alteração nos arquivos PHP no host aparece na hora dentro do container.
