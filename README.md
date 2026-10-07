# roblox_tracker

Issue tracker voor Bonkbox Studios: bugs en features per project, met drie ingangen die dezelfde businesslogica delen.

- **Webinterface** (Livewire 4 + Flux): projecten, kanbanbord, lijst, itempagina, inbox, projectinstellingen en API-tokens.
- **REST API** onder `/api/v1`, met Sanctum-tokens. De OpenAPI-documentatie staat op `/docs/api`.
- **MCP-server** op `/mcp` voor AI-agents, met hetzelfde token als de API.
- **Inbox** voor de voice-agent: `POST /api/v1/inbox` met vrije tekst. Claude maakt daar een item van, of een concept als het niet zeker genoeg is.

Alle logica staat in `app/Actions`; web, API, MCP en de inbox-job roepen die Actions aan.

## Lokaal draaien (DDEV)

```bash
ddev start
ddev composer install
ddev npm install && ddev npm run build
ddev artisan migrate --seed
ddev launch
```

De seeder maakt het account `test@example.com` (wachtwoord `password`) en het project Bonkbox Studios (`BONKBOX`). DDEV start ook een queue-worker voor de inbox en webhooks.

Zet voor de inbox `ANTHROPIC_API_KEY` in `.env`. Met `INBOX_MODEL` en `INBOX_CONFIDENCE_THRESHOLD` stel je het model en de drempel in; de prompt staat in `config/inbox.php`.

## Tests en kwaliteit

```bash
ddev composer test          # Pest op SQLite
ddev composer test:pgsql    # dezelfde tests op Postgres
ddev composer analyse       # Larastan niveau 6
ddev exec vendor/bin/pint   # codestijl
```

## MCP koppelen

Maak een token aan onder **API-tokens**. De pagina toont het commando voor Claude Code en de configuratie voor `.mcp.json` en Claude Desktop, met het token erin.
