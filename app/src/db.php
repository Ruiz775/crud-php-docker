<?php
/**
 * Conexão com o banco de dados (PostgreSQL) usando PDO.
 *
 * As credenciais NÃO ficam no código: elas vêm das variáveis de ambiente
 * definidas na seção "environment" do docker-compose.yml.
 */

declare(strict_types=1);

$dbHost = getenv('DB_HOST') ?: 'db';
$dbPort = getenv('DB_PORT') ?: '5432';
$dbName = getenv('DB_NAME') ?: 'crud_produtos';
$dbUser = getenv('DB_USER') ?: 'app_user';
$dbPass = getenv('DB_PASSWORD') ?: 'app_password';

$dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbName}";

/*
 * Na PRIMEIRA vez que os containers sobem, o PostgreSQL leva alguns segundos
 * para aceitar conexões. Como o trabalho não permite healthcheck nem scripts
 * de espera/inicialização, fazemos algumas tentativas de reconexão aqui mesmo,
 * no código da aplicação.
 */
$maxTentativas = 15;
$pdo = null;

for ($tentativa = 1; $tentativa <= $maxTentativas; $tentativa++) {
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        break;
    } catch (PDOException $e) {
        if ($tentativa === $maxTentativas) {
            http_response_code(503);
            exit('Não foi possível conectar ao banco de dados: ' . $e->getMessage());
        }
        sleep(2);
    }
}

/*
 * Criação automática da tabela caso ela ainda não exista.
 * Dessa forma não é preciso rodar nenhum script SQL manualmente:
 * basta subir os containers e abrir a aplicação no navegador.
 */
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS produtos (
        id             SERIAL PRIMARY KEY,
        nome           VARCHAR(150) NOT NULL,
        descricao      TEXT NOT NULL,
        preco          NUMERIC(10,2) NOT NULL DEFAULT 0,
        data_cadastro  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )'
);

/**
 * Escape de saída para evitar injeção de HTML / XSS nas telas.
 */
function e(?string $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
