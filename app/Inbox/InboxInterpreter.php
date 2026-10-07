<?php

namespace App\Inbox;

interface InboxInterpreter
{
    /**
     * @throws InterpretationFailed
     */
    public function interpret(string $text): InboxSuggestion;
}
