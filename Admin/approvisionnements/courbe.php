<?php
require_once '../../config.db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Récupérer les données d'approvisionnement pour tracer une courbe
        try {
            // Supposons que vous voulez tracer la courbe en fonction des quantités par jour
            $stmt = $pdo->prepare("SELECT date, SUM(stock_arrive) as quantity FROM approvisionnements GROUP BY date ORDER BY date ASC");
            $stmt->execute();
            $approvisionnements = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Formater les données pour le graphique
            $chartData = [];
            foreach ($approvisionnements as $row) {
                $chartData[] = [
                    'date' => $row['date'],
                    'quantity' => (float)$row['quantity'],
                ];
            }

            // Retourner les données sous forme de JSON avec un message de confirmation
            echo json_encode([
                'message' => 'Données récupérées avec succès',
                'data' => $chartData
            ]);
        } catch (Exception $e) {
            echo json_encode(['message' => 'Erreur lors de la récupération des données', 'error' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Méthode non autorisée']);
        break;
}
?>
