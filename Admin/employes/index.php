<?php
require_once '../../config.db.php';  // Assurez-vous que ce fichier contient la configuration correcte pour se connecter à votre base de données

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Loguer les données reçues pour débogage
        error_log('Données reçues : ' . print_r($_POST, true));
        error_log('Fichiers reçus : ' . print_r($_FILES, true));

        // Récupération des données envoyées via le formulaire POST
        $nom = $_POST['nom'] ?? null;
        $prenom = $_POST['prenom'] ?? null;
        $dateNaissance = $_POST['date_naissance'] ?? null;
        $age = isset($_POST['age']) ? (int)$_POST['age'] : null;
        $email = $_POST['email'] ?? null;
        $situationMatrimoniale = $_POST['situation_matrimoniale'] ?? null;
        $telephone = $_POST['telephone'] ?? null;
        $numeroCompte = $_POST['numero_compte'] ?? null;
        $personneAPrevenir = $_POST['personne_a_prevenir'] ?? null;
        $telephonePersonneAPrevenir = $_POST['telephone_personne_a_prevenir'] ?? null;
        $nationalite = $_POST['nationalite'] ?? null;
        $numeroMatricule = $_POST['numero_matricule'] ?? null;
        $posteOccupe = $_POST['poste_occupe'] ?? null;
        $numeroCarteIdentite = $_POST['numero_carte_identite'] ?? null;
        $motDePasse = isset($_POST['mot_de_passe']) ? password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT) : null;

        // Gestion du fichier photo
        $photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $photo = file_get_contents($_FILES['photo']['tmp_name']); // Lire le contenu du fichier photo
        }

        // Vérification des champs obligatoires
        $errors = [];

        if (!$nom) $errors[] = 'Nom manquant';
        if (!$prenom) $errors[] = 'Prénom manquant';
        if (!$dateNaissance) $errors[] = 'Date de naissance manquante';
        if ($age === null) $errors[] = 'Âge manquant';
        if (!$email) $errors[] = 'Email manquant';
        if (!$situationMatrimoniale) $errors[] = 'Situation matrimoniale manquante';
        if (!$telephone) $errors[] = 'Téléphone manquant';
        if (!$numeroCompte) $errors[] = 'Numéro de compte manquant';
        if (!$personneAPrevenir) $errors[] = 'Personne à prévenir manquante';
        if (!$telephonePersonneAPrevenir) $errors[] = 'Téléphone de la personne à prévenir manquant';
        if (!$nationalite) $errors[] = 'Nationalité manquante';
        if (!$numeroMatricule) $errors[] = 'Numéro de matricule manquant';
        if (!$posteOccupe) $errors[] = 'Poste occupé manquant';
        if (!$numeroCarteIdentite) $errors[] = 'Numéro de carte d\'identité manquant';
        if (!$motDePasse) $errors[] = 'Mot de passe manquant';

        if (!empty($errors)) {
            echo json_encode(['message' => 'Données manquantes ou incorrectes', 'errors' => $errors]);
            exit;
        }

        try {
            // Insérer les données dans la base de données
            $stmt = $pdo->prepare("INSERT INTO employes (nom, prenom, date_naissance, age, email, situation_matrimoniale, telephone, numero_compte, personne_a_prevenir, telephone_personne_a_prevenir, nationalite, numero_matricule, poste_occupe, numero_carte_identite, photo, mot_de_passe) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$nom, $prenom, $dateNaissance, $age, $email, $situationMatrimoniale, $telephone, $numeroCompte, $personneAPrevenir, $telephonePersonneAPrevenir, $nationalite, $numeroMatricule, $posteOccupe, $numeroCarteIdentite, $photo, $motDePasse])) {
                echo json_encode(['message' => 'Employé ajouté avec succès']);
            } else {
                $errorInfo = $stmt->errorInfo();
                echo json_encode(['message' => 'Erreur lors de l\'ajout de l\'employé', 'error' => $errorInfo]);
            }
        } catch (PDOException $e) {
            echo json_encode(['message' => 'Erreur lors de l\'ajout de l\'employé', 'error' => $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Mise à jour d'un employé existant
        parse_str(file_get_contents('php://input'), $data);
        $id = $data['id'] ?? null;
        $nom = $data['nom'] ?? null;
        $prenom = $data['prenom'] ?? null;
        $dateNaissance = $data['date_naissance'] ?? null;
        $age = isset($data['age']) ? (int)$data['age'] : null;
        $email = $data['email'] ?? null;
        $situationMatrimoniale = $data['situation_matrimoniale'] ?? null;
        $telephone = $data['telephone'] ?? null;
        $numeroCompte = $data['numero_compte'] ?? null;
        $personneAPrevenir = $data['personne_a_prevenir'] ?? null;
        $telephonePersonneAPrevenir = $data['telephone_personne_a_prevenir'] ?? null;
        $nationalite = $data['nationalite'] ?? null;
        $numeroMatricule = $data['numero_matricule'] ?? null;
        $posteOccupe = $data['poste_occupe'] ?? null;
        $numeroCarteIdentite = $data['numero_carte_identite'] ?? null;
        $motDePasse = isset($data['mot_de_passe']) ? password_hash($data['mot_de_passe'], PASSWORD_BCRYPT) : null;

        // Gestion du fichier photo pour mise à jour
        $photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $photo = file_get_contents($_FILES['photo']['tmp_name']); // Lire le contenu du fichier photo
        }

        // Vérification des champs obligatoires pour la mise à jour
        $errors = [];
        if (!$id) $errors[] = 'ID manquant';
        if (!$nom) $errors[] = 'Nom manquant';
        if (!$prenom) $errors[] = 'Prénom manquant';
        if (!$dateNaissance) $errors[] = 'Date de naissance manquante';
        if ($age === null) $errors[] = 'Âge manquant';
        if (!$email) $errors[] = 'Email manquant';
        if (!$situationMatrimoniale) $errors[] = 'Situation matrimoniale manquante';
        if (!$telephone) $errors[] = 'Téléphone manquant';
        if (!$numeroCompte) $errors[] = 'Numéro de compte manquant';
        if (!$personneAPrevenir) $errors[] = 'Personne à prévenir manquante';
        if (!$telephonePersonneAPrevenir) $errors[] = 'Téléphone de la personne à prévenir manquant';
        if (!$nationalite) $errors[] = 'Nationalité manquante';
        if (!$numeroMatricule) $errors[] = 'Numéro de matricule manquant';
        if (!$posteOccupe) $errors[] = 'Poste occupé manquant';
        if (!$numeroCarteIdentite) $errors[] = 'Numéro de carte d\'identité manquant';

        if (!empty($errors)) {
            echo json_encode(['message' => 'Données manquantes ou incorrectes', 'errors' => $errors]);
            exit;
        }

        try {
            // Mise à jour des données dans la base de données
            $stmt = $pdo->prepare("UPDATE employes SET nom = ?, prenom = ?, date_naissance = ?, age = ?, email = ?, situation_matrimoniale = ?, telephone = ?, numero_compte = ?, personne_a_prevenir = ?, telephone_personne_a_prevenir = ?, nationalite = ?, numero_matricule = ?, poste_occupe = ?, numero_carte_identite = ?, photo = ?, mot_de_passe = ? WHERE id = ?");
            if ($stmt->execute([$nom, $prenom, $dateNaissance, $age, $email, $situationMatrimoniale, $telephone, $numeroCompte, $personneAPrevenir, $telephonePersonneAPrevenir, $nationalite, $numeroMatricule, $posteOccupe, $numeroCarteIdentite, $photo, $motDePasse, $id])) {
                echo json_encode(['message' => 'Employé mis à jour avec succès']);
            } else {
                $errorInfo = $stmt->errorInfo();
                echo json_encode(['message' => 'Erreur lors de la mise à jour de l\'employé', 'error' => $errorInfo]);
            }
        } catch (PDOException $e) {
            echo json_encode(['message' => 'Erreur lors de la mise à jour de l\'employé', 'error' => $e->getMessage()]);
        }
        break;

    // Autres méthodes comme DELETE ou GET si nécessaire
}
?>
