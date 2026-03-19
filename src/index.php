<?php

$host = 'devsecops-bdd';
$db   = 'myapp';
$user = 'appuser';
$pass = 'apppassword';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log("Erreur de connexion BDD : " . $e->getMessage());
    die("Erreur de connexion. Veuillez contacter l'administrateur.");
}

$search = $_GET['search'] ?? '';
$ip     = $_GET['ip'] ?? '';

$validatedIp = filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Annuaire Interne</title>
    <style>body { font-family: sans-serif; padding: 20px; }</style>
</head>
<body>
    <h1>Annuaire de l'entreprise</h1>

    <p>Résultats de recherche pour : <b><?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?></b></p>

    <form method="GET">
        <input type="text" name="search"
               placeholder="Rechercher un collègue..."
               value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit">Rechercher</button>
    </form>
    <hr>

    <?php if ($search) : ?>
        <?php
        $sql  = "SELECT username, role FROM users WHERE username = :username";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':username', $search, PDO::PARAM_STR);

        try {
            $stmt->execute();
            $results = $stmt->fetchAll();

            if ($results) {
                echo "<ul>";
                foreach ($results as $row) {
                    $safeUsername = htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8');
                    echo "<li><strong>$safeUsername</strong></li>";
                }
                echo "</ul>";
            } else {
                echo "Aucun utilisateur trouvé.";
            }
        } catch (PDOException $e) {
            error_log("Erreur SQL : " . $e->getMessage()); // Log serveur uniquement
            echo "Une erreur est survenue lors de la recherche.";
        }
        ?>
    <?php endif; ?>

    <hr>
    <div style="background-color:#f8d7da;padding:10px;border:1px solid #f5c6cb;">
        <h3>Zone Admin : Diagnostic Réseau</h3>
        <p>Vérifier la connectivité d'un serveur interne.</p>

        <form method="GET">
            <input type="hidden" name="search"
                   value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            <label>IP à tester :</label>
            <input type="text" name="ip"
                   placeholder="ex: 8.8.8.8"
                   value="<?= htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Pinger</button>
        </form>

        <?php if ($ip !== '') : ?>
            <?php if ($validatedIp !== null) : ?>
                <pre>
                    Test de ping sur : <?= htmlspecialchars($validatedIp, ENT_QUOTES, 'UTF-8') ?>

                    --------------------------
                    <?php
                        $safeIp = escapeshellarg($validatedIp);
                        system("ping -c 2 " . $safeIp);
                    ?>
                </pre>
            <?php else: ?>
                <p style="color:red;">
                    ⚠ Adresse IP invalide. Seules les adresses IPv4 et IPv6 valides sont acceptées.
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>