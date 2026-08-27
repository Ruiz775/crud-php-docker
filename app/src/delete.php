<?php
/**
 * DELETE — Exclusão de um produto.
 *
 * O acesso vem de um link na listagem que pede confirmação via JavaScript
 * (confirm) antes de chamar esta página.
 */
require __DIR__ . '/db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    exit('Produto inválido.');
}

$stmt = $pdo->prepare('DELETE FROM produtos WHERE id = :id');
$stmt->execute([':id' => $id]);

header('Location: /index.php?ok=1');
exit;
