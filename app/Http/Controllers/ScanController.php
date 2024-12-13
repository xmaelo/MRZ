<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ScanController extends Controller
{
    public function index()
    {
        $filePath = session('filePath');  // Récupère le chemin du fichier importé de la session
        Log::info('Chemin du fichier récupéré depuis la session', ['filePath' => $filePath]);
    
        // Ne pas effacer la session ici pour que le chemin soit toujours accessible
        // session()->forget('filePath');  // Enlève cette ligne
        
        // Passer le chemin du fichier à la vue
        return view('scan', compact('filePath'));
    }
    

    public function scan(Request $request)
    {
        Log::info('Méthode scan appelée.');
        $outputFile = 'C:\\path\\to\\storage\\scans\\document.jpeg';

        $command = '"C:\\Program Files\\NAPS2\\NAPS2.Console.exe" scan --output ' . escapeshellarg($outputFile) . ' --force';
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            return response()->json(['error' => 'Le scan a échoué.'], 500);
        }

        // Prétraitement de l'image
        $preprocessedImage = $this->processScannedImage($outputFile);
        if ($preprocessedImage === null) {
            return response()->json(['error' => 'Le prétraitement de l\'image a échoué.'], 500);
        }

        $imageUrl = asset('storage/scans/document_processed.jpeg');
        return response()->json(['success' => 'Scan et prétraitement réussis.', 'imagePath' => $imageUrl]);
    }

    public function import(Request $request)
    {
        Log::info('Début de l\'importation de fichier', ['user_id' => auth()->id()]);

        // Validation du fichier
        $request->validate(['file' => 'required|file|mimes:png,jpg,jpeg,pdf']);
        Log::info('Validation réussie', ['file' => $request->file('file')->getClientOriginalName()]);

        // Sauvegarde du fichier dans 'storage/uploads'
        $path = $request->file('file')->store('uploads', 'public');
        Log::info('Fichier importé avec succès', ['path' => $path]);

        // Vérification de l'URL du fichier généré
        $filePath  = asset('storage/' . $path);
        Log::info('URL générée pour l\'image', ['filePath ' => $filePath]);

        session(['filePath' => $filePath]);
        Log::info('Chemin du fichier enregistré dans la session', ['filePath' => $filePath ]);
        
        return response()->json(['success' => 'Fichier importé avec succès.', 'filePath' => $filePath ]);
    }

    public function extractMRZscan(Request $request)
    {
        Log::info('Extraction MRZ lancée.');

        $imagePath = public_path('storage/scans/document_processed_final_processed_final_processed.jpeg');
        
        if (!file_exists($imagePath)) {
            return response()->json(['error' => 'Image manquante.'], 404);
        }

        $outputPath = public_path('storage/scans/document_mrz_output');
        $command = "tesseract \"$imagePath\" \"$outputPath\" --psm 3 -c tessedit_char_whitelist=\"ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789<>\"";

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            return response()->json(['error' => 'Erreur lors de l\'extraction MRZ.'], 500);
        }

        $mrzText = file_get_contents($outputPath . '.txt');
        $mrzData = $this->extractInfoFromMRZscan($mrzText);

        if ($mrzData) {
            return response()->json(['success' => 'MRZ extraite avec succès.', 'mrzData' => $mrzData]);
        } else {
            return response()->json(['error' => 'Aucune MRZ détectée.'], 404);
        }
    }

    private function extractInfoFromMRZscan($mrz)
    {
        // Log pour début de l'extraction des informations de la MRZ
        Log::info('Début de l\'analyse des informations de la MRZ.');

        $lines = explode("\n", $mrz);

        if (count($lines) < 2) {
            Log::error('Le format de la MRZ est invalide : moins de 2 lignes.');
            return null;
        }

        // Première ligne : traitement de la nationalité et du nom
        $line1 = $lines[0];
        $nationality = substr($line1, 2, 3);
        $namePart = substr($line1, 5);
        $nameParts = explode("<<", $namePart);

        $lastName = str_replace("<", " ", trim($nameParts[0]));
        $firstName = isset($nameParts[1]) ? trim($nameParts[1]) : '';

        // Deuxième ligne : traitement des autres informations
        $line2 = $lines[1];
        $passportNumber = rtrim(substr($line2, 0, 9), '<');
        $birthDate = substr($line2, 13, 6);
        $sex = substr($line2, 20, 1);
        $expirationDate = substr($line2, 21, 6);
        $cin = substr($line2, 28, 8);
        $cin = str_replace('<', '', $cin);

        return [
            'nationality' => $nationality,
            'last_name' => $lastName,
            'first_name' => $firstName,
            'passport_number' => $passportNumber,
            'birth_date' => $birthDate,
            'sex' => $sex,
            'cin' => $cin,
            'expiration_date' => $expirationDate,
        ];
    }


    public function processScannedImage($imagePath)
    {
        Log::info('Début du prétraitement de l\'image : ' . $imagePath);
    
        // Définir le chemin pour l'image prétraitée
        $preprocessedImage = public_path('storage/scans/') . pathinfo($imagePath, PATHINFO_FILENAME) . '_processed.jpeg';
    
        // Utiliser ImageMagick pour détecter et supprimer les bords blancs (trim automatique)
     //   $commandTrim = "magick \"$imagePath\" -trim +repage \"$preprocessedImage\"";
 
     $commandTrim = "magick \"$imagePath\" -fuzz 50% -trim +repage \"$preprocessedImage\"";
 
        exec($commandTrim, $output, $returnVar);
        Log::info('Commande de découpe (trim) exécutée : ' . $commandTrim);
        Log::info('Sortie de la commande : ' . implode("\n", $output));
        Log::info('Code de retour : ' . $returnVar);
    
        if ($returnVar !== 0) {
            Log::error('Erreur lors du trim de l\'image : ' . implode("\n", $output));
            return null;
        }
    
        // Vérifier que l'image a bien été découpée
        $trimmedImagePath = public_path('storage/scans/') . pathinfo($preprocessedImage, PATHINFO_FILENAME) . '_final_processed.jpeg';
        if (!file_exists($preprocessedImage)) {
            Log::error('L\'image après découpe (trim) n\'a pas été générée.');
            return null;
        }
    
        // Appliquer un crop pour ne garder que les 5% du bas de l'image
        // Lire les dimensions de l'image
        $imageDimensions = getimagesize($preprocessedImage);
        $imageWidth = $imageDimensions[0];  // Largeur de l'image
        $imageHeight = $imageDimensions[1]; // Hauteur de l'image
    
        // Calculer la hauteur des 5% du bas de l'image
        $cropHeight = (int)($imageHeight * 0.13);  // 5% de la hauteur
    
        // Créer la commande de crop pour garder uniquement les 5% du bas
        // Il faut éviter de passer des valeurs non échappées dans la commande
        $commandCrop = "magick \"$preprocessedImage\" -crop {$imageWidth}x{$cropHeight}+0+" . ($imageHeight - $cropHeight) . " +repage \"$trimmedImagePath\"";
    
        exec($commandCrop, $output, $returnVar);
        Log::info('Commande de crop exécutée : ' . $commandCrop);
        Log::info('Sortie de la commande : ' . implode("\n", $output));
        Log::info('Code de retour : ' . $returnVar);
    
        if ($returnVar !== 0) {
            Log::error('Erreur lors du crop de l\'image : ' . implode("\n", $output));
            return null;
        }
    
        // Appliquer des transformations supplémentaires pour améliorer l'image
        $preprocessedImagePath = public_path('storage/scans/') . pathinfo($trimmedImagePath, PATHINFO_FILENAME) . '_final_processed.jpeg';
        $commandPreprocess = "magick \"$trimmedImagePath\" -deskew 80%  -resize 300% -colorspace Gray -contrast-stretch 5x95% -sharpen 0x2 -noise 5 -threshold 70% \"$preprocessedImagePath\"";
    
        exec($commandPreprocess, $output, $returnVar);
        Log::info('Commande de prétraitement exécutée : ' . $commandPreprocess);
        Log::info('Sortie de la commande : ' . implode("\n", $output));
        Log::info('Code de retour : ' . $returnVar);
    
        if ($returnVar !== 0) {
            Log::error('Erreur lors du prétraitement de l\'image : ' . implode("\n", $output));
            return null;
        }
    
        Log::info('Image prétraitée sauvegardée à : ' . $preprocessedImagePath);
        return $preprocessedImagePath;
    
    }












}
