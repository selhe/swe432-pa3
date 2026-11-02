<?php
/**
 * RENDERING: builds the HTML for results, errors and processing info.
 * Every value that came from the uploaded file goes through htmlspecialchars()
 * so it is shown as text and can never inject HTML or scripts (XSS).
 */
class Renderer
{
    private function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /** @param Result[] $results */
    public function results(array $results, string $fileName, int $total): string
    {
        $name = $this->e($fileName);
        $html = "<p>Loaded $total records from <strong>$name</strong>.</p>\n<ol>\n";
        foreach ($results as $result) {
            $question = $this->e($result->getQuestion());
            $answer = $this->e($result->getAnswer());
            $html .= "  <li><div class=\"question\">$question</div><div class=\"answer\">$answer</div></li>\n";
        }
        return $html . "</ol>\n";
    }

    public function error(string $message): string
    {
        return "<p class=\"error\">{$this->e($message)}</p>\n";
    }

    public function processingInfo(string $timestamp, float $seconds): string
    {
        $ms = number_format($seconds * 1000, 1);
        return "<p>Processed at <strong>$timestamp</strong> in <strong>$ms ms</strong>.</p>\n";
    }
}
