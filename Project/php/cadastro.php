<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = "localhost:3306";
$usuario = "root";
$senha = "";
$database = "estudomais";

$conexao = new mysqli($host, $usuario, $senha, $database);

if ($conexao->connect_error) {
    http_response_code(500);
    echo json_encode(["erro" => "Falha na conexão: " . $conexao->connect_error]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome_aluno = trim($_POST['nome_aluno']);
    $senha_aluno = $_POST['senha_aluno'];
    $email_aluno = trim($_POST['email']);
    $senha_confirmacao = trim($_POST['senha_confirmacao']);

    if (strlen($nome_aluno) > 50) {
        http_response_code(400);
        echo json_encode(["erro" => "O nome do aluno deve ter no máximo 50 caracteres."]);
        exit;
    }

    if (strlen($email_aluno) > 50) {
        http_response_code(400);
        echo json_encode(["erro" => "O email deve ter no máximo 50 caracteres."]);
        exit;
    }

    if (strlen($senha_aluno) > 255) {
        http_response_code(400);
        echo json_encode(["erro" => "A senha deve ter no máximo 255 caracteres."]);
        exit;
    }
    if ($senha_aluno !== $senha_confirmacao) {
        http_response_code(400);
        echo json_encode(["erro" => "As senhas não coincidem."]);
        exit;
    }
    

    // Prepara uma consulta para verificar se já existe aluno com o mesmo nome ou email
    $query = "SELECT nome_aluno, email FROM aluno WHERE nome_aluno = ? OR email = ?";
    $stmt = $conexao->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["erro" => "Erro ao preparar a consulta: " . $conexao->error]);
        exit;
    }

    // Associa os parâmetros à consulta
    $stmt->bind_param("ss", $nome_aluno, $email_aluno);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $linha = $resultado->fetch_assoc();
        http_response_code(409); // Código 409: conflito
        if ($linha['nome_aluno'] === $nome_aluno) {
            echo json_encode(["erro" => "O nome de usuário já está em uso."]);
        } elseif ($linha['email'] === $email_aluno) {
            echo json_encode(["erro" => "O email já está em uso."]);
        }
        exit;
    }

    $stmt->close();

    // Prepara a inserção dos dados na tabela aluno
    $stmt = $conexao->prepare("INSERT INTO aluno (nome_aluno, senha_aluno, email) VALUES (?, ?, ?)");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["erro" => "Erro ao preparar a inserção: " . $conexao->error]);
        exit;
    }

    $senha_hash = password_hash($senha_aluno, PASSWORD_DEFAULT);
    $stmt->bind_param("sss", $nome_aluno, $senha_hash, $email_aluno);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(["sucesso" => "Cadastro realizado com sucesso!"]);
    } else {
        http_response_code(500);
        echo json_encode(["erro" => "Erro ao realizar o cadastro: " . $stmt->error]);
    }

    $stmt->close();
}

$conexao->close();
?>
