<?php
/**
 * READ — Página de listagem de todos os produtos cadastrados.
 */
require __DIR__ . '/db.php';

$produtos = $pdo->query('SELECT id, nome, descricao, preco, data_cadastro FROM produtos ORDER BY id DESC')->fetchAll();

$tituloPagina = 'Listagem de Produtos';
require __DIR__ . '/includes/header.php';
?>

<div class="cabecalho-lista">
    <h1>Produtos cadastrados</h1>
    <a class="botao" href="/create.php">+ Novo produto</a>
</div>

<?php if (isset($_GET['ok'])): ?>
    <p class="alerta sucesso">Operação realizada com sucesso.</p>
<?php endif; ?>

<?php if (count($produtos) === 0): ?>
    <p class="vazio">Nenhum produto cadastrado ainda. Clique em <strong>“Novo produto”</strong> para começar.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Descrição</th>
                <th>Preço (R$)</th>
                <th>Data de cadastro</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($produtos as $p): ?>
            <tr>
                <td><?= e((string) $p['id']) ?></td>
                <td><?= e($p['nome']) ?></td>
                <td><?= e($p['descricao']) ?></td>
                <td><?= e(number_format((float) $p['preco'], 2, ',', '.')) ?></td>
                <td><?= e(date('d/m/Y H:i', strtotime($p['data_cadastro']))) ?></td>
                <td class="acoes">
                    <a class="botao pequeno" href="/edit.php?id=<?= e((string) $p['id']) ?>">Editar</a>
                    <a class="botao pequeno perigo"
                       href="/delete.php?id=<?= e((string) $p['id']) ?>"
                       onclick="return confirm('Excluir o produto &quot;<?= e($p['nome']) ?>&quot;?');">Excluir</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
