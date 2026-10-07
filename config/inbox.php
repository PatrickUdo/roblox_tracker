<?php

return [

    /*
    | Messages from the voice agent are interpreted by Claude. Items are created
    | automatically when the reported confidence is at or above the threshold;
    | below it they wait as drafts in the project inbox.
    */

    'threshold' => (float) env('INBOX_CONFIDENCE_THRESHOLD', 0.8),

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('INBOX_MODEL', 'claude-opus-5-5'),
        // Short extraction task: low effort is enough and keeps latency and cost down.
        'effort' => env('INBOX_EFFORT', 'low'),
    ],

    'prompt' => <<<'PROMPT'
        Je zet gesproken meldingen van monteurs om naar een issue voor een issue tracker.
        De tekst is een automatische transcriptie en kan spreektaal, herhalingen of fouten bevatten.

        Bepaal:
        - type: "bug" als iets kapot is of anders werkt dan verwacht, "feature" als het een wens of verbetering is.
        - title: korte, concrete titel in het Nederlands (maximaal 80 tekens), zonder het type te herhalen.
        - description: nette beschrijving in markdown. Gebruik voor bugs de kopjes "Stappen", "Verwacht" en
          "Werkelijk" waar de melding daar informatie over geeft; verzin niets wat niet in de melding staat.
        - priority: "critical" als werk stilligt of er een veiligheidsrisico is, "high" als het werk ernstig
          hindert, "medium" als normaal, "low" voor kleine ongemakken of wensen.
        - confidence: getal van 0 tot 1 dat aangeeft hoe zeker je bent dat dit een bruikbaar, eenduidig issue is.
          Geef een lage waarde als de melding onduidelijk, onvolledig of geen issue is.
        PROMPT,

];
