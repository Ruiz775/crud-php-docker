# CRUD de Produtos — PHP + PostgreSQL + Docker Compose

Aplicação web simples que implementa um **CRUD** (Create, Read, Update, Delete)
para a entidade **Produto**, totalmente containerizada com **Docker Compose**.

A aplicação é escrita em **PHP puro** (sem frameworks) e usa **PDO** para conversar
com um banco **PostgreSQL**. Todo o ambiente sobe com um único comando
(`docker compose up -d`), sem precisar instalar PHP ou banco de dados na máquina.

### O que a aplicação faz

| Operação | Página | Descrição |
|----------|--------|-----------|
| **Read**   | `index.php`  | Lista todos os produtos cadastrados em uma tabela. |
| **Create** | `create.php` | Formulário de cadastro (envio via `POST`). |
| **Update** | `edit.php`   | Formulário de edição, pré-carregado com os dados do registro. |
| **Delete** | `delete.php` | Exclusão de um produto (link na listagem com confirmação via JavaScript). |

### A entidade `produtos`

| Campo           | Tipo                | Observação                                   |
|-----------------|---------------------|----------------------------------------------|
| `id`            | `SERIAL` (PK)       | Chave primária, auto incremento.             |
| `nome`          | `VARCHAR(150)`      | Nome do produto.                             |
| `descricao`     | `TEXT`              | Descrição livre do produto.                  |
| `preco`         | `NUMERIC(10,2)`     | Preço em reais.                              |
| `data_cadastro` | `TIMESTAMP`         | Preenchido automaticamente (`CURRENT_TIMESTAMP`). |

São **4 campos além do `id`**, atendendo ao mínimo de 3 exigido pelo enunciado.

---

## 1. Pré-requisitos

Você só precisa ter instalado:

- **Docker** (Engine 20.10+)
- **Docker Compose** (v2 — já incluído no Docker Desktop; no Linux, o plugin `docker-compose-plugin`)

> Não é necessário ter PHP, Composer ou PostgreSQL instalados no computador.
> O projeto foi testado para rodar **apenas com Docker**.

Para conferir:

```bash
docker --version
docker compose version
```

---

## 2. Passo a passo para executar

### 2.1. Clonar o repositório

```bash
git clone https://github.com/Ruiz775/crud-php-docker.git
cd crud-php-docker
```

### 2.2. Subir os containers

```bash
docker compose up -d
```

Na primeira execução o Docker vai:

1. Construir a imagem do serviço `app` a partir de `app/Dockerfile`
   (imagem oficial `php:8.2-apache` + extensão `pdo_pgsql`);
2. Baixar a imagem oficial `postgres:16`;
3. Criar a rede `rede-crud` e o volume `dados-postgres`;
4. Iniciar os dois containers (`produtos-app` e `produtos-db`).

### 2.3. Como a tabela do banco é criada

A tabela **`produtos` é criada automaticamente pelo próprio código PHP**.

No arquivo [`app/src/db.php`](app/src/db.php), logo após abrir a conexão, é executado:

```sql
CREATE TABLE IF NOT EXISTS produtos (
    id             SERIAL PRIMARY KEY,
    nome           VARCHAR(150) NOT NULL,
    descricao      TEXT NOT NULL,
    preco          NUMERIC(10,2) NOT NULL DEFAULT 0,
    data_cadastro  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

Ou seja: **não é preciso rodar nenhum script SQL manualmente**. Basta abrir a
aplicação no navegador que, na primeira requisição, a tabela é verificada e
criada se ainda não existir. O banco em si (`crud_produtos`) é criado pela
imagem oficial do PostgreSQL por meio da variável `POSTGRES_DB`.

> **Espera pelo banco:** na primeira subida, o PostgreSQL leva alguns segundos
> para aceitar conexões. Como o enunciado não permite `healthcheck` nem scripts
> de espera, o `db.php` faz até 15 tentativas de reconexão (com 2s de intervalo)
> antes de desistir. Se a primeira página demorar um pouco, é só isso acontecendo.

### 2.4. Acessar a aplicação

Abra no navegador:

**<http://localhost:8080>**

### 2.5. Parar / limpar

```bash
docker compose down            # para e remove os containers (o volume é mantido)
docker compose down -v         # idem, mas também apaga o volume (zera o banco)
```

---

## 3. Explicação detalhada do `docker-compose.yml`

O arquivo [`docker-compose.yml`](docker-compose.yml) está **comentado linha a linha**.
Abaixo, a explicação em texto de cada parte.

### 3.1. Serviço `app` (aplicação PHP)

| Item | Valor | Função |
|------|-------|--------|
| `build.context` / `dockerfile` | `./app` / `Dockerfile` | Constrói a imagem a partir de `app/Dockerfile`. Partimos da imagem **oficial e pronta** `php:8.2-apache` e apenas adicionamos a extensão `pdo_pgsql`, porque a imagem oficial do PHP **não traz drivers de banco por padrão**. |
| `container_name` | `produtos-app` | Nome fixo do container, facilitando comandos como `docker logs produtos-app`. |
| `ports` | `8080:80` | Publica a porta **80** do Apache (dentro do container) na porta **8080** do host. Acesso: `http://localhost:8080`. |
| `environment` | `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` | Dados de conexão com o banco, lidos pelo PHP via `getenv()`. Definidos **direto no Compose**, sem arquivo `.env`. |
| `volumes` | `./app/src:/var/www/html` | Monta o código-fonte do host dentro do diretório servido pelo Apache. Editar um `.php` no host reflete imediatamente no container. |
| `depends_on` | `db` | Faz o Compose iniciar o `db` **antes** do `app` (apenas ordem de start; a espera pela prontidão do banco é feita no `db.php`). |
| `networks` | `rede-crud` | Conecta o container à rede personalizada do projeto. |
| `restart` | `unless-stopped` | Sobe o container de novo caso ele caia, a menos que tenha sido parado manualmente. |

### 3.2. Serviço `db` (PostgreSQL)

| Item | Valor | Função |
|------|-------|--------|
| `image` | `postgres:16` | Imagem **oficial** do PostgreSQL 16, do Docker Hub. |
| `container_name` | `produtos-db` | Nome fixo do container do banco. |
| `environment` | `POSTGRES_DB`, `POSTGRES_USER`, `POSTGRES_PASSWORD` | Variáveis que a **própria imagem oficial** usa no primeiro start para criar o banco, o usuário e a senha. |
| `volumes` | `dados-postgres:/var/lib/postgresql/data` | Guarda os arquivos de dados do Postgres em um **volume nomeado**, garantindo persistência mesmo após `docker compose down`. |
| `networks` | `rede-crud` | Conecta o banco à mesma rede do `app`. |
| `restart` | `unless-stopped` | Mesma política de reinício do `app`. |

### 3.3. Variáveis de ambiente utilizadas

| Variável (serviço `app`) | Valor | Para que serve |
|--------------------------|-------|----------------|
| `DB_HOST`     | `db`            | Nome do serviço/container do banco. A rede `bridge` personalizada resolve esse nome para o IP do container do Postgres. |
| `DB_PORT`     | `5432`          | Porta do PostgreSQL dentro da rede interna. |
| `DB_NAME`     | `crud_produtos` | Nome do banco de dados usado pela aplicação. |
| `DB_USER`     | `app_user`      | Usuário de conexão. |
| `DB_PASSWORD` | `app_password`  | Senha do usuário de conexão. |

As três variáveis `POSTGRES_*` do serviço `db` têm valores equivalentes
(`crud_produtos`, `app_user`, `app_password`) para que o usuário/banco criados
pela imagem oficial sejam exatamente os que a aplicação espera.

### 3.4. A rede criada

- **Nome:** `rede-crud`
- **Driver:** `bridge` (rede local padrão do Docker)
- **Função:** isola os containers deste projeto em uma rede própria e fornece
  **DNS interno**: dentro dessa rede, o container `app` alcança o banco apenas
  usando o nome `db` (valor de `DB_HOST`), sem precisar saber o IP.

### 3.5. O volume criado

- **Nome:** `dados-postgres`
- **Montado em:** `/var/lib/postgresql/data` (onde o PostgreSQL grava tudo)
- **Função:** manter os dados do banco mesmo que os containers sejam removidos
  (`docker compose down`). Os dados só são apagados com `docker compose down -v`.

---

## 4. Pontos interessantes observados pela dupla

1. **Variáveis de ambiente direto no `docker-compose.yml`.**
   Toda a configuração de conexão fica na seção `environment` do Compose. Dá para
   mudar host, usuário, senha ou nome do banco **sem tocar em uma linha de código
   PHP** — o `db.php` só lê `getenv()`.

2. **Persistência com volume nomeado.**
   Sem o volume, todo `docker compose down` apagaria o banco. Com o volume
   `dados-postgres` montado em `/var/lib/postgresql/data`, os produtos cadastrados
   continuam lá depois de reiniciar (ou até recriar) os containers.

3. **Rede `bridge` personalizada = comunicação por nome.**
   Ao criar a rede `rede-crud`, o Docker passa a resolver `db` como nome de host
   dentro dela. Isso deixa a comunicação entre `app` e `db` simples e isolada do
   resto da máquina, sem expor a porta do banco para o host.

4. **A imagem oficial do PHP não traz driver de banco.**
   `php:8.2-apache` vem "pelada": foi preciso estender a imagem no `Dockerfile`
   com `docker-php-ext-install pdo_pgsql` (e o pacote `libpq-dev`) para o PDO
   enxergar o PostgreSQL. Bom aprendizado sobre o que cada imagem oficial já inclui.

5. **Criação da tabela pelo próprio PHP (`CREATE TABLE IF NOT EXISTS`).**
   Evita depender de script SQL manual ou de mecanismos de init automático
   (proibidos no enunciado): a aplicação se autoconfigura na primeira requisição.

---

## 5. Estrutura do projeto

```
.
├── docker-compose.yml        # Orquestração dos serviços (comentado linha a linha)
├── README.md                 # Este arquivo
├── .gitignore
└── app/
    ├── Dockerfile            # php:8.2-apache + extensão pdo_pgsql
    └── src/                  # Código servido pelo Apache (/var/www/html)
        ├── db.php            # Conexão PDO + retry + CREATE TABLE IF NOT EXISTS
        ├── index.php         # READ  — listagem
        ├── create.php        # CREATE — formulário de cadastro (POST)
        ├── edit.php          # UPDATE — formulário de edição (POST)
        ├── delete.php        # DELETE — exclusão
        ├── includes/
        │   ├── header.php
        │   └── footer.php
        └── assets/
            └── style.css
```

---

## 6. Autores

| Nome             | RA     |
|------------------|--------|
| Gustavo Ruiz     | 250417 |
