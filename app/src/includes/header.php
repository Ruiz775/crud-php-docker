<?php
/** Cabeçalho comum a todas as páginas. */
$tituloPagina = $tituloPagina ?? 'CRUD de Produtos';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tituloPagina) ?></title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <header class="topo">
        <a class="marca" href="/index.php">📦 CRUD de Produtos</a>
        <nav>
            <a href="/index.php">Listagem</a>
            <a href="/create.php">Novo produto</a>
        </nav>
    </header>
    <main class="conteudo">
