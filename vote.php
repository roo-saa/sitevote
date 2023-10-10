<?php
session_start(); // Démarrer la session

$serveur = "localhost"; // Adresse du serveur de base de données
$username = "root"; // Nom d'utilisateur de la base de données
$password = ""; // Mot de passe de la base de données
$dbname = "linkedin"; // Nom de la base de données

// Définir une variable pour stocker les messages d'erreur
$erreur = '';

try {
    // Création de la connexion à la base de données
    $connexion = new PDO("mysql:host=$serveur;dbname=$dbname", $username, $password);
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_POST['vote'])) {
        $idCandidat = $_POST['id'];
        $idUtilisateur = $_SESSION['utilisateur_id']; // Assurez-vous d'avoir l'ID de l'utilisateur
        
        // Vérification si l'utilisateur a déjà voté dans les 24 dernières heures
        $requeteVerifVote = $connexion->prepare("SELECT MAX(date_vote) AS derniere_vote FROM vote_date WHERE utilisateur_id = ?");
        $requeteVerifVote->execute([$idUtilisateur]);
        $resultatVerifVote = $requeteVerifVote->fetch();
        
        if ($resultatVerifVote) {
            $derniereVote = strtotime($resultatVerifVote['derniere_vote']);
            $maintenant = time();
            $delai = 24 * 3600; // 24 heures en secondes
            
            if (($maintenant - $derniereVote) <= $delai) {
                // L'utilisateur a déjà voté dans les 24 dernières heures, afficher un message d'erreur
                $erreur = 'Vous avez déjà voté pour un candidat dans les 24 dernières heures. Vous ne pouvez pas voter pour un autre candidat pour le moment.';
            }
        }
        
        // Si aucune erreur n'a été détectée jusqu'à présent, insérer le vote dans la table "vote_date"
        if (empty($erreur)) {
            $requeteInsererVote = $connexion->prepare("INSERT INTO vote_date (ip_users, candidat_id, utilisateur_id, date_vote) VALUES (?, ?, ?, NOW())");
            if ($requeteInsererVote->execute([$_SERVER['REMOTE_ADDR'], $idCandidat, $idUtilisateur])) {

                // Enregistrement du vote réussi, vous pouvez également mettre à jour les points du candidat ici si nécessaire
                $requeteIncrementPoints = $connexion->prepare("UPDATE candidat SET point = point + 1 WHERE id = ?");
                if ($requeteIncrementPoints->execute([$idCandidat])) {

                    // Le vote s'est bien passé
                } else {
                    $erreur = 'Une erreur est survenue lors de l\'enregistrement de votre vote.';
                }
            } else {
                $erreur = 'Une erreur est survenue lors de l\'enregistrement de votre vote.';
            }
        }
    }
} catch (PDOException $e) {
    // Gérer l'exception (erreur de base de données) et stocker le message d'erreur dans la variable
    $erreur = 'Une erreur de base de données s\'est produite : ' . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@10">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
</head>

<body>
<?php
if (!empty($erreur)) {
    // Afficher le message d'erreur s'il y en a un
    echo '<script>
        Swal.fire({
            title: "Erreur!",
            text: "' . $erreur . '",
            icon: "error",
            confirmButtonText: "OK"
        }).then(() => {
            // Rediriger l\'utilisateur vers une page de confirmation ou de remerciement
            window.location.href = "location.php";
        });
    </script>';
} else {
    // Afficher le message de succès
    echo '<script>
        Swal.fire({
            title: "Succès!",
            text: "Votre vote a été enregistré avec succès!",
            icon: "success",
            confirmButtonText: "OK"
        }).then(() => {
            // Rediriger l\'utilisateur vers une page de confirmation ou de remerciement
            window.location.href = "location.php";
        });
    </script>';
}

?>
</body>
</html>
