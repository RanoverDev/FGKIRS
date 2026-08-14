# FGKIRS — Contexto do Projeto para IA

## Visão Geral

Sistema de gestão da **Federação Gaúcha de Karatê Interestilos (FGKIRS)**.  
Desenvolvido em PHP 8.3+ com arquitetura MVC pura, sem frameworks externos.  
URL de produção: `https://fgkirs.com.br`

---

## Stack

| Camada       | Tecnologia                                   |
|--------------|----------------------------------------------|
| Backend      | PHP 8.3+, MVC custom (sem Laravel/Symfony)   |
| Banco de dados | MySQL (host: `mysql.fgkirs.com.br`, db: `fgkirs01`) |
| Frontend     | Tailwind CSS v4 via CDN, Vanilla JS          |
| Autenticação | Sessões PHP nativas + BCRYPT                 |
| E-mail       | SMTP próprio via `Helpers\Mailer`            |
| Imagens      | GD Library — max 1200px, qualidade 55, JPG   |

---

## Estrutura de Diretórios

```
www/
├── app/
│   ├── Controllers/
│   │   ├── Admin/          # DashboardController, UserController, DojoController,
│   │   │                   # StyleController, GraduationController, PostController,
│   │   │                   # GalleryController, FederationProfileController, BoardController
│   │   ├── Auth/           # LoginController (login, logout, forgot/reset password)
│   │   ├── Controller.php  # Base controller (render helper)
│   │   └── HomeController.php  # Páginas públicas
│   ├── Core/
│   │   ├── Database.php    # PDO Singleton
│   │   └── Router.php      # Roteador com suporte a {params} dinâmicos
│   ├── Helpers/
│   │   ├── Auth.php        # Autenticação e autorização baseada em roles
│   │   ├── Csrf.php        # Proteção CSRF
│   │   ├── ImageProcessor.php  # Upload/processamento de imagens
│   │   ├── Mailer.php      # Envio SMTP
│   │   ├── RateLimiter.php # Rate limiting por IP
│   │   └── Slugify.php     # Geração de slugs
│   ├── Models/
│   │   ├── Dojo.php
│   │   ├── Event.php
│   │   ├── FederationProfile.php
│   │   ├── Gallery.php
│   │   ├── News.php
│   │   └── Post.php
│   └── Views/
│       ├── admin/          # Painel administrativo (layout: header, sidebar, footer)
│       ├── auth/           # Login, forgot/reset password
│       ├── partials/       # public_header, public_footer, about_block, pix_popup
│       ├── errors/         # 404.php
│       └── (páginas públicas: home, dojos, events, galleries, news, contact, about)
├── config/
│   └── config.php          # Constantes de ambiente (DB, SMTP, imagem)
├── database/
│   ├── schema.sql          # Schema fase 1 (users, dojos)
│   └── phase2_schema.sql   # Schema fase 2 (estilos, graduações, pagamentos, posts)
├── public/
│   ├── index.php           # Front controller — carrega config + despacha router
│   ├── router.php          # Usado apenas com `php -S` (CLI server)
│   ├── .htaccess           # Rewrite para index.php
│   ├── .user.ini           # PHP config de produção
│   └── uploads/            # Imagens enviadas (users/, dojos/, posts/, galleries/)
├── .agents/rules/          # Regras do workspace para agentes IA
├── .agent/skills/          # Skills locais (php-best-practices, architecture, etc.)
├── .htaccess               # Redirect www/ para public/
└── index.php               # Entrada raiz (redireciona para public/)
```

---

## Banco de Dados — Tabelas

### Fase 1 (`schema.sql`)
- **`dojos`** — dojos afiliados (name, address, city, state, sensei_id, logo)
- **`users`** — todos os usuários; role: `admin | sensei | aluno-colaborador | aluno`

### Fase 2 (`phase2_schema.sql`)
- **`martial_arts_styles`** — estilos de artes marciais (Shotokan, Goju-ryu, etc.)
- **`graduations`** — faixas/belts por estilo (order_rank, belt_color, minimum_time_months)
- **`student_profiles`** — perfil do aluno (user_id, style_id, current_graduation_id, registration_number, birth_date, status)
- **`graduation_history`** — histórico de promoções (promoted_by_sensei_id, exam_score, promotion_date)
- **`payments`** — pagamentos (monthly/exam/registration/other; status: pending/paid/overdue/cancelled)
- **`posts`** — notícias e eventos (type: `news | event`; event_date, event_location, slug implícito via Slugify)

> Dados de acesso (já em `config/config.php`):  
> DB_HOST=`mysql.fgkirs.com.br` | DB_NAME=`fgkirs01` | DB_USER=`fgkirs01`

---

## Autenticação e Roles

```php
Auth::check()          // usuário logado?
Auth::user()           // array com dados do usuário
Auth::role()           // 'admin' | 'sensei' | 'aluno-colaborador' | 'aluno'
Auth::isAdmin()
Auth::isSensei()
Auth::authorize(['admin', 'sensei'])  // redireciona se não autorizado
```

| Role              | Acesso                                                   |
|-------------------|----------------------------------------------------------|
| `admin`           | Total — todos os módulos                                 |
| `sensei`          | Alunos do seu dojo, editar seu dojo, graduações          |
| `aluno-colaborador` | Perfil próprio, visualização                           |
| `aluno`           | Perfil próprio, visualização                             |

---

## Roteamento

Todas as rotas estão em `public/index.php`. Padrão: `Controller@method`.  
Rotas com parâmetro usam `{id}` ou `{slug}`.

```
Público:   /  /a-fgkirs  /dojos  /contato  /noticias  /eventos  /galerias
Auth:      /login  /logout  /recuperar-senha  /redefinir-senha
Admin:     /fgkirs-admin  /fgkirs-admin/users  /fgkirs-admin/dojos
           /fgkirs-admin/styles  /fgkirs-admin/graduations  /fgkirs-admin/posts
           /fgkirs-admin/galleries  /fgkirs-admin/federation-profile
           /fgkirs-admin/board
```

---

## Padrões de Código

### Nomenclatura
- **Backend (PHP/SQL)**: SEMPRE em inglês — classes, métodos, variáveis, colunas, rotas, chaves JSON
- **Frontend (Views)**: SEMPRE em pt-BR — labels, placeholders, botões, mensagens, textos ao usuário

### Qualidade
- PSR-12 obrigatório
- PHP 8.x: constructor property promotion, `match`, named arguments, typed properties
- Controllers finos — lógica nos Models
- PDO Prepared Statements em todas as queries (sem concatenação de SQL)
- CSRF em todos os formulários (`Helpers\Csrf`)
- `htmlspecialchars()` em toda saída de variáveis nas Views
- Sem comentários óbvios — apenas os não-óbvios (constraint oculta, workaround específico)

### Imagens
```php
// Upload e processamento
$filename = ImageProcessor::process($_FILES['photo'], '/path/to/dir');
// Exclui arquivo
ImageProcessor::delete('/path/to/file.jpg');
// Config: max 1200px largura, qualidade 55, sempre salvo como JPG
```

### Database
```php
$db = Database::getInstance();  // Singleton PDO
$rows = $db->query("SELECT * FROM users WHERE id = :id", ['id' => $id]);
$db->execute("UPDATE users SET name = :name WHERE id = :id", ['name' => $name, 'id' => $id]);
```

---

## Skills Locais (`.agent/skills/`)

Antes de propor mudanças arquiteturais ou de frontend, consultar:

- `@php-best-practices` — padrões PHP, segurança, PDO
- `@architecture` — estrutura MVC, banco de dados
- `@web-design-guidelines` — decisões de UI
- `@frontend-design` — responsividade, Tailwind
- `@javascript-patterns` — JS vanilla, performance
- `@tailwind-patterns` — classes Tailwind, pixel-perfect

---

## O que está implementado

- [x] Autenticação completa (login, logout, recuperação de senha por e-mail)
- [x] CRUD de Usuários (admin/sensei/aluno) com upload de foto
- [x] CRUD de Dojos com upload de logo
- [x] CRUD de Estilos de Artes Marciais
- [x] CRUD de Graduações/Faixas + promoção de alunos + histórico
- [x] CRUD de Posts (Notícias e Eventos) com galeria de imagens
- [x] CRUD de Galerias com upload em lote via ZIP
- [x] Perfil da Federação (dados institucionais, redes sociais)
- [x] Estrutura Administrativa / Diretoria (Board)
- [x] Dashboards por role (admin, presidente, sensei)
- [x] Frontend público: Home, Sobre, Dojos, Notícias, Eventos, Galerias, Contato
- [x] CSRF em formulários, Rate Limiter, Slugify, Mailer

## O que ainda pode ser evoluído

- [ ] Controle de presenças / frequência dos alunos
- [ ] Portal do aluno (área logada para aluno ver suas informações)
- [ ] Dashboard de métricas / relatórios (pagamentos em dia, alunos por faixa, etc.)
- [ ] Módulo de pagamentos (geração de boleto/PIX, controle de inadimplência)
- [ ] Notificações internas / sistema de avisos
- [ ] API pública (JSON) para consumo mobile
- [ ] Remover a rota de debug `/debug-sync` e o `ini_set display_errors` do `public/index.php`

---

## Credenciais de Desenvolvimento

- **Admin:** `presidente@fgkirs.com.br` / `admin123`
- **SMTP:** `falecom@fgkirs.com.br` via `smtp.fgkirs.com.br:587` (TLS)
- **DB password** está em `config/config.php` — não commitar alterações neste arquivo

---

## Regras do Workspace (`.agents/rules/php-build-references.md`)

Este arquivo é carregado automaticamente por agentes IA. Em resumo:
1. Seguir PSR-12 e PHP 8.x moderno
2. Prepared Statements obrigatórios
3. Controllers finos, lógica nos Models
4. Tailwind CSS v4 para todo o styling
5. Nunca modificar `node_modules/`, `.next/`, `.git/`
6. Consultar skills locais antes de decisões arquiteturais
7. Se existir `build-references/` com mockups/screenshots, usá-los como fonte de verdade visual
