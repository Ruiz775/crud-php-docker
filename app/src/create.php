<?php
/**
 * CREATE — Formulário de cadastro de um novo produto (envio via POST).
 */
require __DIR__ . '/db.php';

$erros = [];
$dados = ['nome' => '', 'descricao' => '', 'preco' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['nome']      = trim($_POST['nome'] ?? '');
    $dados['descricao'] = trim($_POST['descricao'] ?? '');
    $dados['preco']     = trim($_POST['preco'] ?? '');

    if ($dados['nome'] === '') {
        $erros[] = 'O campo "Nome" é obrigatório.';
    }
    if ($dados['descricao'] === '') {
        $erros[] = 'O campo "Descrição" é obrigatório.';
    }
    $precoNormalizado = str_replace(',', '.', $dados['preco']);
    if ($dados['preco'] === '' || !is_numeric($precoNormalizado) || (float) $precoNormalizado < 0) {
        $erros[] = 'O campo "Preço" deve ser um número maior ou igual a zero.';
    }

    if (count($erros) === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO produtos (nome, descricao, preco) VALUES (:nome, :descricao, :preco)'
        );
        $stmt->execute([
            ':nome'      => $dados['nome'],
            ':descricao' => $dados['descricao'],
            ':preco'     => (float) $precoNormalizado,
        ]);

        header('Location: /index.php?ok=1');
        exit;
    }
}

$tituloPagina = 'Novo Produto';
require __DIR__ . '/includes/header.php';
?>

<h1>Novo produto</h1>

<?php if ($erros): ?>
    <ul class="alerta erro">
        <?php foreach ($erros as $erro): ?>
            <li><?= e($erro) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" action="/create.php" class="formulario">
    <label>
        Nome
        <input type="text" name="nome" maxlength="150" value="<?= e($dados['nome']) ?>" required>
    </label>

    <label>
        Descrição
        <textarea name="descricao" rows="4" required><?= e($dados['descricao']) ?></textarea>
    </label>

    <label>
        Preço (R$)
        <input type="text" name="preco" inputmode="decimal" placeholder="0,00" value="<?= e($dados['preco']) ?>" required>
    </label>

    <div class="acoes-formulario">
        <button type="submit" class="botao">Salvar</button>
        <a class="botao secundario" href="/index.php">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
