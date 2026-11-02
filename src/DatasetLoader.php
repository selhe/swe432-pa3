<?php
require_once __DIR__ . '/InvalidDatasetException.php';

/**
 * READING: turns an entry from $_FILES into an array of records.
 */
class DatasetLoader
{
    /**
     * @param array $file one entry from $_FILES, e.g. $_FILES['dataset']
     * @return array list of records (each record is an associative array)
     * @throws InvalidDatasetException
     */
    public function load(array $file): array
    {
        // PHP reports upload problems (no file, file too big, ...) through the 'error' field
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidDatasetException($this->uploadErrorMessage($file['error']));
        }

        $text = file_get_contents($file['tmp_name']);
        if ($text === false) {
            throw new InvalidDatasetException('the uploaded file could not be read');
        }

        return $this->parseRecords($text);
    }

    /**
     * Accepts a plain JSON array of records OR a GeoJSON FeatureCollection,
     * where each record's fields are inside feature["properties"].
     */
    private function parseRecords(string $text): array
    {
        // true = decode objects as associative arrays; JSON_THROW_ON_ERROR makes bad JSON throw
        try {
            $data = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidDatasetException("the file is not valid JSON ({$e->getMessage()})");
        }

        if (is_array($data) && array_is_list($data)) {
            $records = $data;
        } elseif (is_array($data) && isset($data['features']) && is_array($data['features'])) {
            $records = array_map(fn($feature) => $feature['properties'] ?? [], $data['features']);
        } else {
            throw new InvalidDatasetException('expected an array of records or a "features" array');
        }

        if (count($records) === 0) {
            throw new InvalidDatasetException('the file contains no records');
        }
        return $records;
    }

    private function uploadErrorMessage(int $code): string
    {
        switch ($code) {
            case UPLOAD_ERR_NO_FILE:
                return 'no file was selected';
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'the file is larger than the server allows';
            default:
                return "the upload failed (error code {$code})";
        }
    }
}
