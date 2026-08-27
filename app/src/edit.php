<?php
/**
 * UPDATE — Formulário de edição de um produto, pré-carregado com os dados atuais.
 */
require __DIR__ . '/db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    exit('Produto inválido.');
}

// Busca o registro que será editado.
$stmt = $pdo->prepare('SELECT id, nome, descricao, preco FROM produtos WHERE id = :id');
$stmt->execute([':id' => $id]);
$produto = $stmt->fetch();

if (!$produto) {
    http_response_code(404);
    exit('Produto não encontrado.');
}

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produto['nome']      = trim($_POST['nome'] ?? '');
    $produto['descricao'] = trim($_POST['descricao'] ?? '');
    $produto['preco']     = trim($_POST['preco'] ?? '');

    if ($produto['nome'] === '') {
        $erros[] = 'O campo "Nome" é obrigatório.';
    }
    if ($produto['descricao'] === '') {
        $erros[] = 'O campo "Descrição" é obrigatório.';
    }
    $precoNormalizado = str_replace(',', '.', (string) $produto['preco']);
    if ($produto['preco'] === '' || !is_numeric($precoNormalizado) || (float) $precoNormalizado < 0) {
        $erros[] = 'O campo "Preço" deve ser um número maior ou igual a zero.';
    }

    if (count($erros) === 0) {
        $stmt = $pdo->prepare(
            'UPDATE produtos SET nome = :nome, descricao = :descricao, preco = :preco WHERE id = :id'
        );
        $stmt->execute([
            ':nome'      => $produto['nome'],
            ':descricao' => $produto['descricao'],
            ':preco'     => (float) $precoNormalizado,
            ':id'        => $id,
        ]);

        header('Location: /index.php?ok=1');
        exit;
    }
}

$tituloPagina = 'Editar Produto';
require __DIR__ . '/includes/header.php';
?>

<h1>Editar produto #<?= e((string) $id) ?></h1>

<?php if ($erros): ?>
    <ul class="alerta erro">
        <?php foreach ($erros as $erro): ?>
            <li><?= e($erro) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" action="/edit.php" class="formulario">
    <input type="hidden" name="id" value="<?= e((string) $id) ?>">

    <label>
        Nome
        <input type="text" name="nome" maxlength="150" value="<?= e($produto['nome']) ?>" required>
    </label>

    <label>
        Descrição
        <textarea name="descricao" rows="4" required><?= e($produto['descricao']) ?></textarea>
    </label>

    <label>
        Preço (R$)
        <input type="text" name="preco" inputmode="decimal" placeholder="0,00" value="<?= e(str_replace('.', ',', (string) $produto['preco'])) ?>" required>
    </label>

    <div class="acoes-formulario">
        <button type="submit" class="botao">Atualizar</button>
        <a class="botao secundario" href="/index.php">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
