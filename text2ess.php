<?php
require_once 'dompdf/autoload.inc.php';
require_once 'jpgraph/src/jpgraph.php';
require_once 'jpgraph/src/jpgraph_bar.php';
require_once 'jpgraph/src/jpgraph_pie.php';
require_once 'jpgraph/src/jpgraph_pie3d.php';
require_once 'jpgraph/src/jpgraph_line.php';

use Dompdf\Dompdf;

$grapList = ["bar", "pie","line"]; 


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
    $timestamp = (new DateTime())->format('Ymd_His'); // Exemple : 20241015_123456
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

// Génération et sauvegarde automatique du PDF
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
    
    // Sauvegarde le PDF dans un dossier "history"
    $pdfDir = $_SERVER['DOCUMENT_ROOT'] . '/dompdfanagr/history/';
    if (!is_dir($pdfDir)) {
        mkdir($pdfDir, 0777, true); // Crée le dossier s'il n'existe pas

    }

    $file_name = "analyse".(new DateTime())->format("y_m_d_H_i_s").".pdf"; 

    //$pdfFilePath = $pdfDir . 'analyse_ventes_' . (new DateTime())->format('Ymd_His') . '.pdf';
    //file_put_contents($pdfFilePath, $dompdf->output());
    
    // Affiche le PDF dans le navigateur
    $dompdf->stream($file_name, ["Attachment" => true]);
}


// generate bar graph Reporting PDF ; 

function generateBarGraphPDF() {

    $barImagePath = generateBarGraph();
    $dompdf = new Dompdf();

    // Encode les images en Base64
    $barImageData = base64_encode(file_get_contents($barImagePath));
    
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
    </body>
    </html>';
    
    $dompdf->loadHTML($html); 
    $dompdf->setPaper('A4', "portrait");
    $dompdf->render(); 
     


    $file_name = "bar".(new DateTime())->format("y_m_d_H_i_s").".pdf"; 


    $dompdf->stream($file_name, ["Attachment"=> true]); 
}

// Exécute la génération du PDF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $graphType = htmlentities($_POST["graph"]); 

    switch ($graphType) {
        case 'bar':
            generateBarGraphPDF();
            break;
       
        default:
            # code...
            break;
    }
    //generatePDF();

}
?>

<!-- Formulaire HTML pour générer le PDF -->
<html>
<head>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>
<body>
<div class="container">
    <h1 class="mt-5">Analyse des Ventes</h1>
    <p>Cliquez sur le bouton ci-dessous pour générer le rapport PDF avec les graphiques des ventes.</p>
    <form method="post" action="" class="d-block justify-content-center items-center ">

        <div class="">
            <div class="form-group ">
              <label for=""> Graph to download</label>
              <select class="form-control" name="graph" >
                <?php foreach ($grapList as $gl):?>
                <option value=<?=$gl ?>> <?= $gl ?> </option>
                <?php endforeach; ?>
                <option value="all">Tout </option>
              </select>
            </div>
        </div>
        <input type="submit" value="Générer le PDF" class="btn btn-primary mt-3">
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</body>
</html>


