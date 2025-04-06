<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FileUploader
{
    private SluggerInterface $slugger;
    private ParameterBagInterface $parameterBag;

    public function __construct(SluggerInterface $slugger, ParameterBagInterface $parameterBag)
    {
        $this->slugger = $slugger;
        $this->parameterBag = $parameterBag;
    }

    /**
     * Télécharge un fichier dans le répertoire spécifié
     * 
     * @param UploadedFile $file Le fichier à télécharger
     * @param string $directory Le paramètre du répertoire cible (par exemple 'posts_directory')
     * @param string $oldFilename Ancien fichier à supprimer s'il existe
     * @param string $prefix Préfixe pour le nom du fichier
     * @return string Le nom du fichier téléchargé
     * @throws \Exception
     */
    public function upload(UploadedFile $file, string $directory, ?string $oldFilename = null, string $prefix = ''): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $prefix . $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
        
        // Récupérer le chemin du répertoire depuis les paramètres
        $targetDirectory = $this->parameterBag->get($directory);

        try {
            // Supprimer l'ancien fichier s'il existe
            if ($oldFilename) {
                $this->remove($oldFilename, $targetDirectory);
            }
            
            $file->move($targetDirectory, $fileName);
        } catch (FileException $e) {
            throw new \Exception('Une erreur est survenue lors du téléchargement du fichier: ' . $e->getMessage());
        }

        return $fileName;
    }

    /**
     * Supprime un fichier du répertoire spécifié
     * 
     * @param string $filename Nom du fichier à supprimer
     * @param string $directory Chemin du répertoire
     * @return bool True si le fichier a été supprimé, false sinon
     */
    public function remove(string $filename, string $directory): bool
    {
        $filePath = $directory . '/' . $filename;
        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }
} 