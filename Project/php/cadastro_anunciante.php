<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $host = "localhost:3306";
    $usuario = "root";
    $senha_db = "";
    $database = "estudomais";

    $conn = new mysqli($host, $usuario, $senha_db, $database);

    if ($conn->connect_error) {
        die("Erro de conexão: " . $conn->connect_error);
    }

    $nome = trim($_POST['nome_empresa']);
    $email = trim($_POST['email_empresa']);
    $documento = preg_replace('/\D/', '', $_POST['documento']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $data_criacao = date('Y-m-d H:i:s');

    $erros = [];

    if (empty($nome)) $erros[] = "Nome da empresa é obrigatório.";
    if (empty($email)) $erros[] = "E-mail é obrigatório.";
    if (empty($documento)) $erros[] = "CPF ou CNPJ é obrigatório.";
    if (empty($senha) || empty($confirmar_senha)) $erros[] = "Senha e confirmação são obrigatórias.";

    if (strlen($nome) < 3 || strlen($nome) > 100) {
        $erros[] = "O nome da empresa deve ter entre 3 e 100 caracteres.";
    }

    if (strlen($email) > 150) {
        $erros[] = "O e-mail não pode ultrapassar 150 caracteres.";
    }

    if (strlen($senha) < 6 || strlen($senha) > 50) {
        $erros[] = "A senha deve ter entre 6 e 50 caracteres.";
    }

    if ($senha !== $confirmar_senha) {
        $erros[] = "As senhas não coincidem.";
    }

    if (strlen($documento) == 11 && !validarCPF($documento)) {
        $erros[] = "CPF inválido.";
    } elseif (strlen($documento) == 14 && !validarCNPJ($documento)) {
        $erros[] = "CNPJ inválido.";
    } elseif (strlen($documento) !== 11 && strlen($documento) !== 14) {
        $erros[] = "CPF ou CNPJ deve ter 11 ou 14 dígitos.";
    }

    $stmt = $conn->prepare("SELECT id_anunciante FROM anunciante WHERE email_empresa = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $erros[] = "Este e-mail já está em uso.";
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT id_anunciante FROM anunciante WHERE documento = ?");
    $stmt->bind_param("s", $documento);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $erros[] = "Este CPF ou CNPJ já está em uso.";
    }
    $stmt->close();

    if (!empty($erros)) {
        foreach ($erros as $erro) {
            echo "<p style='color: red;'>$erro</p>";
        }
        exit;
    }

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    $sql = "INSERT INTO anunciante (nome_empresa, email_empresa, documento, senha, data_de_criacao_anunciante)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $nome, $email, $documento, $senha_hash, $data_criacao);

    if ($stmt->execute()) {
        header("Location: /Projeto-Planner/Project/html/login_anunciante.html");
        exit;
    } else {
        echo "Erro ao cadastrar: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Método inválido.";
}

function validarCPF($cpf) {
    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    return true;
}

function validarCNPJ($cnpj) {
    if (strlen($cnpj) != 14 || preg_match('/(\d)\1{13}/', $cnpj)) return false;
    for ($t = 12; $t < 14; $t++) {
        for ($d = 0, $p = 2, $c = $t - 7; $c >= 0; $c--) {
            $d += $cnpj[$c] * $p++;
            if ($p > 9) $p = 2;
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cnpj[$c] != $d) return false;
    }
    return true;
}
?>
