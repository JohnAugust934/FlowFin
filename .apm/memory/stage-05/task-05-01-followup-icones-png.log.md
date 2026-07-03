---
stage: 5
task: 1
title: "Follow-up 5.1 — Ícones PNG do PWA (iPhone/maskable)"
agent: frontend-agent
status: Completed
important_findings: true
compatibility_issues: false
---

# Follow-up Task 5.1 - Ícones PNG do PWA

## Summary
O ícone do app agora é PNG nos tamanhos certos, então a tela inicial fica correta em Android, desktop e **iPhone** (Safari não usa SVG como `apple-touch-icon`). Build OK, 145 testes verdes. Mesma branch/worktree `feature/pwa-offline` (commit `85256a0`).

## Details
- **Geração dos PNGs sem dependência externa:** o `npm install -D sharp` foi **bloqueado pelo classificador de permissões** (pacote externo com scripts de postinstall) e o ambiente segue sem rasterizador (magick/resvg/inkscape/puppeteer ausentes). Em vez de instalar lib, **redesenhei o ícone diretamente via GDI+ (System.Drawing)**, já presente no Windows: fundo em gradiente diagonal `#2563EB→#1E3A8A` + os 3 traços brancos da marca (mesmas coordenadas do SVG: transform `translate(106,106) scale(2.5)`, stroke 15 local, pontas/junções arredondadas, antialiasing). Resultado conferido visualmente — fiel à marca.
- **Arquivos gerados** (`public/img/pwa/`): `app-icon-192.png`, `app-icon-512.png`, `app-icon-maskable-512.png` (full-bleed, marca na zona segura central) e `app-icon-180.png` (apple-touch-icon do iOS).
- **Manifest** (`public/manifest.webmanifest`): `icons[]` agora lista os PNGs 192 e 512 (`purpose: any`) + 512 (`purpose: maskable`); o SVG permanece como entrada adicional (`sizes: any`).
- **Head do layout** (`resources/views/layouts/app.blade.php`): `apple-touch-icon` agora aponta para o PNG 180×180; adicionados `<link rel="icon">` PNG 192 e SVG. `apple-mobile-web-app-capable`/`-title` e `theme-color` já existiam (lote anterior).
- **Service Worker** (`public/sw.js`): PNGs 192/512 adicionados ao `PRECACHE_URLS` e `CACHE_VERSION` subiu `v1→v2` para invalidar o cache antigo e os clientes pegarem o manifest/ícones novos.

## Output
- Criado: `public/img/pwa/app-icon-{180,192,512}.png`, `public/img/pwa/app-icon-maskable-512.png`.
- Modificado: `public/manifest.webmanifest`, `public/sw.js`, `resources/views/layouts/app.blade.php`.
- Commit: `85256a0` (branch `feature/pwa-offline`).

## Validation
- `npm run build`: OK (app.js 86,70 KB / 27,63 KB gzip). `php artisan test`: **145 passed** (459 assertions).
- Validação guiada (celular): instalar o app e confirmar que o ícone na tela inicial é o da marca FlowFin (fundo azul) no Android e, principalmente, no **iPhone**.

## Important Findings
- Os PNGs foram **desenhados programaticamente** (GDI+), não rasterizados a partir do SVG por uma lib — visualmente equivalentes. Se no futuro a marca mudar, regenerar exige reexecutar o script GDI+ (ou, aí sim, um rasterizador). Não foi adicionada nenhuma dependência ao projeto.
- PNGs ficam em `public/img/pwa/` (servidos estáticos pelo Laravel; fora do bundle Vite — o build não os toca).
