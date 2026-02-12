# FGKIRS Core System - README

## 📋 Visão Geral

Sistema de gerenciamento para a Federação Gaúcha de Karatê (FGKIRS) desenvolvido em PHP 8.3+ usando arquitetura MVC sem frameworks externos.

## 🚀 Características

- ✅ PDO Singleton para conexão com banco de dados
- ✅ Sistema de autenticação com BCRYPT
- ✅ Autorização baseada em papéis (Admin, Sensei, Aluno)
- ✅ CRUD completo para Usuários e Dojos
- ✅ Processamento automático de imagens (JPG, 1200px, qualidade 55)
- ✅ Interface responsiva mobile-first com Tailwind CSS
- ✅ PSR-12 compliance

## 📁 Estrutura do Projeto

```
fgki/www/
├── app/
│   ├── Controllers/
│   │   └── Admin/
│   │       ├── UserController.php
│   │       └── DojoController.php
│   ├── Core/
│   │   └── Database.php
│   ├── Helpers/
│   │   ├── Auth.php
│   │   └── ImageProcessor.php
│   └── Views/
│       └── admin/
│           ├── layout/
│           ├── users/
│           └── dojos/
├── config/
│   └── config.php
├── database/
│   └── schema.sql
├── public/
│   ├── index.php (router)
│   └── uploads/
│       ├── users/
│       └── dojos/
├── login.php
└── logout.php
```

## ⚙️ Instalação

### 1. Importar o Banco de Dados

```bash
mysql -h mysql.fgkirs.com.br -u fgkirs01 -p fgkirs01 < database/schema.sql
```

### 2. Configurar Permissões

```bash
chmod 755 public/uploads/users
chmod 755 public/uploads/dojos
```

### 3. Configurar Servidor Web

**Apache (.htaccess no diretório public/):**

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Nginx:**

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 🔑 Credenciais Padrão

- **Email:** <admin@fgkirs.com.br>
- **Senha:** admin123

## 🎨 Paleta de Cores

- **Red-700 (#b91c1c)**: Ações primárias, estados ativos
- **Slate-900 (#0f172a)**: Sidebar, cabeçalhos
- **Branco**: Fundos limpos

## 🔐 Papéis e Permissões

### Admin

- Acesso total ao sistema
- Pode criar/editar/excluir dojos
- Pode gerenciar todos os usuários
- Pode atribuir senseis aos dojos

### Sensei

- Pode gerenciar apenas alunos do seu dojo
- Pode editar informações do seu dojo
- Não pode criar/excluir dojos
- Não pode alterar atribuição de sensei

### Aluno / Aluno-Colaborador

- Acesso ao próprio perfil
- Visualização de informações

## 📝 Uso dos Controllers

### UserController

```php
$controller = new Controllers\Admin\UserController();

// Listar usuários (com filtro baseado em papel)
$controller->index();

// Criar novo usuário
$controller->create();
$controller->store(); // POST

// Editar usuário
$controller->edit($id);
$controller->update($id); // POST

// Excluir usuário
$controller->delete($id);
```

### DojoController

```php
$controller = new Controllers\Admin\DojoController();

// Listar dojos
$controller->index();

// Criar novo dojo (admin apenas)
$controller->create();
$controller->store(); // POST

// Editar dojo
$controller->edit($id);
$controller->update($id); // POST

// Excluir dojo (admin apenas)
$controller->delete($id);
```

## 🛠️ Classes Utilitárias

### Database

```php
use Core\Database;

$db = Database::getInstance();
$result = $db->query("SELECT * FROM users WHERE email = :email", ['email' => $email]);
```

### Auth

```php
use Helpers\Auth;

// Login
Auth::login($email, $password);

// Verificar autenticação
if (Auth::check()) { }

// Autorizar papéis
if (Auth::authorize(['admin', 'sensei'])) { }

// Obter dados do usuário
$user = Auth::user();
$userId = Auth::id();
$role = Auth::role();

// Helpers
Auth::isAdmin();
Auth::isSensei();
```

### ImageProcessor

```php
use Helpers\ImageProcessor;

// Processar upload
$filename = ImageProcessor::process($_FILES['photo'], '/path/to/upload/dir');

// Excluir imagem
ImageProcessor::delete('/path/to/file.jpg');
```

## 🔍 Testes

### Testar Conexão com Banco

```php
php -r "require 'config/config.php'; \$db = Core\Database::getInstance(); echo 'OK';"
```

### Testar Autenticação

1. Acesse `/login.php`
2. Faça login com as credenciais padrão
3. Verifique redirecionamento para dashboard

### Testar Upload de Imagem

1. Crie um usuário com foto
2. Verifique se o arquivo salvo é JPG
3. Confirme dimensões máximas de 1200px
4. Valide qualidade de compressão

## 📱 Design Responsivo

O sistema usa abordagem mobile-first:

- **< 768px**: Sidebar colapsado, tabelas empilhadas
- **768px - 1024px**: Layout híbrido
- **> 1024px**: Sidebar fixo, layout completo

## 🔒 Segurança

- ✅ Senhas com hash BCRYPT
- ✅ Prepared statements (proteção contra SQL injection)
- ✅ Regeneração de session ID no login
- ✅ Validação de tipos de imagem
- ✅ Escape de HTML com `htmlspecialchars()`
- ✅ Controle de acesso baseado em papéis

## 📚 Próximos Passos

1. Adicionar gerenciamento de notícias
2. Implementar sistema de graduações
3. Adicionar controle de presenças
4. Criar portal do aluno
5. Implementar dashboard de métricas

## 📄 Licença

Desenvolvido para FGKIRS - Federação Gaúcha de Karatê

---

**Versão:** 1.0.0  
**PHP:** 8.3+  
**Banco de Dados:** MySQL 5.7+
