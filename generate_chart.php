<?php
// Définir la taille de l'image
$width = 600;
$height = 400;

// Créer une image vide avec la taille définie
$image = imagecreatetruecolor($width, $height);

// Définir les couleurs pour le fond, les barres et le texte
$backgroundColor = imagecolorallocate($image, 255, 255, 255);
$barColor = imagecolorallocate($image, 0, 102, 204);
$textColor = imagecolorallocate($image, 0, 0, 0);

// Remplir l'arrière-plan avec la couleur blanche
imagefilledrectangle($image, 0, 0, $width, $height, $backgroundColor);

// Les données de l'analyse graphique en dur
$data = [100, 200, 150, 250, 180];
$labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May'];

// Définir la largeur des barres et l'espacement entre elles
$barWidth = 50;
$gap = 30;

// Initialiser la position de la première barre
$x = $gap;

// Boucle pour dessiner chaque barre
foreach ($data as $index => $value) {
    // Calculer la hauteur de la barre
    $barHeight = $value;
    
    // Dessiner la barre
    imagefilledrectangle($image, $x, $height - $barHeight, $x + $barWidth, $height, $barColor);
    
    // Ajouter le label sous chaque barre
    imagestring($image, 5, $x + 5, $height - 20, $labels[$index], $textColor);
    
    // Passer à la position de la prochaine barre
    $x += $barWidth + $gap;
}

// Enregistrer l'image sous forme de fichier JPEG
imagejpeg($image, 'bar_chart.jpg');

// Libérer la mémoire
imagedestroy($image);

// Afficher un message de confirmation
echo "Le graphique a été généré et enregistré sous le nom 'bar_chart.jpg'.";
?>
