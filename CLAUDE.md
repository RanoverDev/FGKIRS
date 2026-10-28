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
- **`posts`** — notícias e agenda de eventos (type: `news | event`; event_date, event_location, slug implícito via Slugify)

### Módulo de Eventos de Disputa (migrations em `database/migrations/`, nunca em `Core/Database.php`)
- **`athletes`** — cadastro permanente do atleta no dojo (independe de login; CPF opcional e único)
- **`rulesets`**, **`age_divisions`**, **`belt_divisions`**, **`weight_classes`**, **`ruleset_disciplines`** (+ `ruleset_discipline_ages`) — regulamento: divisões de idade, faixa e peso e as disciplinas. `graduations.level` é o nível comum de faixa entre estilos
- **`championships`** — o campeonato (title, event_date, registration_start/end, status, post_id opcional ligando à Agenda de Eventos, `ruleset_id`, `age_policy`, `max_individual_entries`, taxas)
- **`event_categories`** — categorias do evento, geradas do regulamento e copiadas para ele (mudar o regulamento nunca altera evento criado)
- **`championship_registrations`** / **`registration_entries`** — o atleta no evento (foto dos dados do dia, status, taxa) e suas categorias; **`registration_log`** audita cada mudança
- **`event_teams`** / **`event_team_members`** — equipes (titulares e reservas)
- **`championship_referees`** — árbitros indicados por cada dojo

> A ficha de inscrição (`Views/admin/championships/print.php`) monta o papel timbrado a partir de
> `federation_profile` — que ganhou `legal_name`, `cnpj` e `website`, editáveis em Perfil da Federação.
> Cabeçalho e rodapé repetem em toda folha via `thead`/`tfoot` de uma tabela-moldura: `position: fixed`
> não reserva espaço no paginado e acaba cobrindo o conteúdo.

> `graduations.is_black_belt` distingue faixa preta de colorida. A coluna é criada e
> preenchida uma única vez por `Database::ensureBlackBeltFlag()`.

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
           /fgkirs-admin/board  /fgkirs-admin/popup  /fgkirs-admin/rulesets  /fgkirs-admin/athletes
Eventos:   /fgkirs-admin/championships                        (lista; admin cria, sensei inscreve)
           /fgkirs-admin/championships/{id}/athletes          (aba Atletas: grade de inscrição)
           /fgkirs-admin/championships/{id}/teams             (aba Equipes)
           /fgkirs-admin/championships/{id}/referees          (aba Árbitros)
           /fgkirs-admin/championships/{id}/summary           (aba Inscritos + impressão)
           /fgkirs-admin/championships/{id}/registrations     (consolidado do Presidente)
           /fgkirs-admin/championships/{id}/brackets          (aba Chaves: categorias e andamento)
           /fgkirs-admin/championships/{id}/brackets/{categoryId}   (chave de uma categoria)
           /fgkirs-admin/championships/{id}/results           (aba Resultados: pódio e quadro de medalhas)
           /fgkirs-admin/ranking                              (ranking de atletas e dojos entre eventos)
```

### Como o módulo de eventos funciona

- **Atleta é do dojo** (`athletes`, independente de login). A inscrição (`championship_registrations`) guarda a foto dos dados do dia, status (`draft → submitted → confirmed`, `cancelled` de qualquer um) e taxa. Nada se apaga: cancelar muda status.
- **Categorias são do evento** (`event_categories`), geradas de um regulamento. O catálogo fixo antigo (`competition_categories`) e as tabelas `championship_athletes/entries/teams/categories` foram aposentados.
- **Regra num lugar só:** `Domain/Championship/` (`EligibilityService`, `CategoryGenerator`, `AgePolicy`, `FeeCalculator`, `RegistrationStatus`) não acessa banco, sessão nem `$_POST`. `Services/` (`RegistrationService`, `TeamService`, `ChampionshipSetupService`) aplicam `DojoScope`, a trava de prazo (`RegistrationGuard`) e transações; `Repositories/` só têm SQL; controllers só traduzem HTTP.
- **Migrations em arquivo:** `database/migrations/NNN_*.sql|php`, aplicadas com `php migrate.php` (`--dry-run` lista). Não criar tabela nova em `Core/Database.php`. Toda FK de inscrição, resultado ou dinheiro é `RESTRICT`.
- **Sem terminal no servidor (KingHost, MariaDB 10.6):** `database/manual/` tem o equivalente em SQL simples para o phpMyAdmin (`01a`…`01d`, `02`, `03`, `04`, `05`), sem `information_schema` nem `PREPARE`, que travam na hospedagem. Eles registram as migrations em `schema_migrations`. Migration nova: gerar o equivalente aí também.
- **Testes:** `composer install && vendor/bin/phpunit`. A suíte `Integration` só roda com `FGKIRS_TEST_DB_HOST/NAME/USER` apontando para um banco **descartável** com as migrations aplicadas (nunca produção).

Telas: `/fgkirs-admin/rulesets` (regulamentos), `/fgkirs-admin/athletes` (cadastro de atletas), `/fgkirs-admin/championships/{id}/categories` (gerar, fechar e juntar), `.../athletes` (grade do sensei), `.../grid` e `.../grid/suggest` (JSON), `.../teams`, `.../referees`, `.../summary`, `.../registrations` (painel do Presidente, `.../export` em CSV).

### Certificado de graduação

- **Arte por estilo:** `martial_arts_styles.certificate_background` (PNG/JPG 4:3, ≥1200px, ≤8 MB), enviada no formulário de Estilos. Nome fixo `public/uploads/certificates/style-{id}.png|jpg`: subir outra arte substitui a anterior (`Helpers\CertificateBackground`). A arte não leva os textos do atleta; kanji e nome do estilo ficam nela.
- **Emissão:** botão "Certificado" na lista de Atletas (e na edição do atleta) abre o pop-up; o texto vem de `Domain\Certificate\CertificateText` (graduação atual do atleta, estilo da graduação, cidade/UF do dojo) e é editável (negrito, itálico, sublinhado, tamanho, cor). `Helpers\RichText` sanitiza por lista branca antes de gravar. Admin emite para qualquer dojo; sensei só para o próprio (403 em JSON).
- **Histórico:** `athlete_certificates` guarda o texto como foi emitido (mudar o cadastro depois não altera certificados antigos); lista na edição do atleta. Impressão em `/fgkirs-admin/certificates/{id}` (1440×1080 px, "Salvar como PDF" do navegador).
- Migration `007_certificates.php`; equivalente para o phpMyAdmin em `database/manual/06_certificados.sql`.

### Lista de atletas (`/fgkirs-admin/athletes`)

- **Ações em ícones e modais, sem sair da lista:** olho = ficha completa (dados, status, certificados), lápis = editar, faixa = gerar certificado; "+ Novo Atleta" também é modal. Os modais carregam HTML de `athletes/fragment/{new,edit/{id},view/{id}}` (views em `Views/admin/athletes/fragments/`) e salvam por `fetch` com `Accept: application/json` — o mesmo controller devolve JSON ou redireciona, conforme o cabeçalho. Campos do formulário em `partials/form_fields.php` (compartilhado com a página `form.php`); comportamento em `public/assets/js/athlete-form.js`.
- **Status:** o selo é o botão (POST `athletes/toggle-status/{id}`; ativo → inativo, inativo/pendente → ativo). Não há exclusão de atleta.
- **Ordenação:** cabeçalhos clicáveis (nome, idade, sexo, faixa, peso, status) por lista branca em `Athlete::orderBy`; filtros e ordem sobrevivem às ações.
- **Foto:** `athletes.photo` (migration `008_athlete_photo.php`, SQL em `database/manual/07_foto_do_atleta.sql`), `Helpers\AthletePhoto`: até 2 MB, JPG/PNG/WebP, reduzida a 400 px de largura, em `public/uploads/athletes/`. A lista usa `a.*` de propósito: sem a coluna (SQL ainda não rodado) a tela continua funcionando.
- **Remover certificado:** `POST certificates/delete/{id}` (admin e sensei do dojo), exclusão definitiva com confirmação; não existe edição de certificado emitido.

### Chaves, resultados e ranking (migration 009, SQL em `database/manual/08_chaves.sql`)

- **Tabelas:** `brackets` (uma por categoria do evento), `bracket_matches` (lutas por rodada/posição), `event_results` (colocação final de cada participante, com pontos). Participante é polimórfico (`registration` ou `team`), por isso sem FK; nada usa CASCADE. Sem o SQL as telas avisam em vez de quebrar (`BracketRepository::tablesExist`).
- **Regra num lugar só:** `Domain/Bracket/BracketTree` (eliminatória simples, byes, avanço, desfazer, trocas, colocação) e `BracketGenerator` (tamanho, ordem de cabeças, byes para os primeiros sorteados, dojos separados na 1ª rodada) são puros e testados; `Domain/Ranking/PointsTable` define os pontos (1º=10, 2º=7, 3º=5, 5º=3, participação=1; categoria com um só atleta não pontua).
- **Quem entra:** inscrições `submitted`/`confirmed` com entrada ativa (individual) ou equipes `submitted`/`confirmed`. Rascunho e cancelado ficam de fora.
- **Colocação:** campeão 1º, vice 2º, derrotados da semifinal 3º (os dois, como no karatê), quartas 5º, oitavas 9º. `event_results` é refeito a cada mudança na chave (`BracketService::finalize`), então nunca diverge dela.
- **Permissão:** ver chave/resultado/ranking é de admin e sensei; sortear, trocar atletas, lançar resultado, mudar atleta de categoria é só do Presidente (`RegistrationGuard::assertAdmin` no serviço). Mudança de sorteio só sem resultados lançados (limpar resultados destrava).
- **Mudar de categoria (peso na pesagem):** `RegistrationService::moveEntry` (admin, motivo obrigatório, grava `registration_log`) e o `BracketService::moveParticipant` acerta as duas chaves (`sync`).
- **Cuidado:** o cabeçalho das abas (`championships/partials/header.php`) usa a variável `$state`; views que o incluem não podem chamar nada de `$state` (a tela da chave usa `$bracketState`).

### Peso e altura (migration 012, SQL em `database/manual/12_peso_e_altura.sql`)

- `weight_classes.measure` e `event_categories.measure` (`weight` | `height`): os limites continuam em `weight_min/weight_max` (kg ou cm; mínimo exclusivo, máximo inclusivo). Menores de 15 (Mirim, Infantil, Infanto Juvenil) usam altura, até 155 cm | acima de 155 cm; de 15 em diante, peso (cortes em 50/65/75 kg masc. e 50/55/65 kg fem.).
- `championship_registrations.declared_height` (cm) é a foto do dia, gravada só quando o atleta cai numa categoria por altura. `AthleteSnapshot::$height`, `EventCategory::requiresHeight()/acceptsHeight()`; a grade mostra um campo só ("Peso / Altura") conforme `measure` da linha.
- Sem o SQL o código novo quebra (insert em `event_categories`, save da inscrição): rodar o SQL antes de subir.

### Inscrição: atleta convidado e edição na grade

A grade de inscrição (`championships/grid.php`) tem "+ Novo atleta" (convidado ou esquecido: entra no cadastro permanente do dojo e já aparece na grade) e um lápis ao lado do nome que edita o atleta num modal, sem sair da tela e sem perder o que já foi marcado. O modal é o mesmo da lista de atletas (`athletes/partials/athlete_form_modal.php`, `openAthleteForm(id, {dojoId, onSaved})`). Depois de editar, "Salvar rascunho" atualiza a foto dos dados da inscrição.

- **Fluxo do sensei:** digita o nome na busca (sem acento) para achar o atleta da lista do dojo; se não existe, a própria busca oferece "Cadastrar como atleta do dojo" ou "Cadastrar como convidado" já com o nome digitado.
- **Convidado** (no modelo antigo se chamava "atleta avulso", `is_guest`): atleta de outro dojo ou clube que o sensei inscreve na hora. Fica no cadastro do dojo que o inscreveu (`athletes.is_guest = 1`, `athletes.guest_origin` obrigatório), aparece com o selo "Convidado" na lista, na ficha, na grade e na ficha impressa, e é reaproveitado em outros eventos. Migration `010_athlete_guest.php`, SQL em `database/manual/09_atleta_convidado.sql`; sem as colunas, a opção some (`Athlete::supportsGuests`) e nada quebra.

### Segurança do módulo de eventos

Três camadas, **todas no backend** — nunca apenas escondendo botão na tela:

1. `Auth::isAdmin()` nas rotas exclusivas do Presidente (criar evento, categorias, consolidado)
2. `Helpers\DojoScope` resolve o dojo pela **sessão** (nunca pela URL) e revalida todo
   `{athleteId}`, `{teamId}`, `{entryId}` contra ele antes de ler ou escrever — id de outro dojo recebe 403
3. `Championship::assertRegistrationOpen()` roda em todo POST/DELETE. Fora do prazo o sensei
   é bloqueado no servidor; o Presidente passa (para corrigir) e a tela avisa que ele está nesse modo

Deletes trafegam por GET (padrão do painel) mas exigem `?token=` do `Csrf` neste módulo.

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
- [x] Popup do site gerenciável pelo admin
- [x] **Módulo de Eventos de disputa** — campeonatos de kata/kumite: regulamento que gera as categorias de cada evento,
      inscrição de atletas (cadastrados e avulsos) com matching automático por idade/sexo/graduação,
      faixa de peso no kumite, equipes, árbitros e ficha de inscrição para impressão/PDF

## O que ainda pode ser evoluído

- [ ] Chaves de disputa por categoria (a dupla `championship_id + category_id` já é a chave natural)
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
