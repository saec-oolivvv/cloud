<?php

declare(strict_types=1);

namespace Saec\Services\Storage;

/**
 * Storage Adapter Interface
 * 
 * Chaque provider cloud/remote implémente cette interface.
 * Pattern: Octopus Arm — chaque adapter est un bras autonome.
 */
interface StorageAdapter
{
    /**
     * Tester la connexion au provider
     * @return array{success: bool, message: string, latency_ms: int}
     */
    public function testConnection(): array;

    /**
     * Informations sur le provider
     * @return array{type: string, name: string, region: string|null, endpoint: string|null, version: string|null}
     */
    public function getInfo(): array;

    /**
     * Lister les fichiers/dossiers dans un chemin
     * @param string $path Chemin relatif (vide = racine)
     * @return array<int, array{name: string, path: string, type: string, size: int, modified: int, mime: string|null}>
     */
    public function list(string $path = ''): array;

    /**
     * Lire le contenu d'un fichier
     * @param string $path Chemin du fichier
     * @return string Contenu du fichier
     * @throws \RuntimeException Si le fichier n'existe pas
     */
    public function read(string $path): string;

    /**
     * Écrire un fichier
     * @param string $path Chemin du fichier
     * @param string $content Contenu à écrire
     * @param array $meta Métadonnées optionnelles
     * @return bool Succès
     */
    public function write(string $path, string $content, array $meta = []): bool;

    /**
     * Supprimer un fichier ou dossier vide
     * @param string $path Chemin
     * @return bool Succès
     */
    public function delete(string $path): bool;

    /**
     * Créer un dossier
     * @param string $path Chemin du dossier
     * @return bool Succès
     */
    public function mkdir(string $path): bool;

    /**
     * Renommer/déplacer un fichier ou dossier
     * @param string $oldPath Ancien chemin
     * @param string $newPath Nouveau chemin
     * @return bool Succès
     */
    public function rename(string $oldPath, string $newPath): bool;

    /**
     * Copier un fichier
     * @param string $source Chemin source
     * @param string $destination Chemin destination
     * @return bool Succès
     */
    public function copy(string $source, string $destination): bool;

    /**
     * Vérifier si un chemin existe
     * @param string $path Chemin
     * @return bool
     */
    public function exists(string $path): bool;

    /**
     * Taille d'un fichier ou total d'un dossier
     * @param string $path Chemin
     * @return int Taille en octets
     */
    public function size(string $path): int;

    /**
     * Dernière modification
     * @param string $path Chemin
     * @return int Timestamp Unix
     */
    public function lastModified(string $path): int;

    /**
     * Type MIME d'un fichier
     * @param string $path Chemin
     * @return string|null Type MIME
     */
    public function mimeType(string $path): ?string;

    /**
     * Espace utilisé
     * @return int Octets
     */
    public function usedSpace(): int;

    /**
     * Espace libre
     * @return int Octets (-1 si inconnu)
     */
    public function freeSpace(): int;

    /**
     * Espace total
     * @return int Octets (-1 si inconnu)
     */
    public function totalSpace(): int;

    /**
     * Upload multiple fichiers
     * @param array<int, array{path: string, content: string, meta?: array}> $files
     * @return array{success: int, errors: array}
     */
    public function putContents(array $files): array;

    /**
     * Supprimer multiple fichiers
     * @param array<int, string> $paths
     * @return array{success: int, errors: array}
     */
    public function deleteContents(array $paths): array;

    /**
     * Générer une URL signée (presigned URL)
     * @param string $path Chemin
     * @param int $expiresInSecondes Durée de validité
     * @return string|null URL signée (null si non supporté)
     */
    public function signedUrl(string $path, int $expiresInSecondes = 3600): ?string;

    /**
     * Upload avec streaming (pour gros fichiers)
     * @param string $path Chemin destination
     * @param resource $stream Handle du flux
     * @param int $size Taille totale
     * @return bool Succès
     */
    public function writeStream(string $path, $stream, int $size): bool;

    /**
     * Télécharger en streaming
     * @param string $path Chemin
     * @return resource|null Handle du flux (null si erreur)
     */
    public function readStream(string $path);

    /**
     * Déterminer si le provider supporte une feature
     * @param string $feature Feature demandée
     * @return bool
     */
    public function supports(string $feature): bool;
}
