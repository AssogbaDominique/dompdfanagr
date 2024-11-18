<?php
require_once 'dompdf/autoload.inc.php';
require_once 'jpgraph/src/jpgraph.php';
require_once 'jpgraph/src/jpgraph_bar.php';
require_once 'jpgraph/src/jpgraph_pie.php';
require_once 'jpgraph/src/jpgraph_pie3d.php';
require_once 'jpgraph/src/jpgraph_line.php';

use Dompdf\Dompdf;

// Récupération des données de la base de données

        $pdo = new PDO('mysql:host=localhost;dbname=ventes_db', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
function generateFileName($graphType) {
    $timestamp = (new DateTime())->format('Ymd_His'); // Exemple : 20241015_123456
    $fileName = "{$graphType}_graph_{$timestamp}.png";
    return $fileName;
}

// Génération du graphique en barres
function generateBarGraph() {
    global $pdo;
    $datay = getDataFromDatabase('SELECT valeur FROM ventes');
    $graph = new Graph(400, 300);
    $graph->SetScale("textlin");
    $barplot = new BarPlot($datay);
    $graph->Add($barplot);
    $barplot->SetColor("blue");
    $barplot->SetFillColor("lightblue");
    $graph->title->Set("Ventes Mensuelles");
    $graph->xaxis->SetTickLabels(['Jan', 'Feb', 'Mar', 'Apr', 'May']);
    
    //$imageFilePath = 'C:\xampp\htdocs\dompdfanagr\temp' . '/' . generateFileName('bar');
    $imageFilePath = $_SERVER['DOCUMENT_ROOT'] . '/dompdfanagr/temp/'. generateFileName('bar');
    $graph->Stroke($imageFilePath); // Sauvegarde le graphique

    //$ventes = json_encode($datay);
    //return var_dump($ventes) ;
    $query = $pdo->prepare("INSERT INTO historiques(type,valeur,ref)VALUES('ventes','$imageFilePath','1234')"); 
    $query->execute();

    return $imageFilePath;

}

// Génération du graphique circulaire
function generatePieGraph() {
    global $pdo;
    $data = getDataFromDatabase('SELECT valeur FROM repartition');
    $graph = new PieGraph(400, 300);
    $graph->title->Set("Répartition des Ventes");
    $p1 = new PiePlot3D($data);
    $p1->SetSliceColors(['#FF6347', '#4682B4', '#FFD700', '#32CD32', '#EE82EE']);
    $graph->Add($p1);
    
    //$imageFilePath = 'C:\xampp\htdocs\dompdfanagr\temp' . '/' . generateFileName('pie');
    $imageFilePath = $_SERVER['DOCUMENT_ROOT'] . '/dompdfanagr/temp/'. generateFileName('pie');
    $graph->Stroke($imageFilePath); // Sauvegarde le graphique

    //$repartition = json_encode($data);
    //return var_dump($ventes) ;
    $ref = rand(1111, 9999);
    $query = $pdo->prepare("INSERT INTO historiques(type,valeur,ref)VALUES('repartition','$imageFilePath','$ref')"); 
    $query->execute();
    return $imageFilePath;
}

// Génération du graphique en courbes
function generateLineGraph() {
    global $pdo;
    $datay = getDataFromDatabase('SELECT valeur FROM evolution');
    $graph = new Graph(400, 300);
    $graph->SetScale("textlin");
    $lineplot = new LinePlot($datay);
    $graph->Add($lineplot);
    $lineplot->SetColor("red");
    $lineplot->SetWeight(2);
    $graph->title->Set("Évolution Mensuelle des Profits");
    $graph->xaxis->SetTickLabels(['Jan', 'Feb', 'Mar', 'Apr', 'May']);
    
    //$imageFilePath = 'C:\xampp\htdocs\dompdfanagr\temp' . '/' . generateFileName('line');
    $imageFilePath = $_SERVER['DOCUMENT_ROOT'] . '/dompdfanagr/temp/'. generateFileName('line');
    $graph->Stroke($imageFilePath); // Sauvegarde le graphique
   
    //$evolution = json_encode($datay);
    //return var_dump($ventes) ;
    $ref = rand(1111, 9999);
    $query = $pdo->prepare("INSERT INTO historiques(type,valeur,ref)VALUES('evolution','$imageFilePath','$ref')"); 
    $query->execute();
    return $imageFilePath;
}

// Génération et affichage du PDF avec les graphiques
function generatePDF() {
    $barImagePath = generateBarGraph();
    $pieImagePath = generatePieGraph();
    $lineImagePath = generateLineGraph();

    $dompdf = new Dompdf();

    // Encode les images en Base64
    $barImageData = base64_encode(file_get_contents($barImagePath));
    $pieImageData = base64_encode(file_get_contents($pieImagePath));
    $lineImageData = base64_encode(file_get_contents($lineImagePath));
    
    $html = '
    <html>
    <head>
        <style>
            body { font-family: DejaVu Sans, sans-serif; }
            .content { text-align: center; }
            img { width: 100%; max-width: 600px; }
            .page-break { page-break-before: always; }
        </style>
    </head>
    <body>
        <div class="content">
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
    $dompdf->stream("analyse_ventes.pdf", ["Attachment" => false]);
}

// Exécute la génération du PDF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    generatePDF();
}
?>

<!-- Formulaire HTML pour générer le PDF -->
<html>
<head>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <h1 class="mt-5">Analyse des Ventes</h1>
    <p>Cliquez sur le bouton ci-dessous pour générer le rapport PDF avec les graphiques des ventes.</p>
    <form method="post" action="">
        <input type="submit" value="Générer le PDF" class="btn btn-primary mt-3">
    </form>
</div>
</body>
</html>
