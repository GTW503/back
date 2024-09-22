switch ($method) {
    case 'GET':
        // Récupérer les données de produits pour un graphique (par jour, semaine, mois, année)
        try {
            $period = $_GET['period'] ?? 'day';

            switch ($period) {
                case 'day':
                    $interval = 'DAY';
                    $dateFormat = '%Y-%m-%d';
                    break;
                case 'week':
                    $interval = 'WEEK';
                    $dateFormat = '%Y-%u'; // %u is the ISO-8601 week number
                    break;
                case 'month':
                    $interval = 'MONTH';
                    $dateFormat = '%Y-%m';
                    break;
                case 'year':
                    $interval = 'YEAR';
                    $dateFormat = '%Y';
                    break;
                default:
                    $interval = 'DAY';
                    $dateFormat = '%Y-%m-%d';
                    break;
            }