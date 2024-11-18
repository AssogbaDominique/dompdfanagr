<?php
require_once 'dompdf/autoload.inc.php';
require_once 'jpgraph/src/jpgraph.php';
require_once 'jpgraph/src/jpgraph_bar.php';
require_once 'jpgraph/src/jpgraph_pie.php';
require_once 'jpgraph/src/jpgraph_pie3d.php';
require_once 'jpgraph/src/jpgraph_line.php';
use Dompdf\Dompdf;

$grapList = ["bar", "pie", "line"]; 

// Récupération des données de la base de données et formatage en tableau HTML
function getTableData($query, $columns) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=ventes_db', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->query($query);

        $table = "<table border='1' cellspacing='0' cellpadding='5' style='width: 100%; border-collapse: collapse; text-align: center;'>
                    <thead>
                        <tr>";

        // Ajouter les en-têtes du tableau
        foreach ($columns as $column) {
            $table .= "<th>{$column}</th>";
        }

        $table .= "</tr></thead><tbody>";

        // Ajouter les lignes du tableau avec les données
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $table .= "<tr>";
            foreach ($columns as $column) {
                $table .= "<td>{$row[$column]}</td>";
            }
            $table .= "</tr>";
        }

        $table .= "</tbody></table>";
        return $table;
    } catch (PDOException $e) {
        echo "Erreur de connexion : " . $e->getMessage();
        return '';
    }
}

// Récupération des données de la base de données
function getDataFromDatabase($query) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=ventes_db', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->query($query);
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = $row['valeur'];
        }
        return $data;
    } catch (PDOException $e) {
        echo "Erreur de connexion : " . $e->getMessage();
        return [];
    }
}

// Génération du nom de fichier avec timestamp
function generateFileName($type) {
    $timestamp = (new DateTime())->format('Ymd_His');
    return "{$type}_graph_{$timestamp}.png";
}

// Génération du graphique en barres
function generateBarGraph() {
    $datay = getDataFromDatabase('SELECT valeur FROM ventes');
    $graph = new Graph(400, 300);
    $graph->SetScale("textlin");
    $barplot = new BarPlot($datay);
    $graph->Add($barplot);
    $barplot->SetColor("blue");
    $barplot->SetFillColor("lightblue");
    $graph->title->Set("Ventes Mensuelles");
    $graph->xaxis->SetTickLabels(['Jan', 'Feb', 'Mar', 'Apr', 'May']);
    
    $imageFilePath = $_SERVER['DOCUMENT_ROOT'] . '/dompdfanagr/temp/' . generateFileName('bar');
    $graph->Stroke($imageFilePath);
    return $imageFilePath;
}

// Génération du graphique circulaire
function generatePieGraph() {
    $data = getDataFromDatabase('SELECT valeur FROM repartition');
    $graph = new PieGraph(400, 300);
    $graph->title->Set("Répartition des Ventes");
    $p1 = new PiePlot3D($data);
    $p1->SetSliceColors(['#FF6347', '#4682B4', '#FFD700', '#32CD32', '#EE82EE']);
    $graph->Add($p1);
    
    $imageFilePath = $_SERVER['DOCUMENT_ROOT'] . '/dompdfanagr/temp/' . generateFileName('pie');
    $graph->Stroke($imageFilePath);
    return $imageFilePath;
}

// Génération du graphique en courbes
function generateLineGraph() {
    $datay = getDataFromDatabase('SELECT valeur FROM evolution');
    $graph = new Graph(400, 300);
    $graph->SetScale("textlin");
    $lineplot = new LinePlot($datay);
    $graph->Add($lineplot);
    $lineplot->SetColor("red");
    $lineplot->SetWeight(2);
    $graph->title->Set("Évolution Mensuelle des Profits");
    $graph->xaxis->SetTickLabels(['Jan', 'Feb', 'Mar', 'Apr', 'May']);
    
    $imageFilePath = $_SERVER['DOCUMENT_ROOT'] . '/dompdfanagr/temp/' . generateFileName('line');
    $graph->Stroke($imageFilePath);
    return $imageFilePath;
}

// Génération du PDF pour un graphique spécifique
function generateGraphPDF($graphType) {
    $dompdf = new Dompdf();
    $imagePath = '';
    $title = '';

    switch ($graphType) {
        case 'bar':
            $imagePath = generateBarGraph();
            $title = 'Analyse des Ventes Mensuelles';
            break;
        case 'pie':
            $imagePath = generatePieGraph();
            $title = 'Répartition des Ventes';
            break;
        case 'line':
            $imagePath = generateLineGraph();
            $title = 'Évolution Mensuelle des Profits';
            break;
        default:
            break;
    }

    $imageData = base64_encode(file_get_contents($imagePath));

    // Récupérer les données du tableau
    $tableData = getTableData('SELECT mois, valeur FROM ventes', ['mois', 'valeur']); 

    $html = "
    <html>
    <head>
        <style>
            body { font-family: DejaVu Sans, sans-serif; }
            .content { text-align: center; }
            img { width: 100%; max-width: 600px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            table, th, td { border: 1px solid black; }
            th, td { padding: 10px; text-align: center; }
            th { background-color: #f2f2f2; }
            .page-break { page-break-before: always; }
        </style>
    </head>
    <body>
        <div class='content '>
            <h1>Tableau des Données</h1>
             $tableData 
        </div>
        <div class='content page-break'>
            <h1>{$title}</h1>
            <img src='data:image/png;base64,{$imageData}' alt='{$title}'>
        </div>
    </body>
    </html>";
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $fileName = "{$graphType}_" . (new DateTime())->format('y_m_d_H_i_s') . ".pdf";
    $dompdf->stream($fileName, ["Attachment" => true]);
}

// Fonction pour générer le PDF avec le tableau et les graphiques
function generateAllGraphsPDF($graphType) {
    $dompdf = new Dompdf();

    // Récupérer les données du tableau
    $tableData = getTableData('SELECT mois, valeur FROM ventes', ['mois', 'valeur']); 

    // Récupérer les images des graphiques
    $barImageData = base64_encode(file_get_contents(generateBarGraph()));
    $pieImageData = base64_encode(file_get_contents(generatePieGraph()));
    $lineImageData = base64_encode(file_get_contents(generateLineGraph()));

    $html = '
    <html>
    <head>
        <style>
            body { font-family: DejaVu Sans, sans-serif; }
            .content { text-align: center; }
            img { width: 100%; max-width: 600px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            table, th, td { border: 1px solid black; }
            th, td { padding: 10px; text-align: center; }
            th { background-color: #f2f2f2; }
            .page-break { page-break-before: always; }
        </style>
    </head>
    <body>
        <div class="content">
            <h1>Tableau des Données</h1>
            ' . $tableData . '
        </div>
        <div class="content page-break">
            <h1>Analyse des Ventes Mensuelles</h1>
            <img src="data:image/png;base64,' . $barImageData . '" alt="Graphique des Ventes Mensuelles">
        </div>

        <div class="content page-break">
            <h1>Répartition des Ventes</h1>
            <img src="data:image/png;base64,' . $pieImageData . '" alt="Graphique Circulaire des Ventes">
        </div>

        <div class="content page-break">
            <h1>Évolution Mensuelle des Profits</h1>
            <img src="data:image/png;base64,' . $lineImageData . '" alt="Graphique en Courbes des Profits">
        </div>
    </body>
    </html>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $fileName = "report_" . (new DateTime())->format('y_m_d_H_i_s') . ".pdf";
    $dompdf->stream($fileName, ["Attachment" => true]);
}

// Exécution de la génération du PDF selon le type de graphique sélectionné
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $graphType = htmlentities($_POST["graph"]);

    if ($graphType === 'all') {
        generateAllGraphsPDF($graphType);
    } else {
        generateGraphPDF($graphType);
    }
}
?>

<!-- Formulaire HTML pour générer le PDF -->
<html>
<head>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" crossorigin="anonymous">
</head>
<body>
<div class="container">
    <h1 class="mt-5">Analyse des Ventes</h1>
    <p>Cliquez sur le bouton ci-dessous pour générer le rapport PDF avec les graphiques des ventes.</p>
    <form method="post" action="" class="d-block justify-content-center items-center">
        <div class="form-group">
            <label for="graph">Graphique à télécharger</label>
            <select class="form-control" name="graph">
                <?php foreach ($grapList as $gl): ?>
                    <option value="<?= $gl ?>"><?= ucfirst($gl) ?></option>
                <?php endforeach; ?>
                <option value="all">Tout</option>
            </select>
        </div>
        <input type="submit" value="Générer le PDF" class="btn btn-primary mt-3">
    </form>
</div>
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" crossorigin="anonymous"></script>
</body>
</html>
