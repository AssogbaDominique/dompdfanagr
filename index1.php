<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;

// Créer une instance de Dompdf
$dompdf = new Dompdf();

// Charger le contenu HTML
$html = '
<html>
<head>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .content { text-align: center; }
        img { width: 100%; max-width: 600px; }
    </style>
</head>
<body>
    <div class="content">
        <h1>Mon document PDF</h1>
        <p>Ceci est un exemple de document PDF généré avec Dompdf.</p>
        <img src="./images/bar_chart.jpg" alt="Graphique des ventes" width="500">;
    </div>
</body>
</html>';

$dompdf->loadHtml($html);

// (Optionnel) Configurer la taille et l'orientation de la page
$dompdf->setPaper('A4', 'portrait');

// Rendre le PDF
$dompdf->render();

// Envoyer le PDF au navigateur
$dompdf->stream("document.pdf", ["Attachment" => false]);
?>