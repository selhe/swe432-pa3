<?php
/**
 * One question and its computed answer.
 * Properties are private and read through getters (encapsulation).
 */
class Result
{
    private string $question;
    private string $answer;

    public function __construct(string $question, string $answer)
    {
        $this->question = $question;
        $this->answer = $answer;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }
}
