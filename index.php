<?php
require_once __DIR__ . '/src/DatasetLoader.php';
require_once __DIR__ . '/src/CrimeAnalyzer.php';
require_once __DIR__ . '/src/Renderer.php';

date_default_timezone_set('America/New_York');

$renderer = new Renderer();
$output = '';
$info = '';

// $_SERVER and $_FILES are superglobals: only process when the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start = microtime(true);
    $fileName = $_FILES['dataset']['name'] ?? 'unknown file';
    try {
        if (!isset($_FILES['dataset'])) {
            throw new InvalidDatasetException('no file was uploaded');
        }
        $records = (new DatasetLoader())->load($_FILES['dataset']);
        $analyzer = new CrimeAnalyzer($records);
        $output = $renderer->results($analyzer->analyze(), $fileName, $analyzer->getRecordCount());
    } catch (InvalidDatasetException $e) {
        $output = $renderer->error("\"$fileName\" is not a valid dataset: {$e->getMessage()}");
    } catch (Throwable $e) {
        // anything unexpected (e.g. a record missing a field) still shows a message instead of a blank page
        $output = $renderer->error("Something went wrong while processing \"$fileName\": {$e->getMessage()}");
    }
    $info = $renderer->processingInfo(date('Y-m-d H:i:s'), microtime(true) - $start);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HW3: Data Analysis with PHP</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1>Data Analysis</h1>
  <form method="post" enctype="multipart/form-data">
    <label for="dataset">Upload the JSON dataset:</label>
    <input type="file" id="dataset" name="dataset" accept=".json,.geojson">
    <button type="submit">Analyze</button>
  </form>

  <div id="output"><?= $output ?></div>
  <div id="info"><?= $info ?></div>
</body>
</html>
