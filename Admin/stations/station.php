<?php
require_once '../../config.db.php';


header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Récupérer toutes les stations
        try {
            $stmt = $pdo->prepare("SELECT * FROM stations");
            $stmt->execute();
            $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($stations);
        } catch (Exception $e) {
            echo json_encode(['message' => 'Erreur lors de la récupération des stations', 'error' => $e->getMessage()]);
        }
        break;

    case 'POST':
        // Ajouter une nouvelle station
        try {
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
                $logo = $_FILES['logo'];
                $uploadDir = '../../uploads/';
                $uploadFile = $uploadDir . basename($logo['name']);
                if (move_uploaded_file($logo['tmp_name'], $uploadFile)) {
                    $logoPath = basename($logo['name']);
                } else {
                    echo json_encode(['message' => 'Erreur lors du téléchargement du logo']);
                    exit;
                }
            } else {
                $logoPath = null;
            }

            $designation = $_POST['designation'] ?? '';
            $activites = $_POST['activites'] ?? '';
            $identifiantFiscal = $_POST['identifiantFiscal'] ?? '';
            $numeroCompte = $_POST['numeroCompte'] ?? '';
            $commune = $_POST['commune'] ?? '';
            $adresseMail = $_POST['adresseMail'] ?? '';
            $registreCommerce = $_POST['registreCommerce'] ?? '';

            if ($designation && $activites && $identifiantFiscal && $numeroCompte && $commune && $adresseMail && $registreCommerce) {
                $stmt = $pdo->prepare("INSERT INTO stations (designation, activites, identifiant_fiscal, numero_compte, commune, adresse_mail, registre_commerce, logo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$designation, $activites, $identifiantFiscal, $numeroCompte, $commune, $adresseMail, $registreCommerce, $logoPath])) {
                    echo json_encode(['message' => 'Station créée avec succès']);
                } else {
                    echo json_encode(['message' => 'Erreur lors de la création de la station']);
                }
            } else {
                echo json_encode(['message' => 'Veuillez remplir tous les champs']);
            }
        } catch (Exception $e) {
            echo json_encode(['message' => 'Erreur lors de la création de la station', 'error' => $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Mettre à jour une station existante
        try {
            parse_str(file_get_contents("php://input"), $data);

            $id = $data['id'] ?? '';
            $designation = $data['designation'] ?? '';
            $activites = $data['activites'] ?? '';
            $identifiantFiscal = $data['identifiant_fiscal'] ?? '';
            $numeroCompte = $data['numero_compte'] ?? '';
            $commune = $data['commune'] ?? '';
            $adresseMail = $data['adresse_mail'] ?? '';
            $registreCommerce = $data['registre_commerce'] ?? '';
            $logoPath = $data['logo'] ?? null; // Assuming logo is handled elsewhere in update

            if ($id && $designation && $activites && $identifiantFiscal && $numeroCompte && $commune && $adresseMail && $registreCommerce) {
                $stmt = $pdo->prepare("UPDATE stations SET designation = ?, activites = ?, identifiant_fiscal = ?, numero_compte = ?, commune = ?, adresse_mail = ?, registre_commerce = ?, logo = ? WHERE id = ?");
                if ($stmt->execute([$designation, $activites, $identifiantFiscal, $numeroCompte, $commune, $adresseMail, $registreCommerce, $logoPath, $id])) {
                    echo json_encode(['message' => 'Station mise à jour avec succès']);
                } else {
                    echo json_encode(['message' => 'Erreur lors de la mise à jour de la station']);
                }
            } else {
                echo json_encode(['message' => 'Veuillez remplir tous les champs']);
            }
        } catch (Exception $e) {
            echo json_encode(['message' => 'Erreur lors de la mise à jour de la station', 'error' => $e->getMessage()]);
        }
        break;

    case 'DELETE':
        // Supprimer une station
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? '';

            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM stations WHERE id = ?");
                if ($stmt->execute([$id])) {
                    echo json_encode(['message' => 'Station supprimée avec succès']);
                } else {
                    echo json_encode(['message' => 'Erreur lors de la suppression de la station']);
                }
            } else {
                echo json_encode(['message' => 'ID de la station manquant']);
            }
        } catch (Exception $e) {
            echo json_encode(['message' => 'Erreur lors de la suppression de la station', 'error' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Méthode non autorisée']);
        break;
}
?>
