# Guia de Solução para Erro 403 - FGKIRS

## 🔍 Diagnóstico do Problema

O erro 403 (Forbidden) pode ter várias causas. Siga estas etapas:

---

## ✅ Solução 1: Verificar Permissões dos Arquivos

Execute no servidor via SSH ou painel de controle:

```bash
# Definir permissões corretas para diretórios
find /caminho/para/www -type d -exec chmod 755 {} \;

# Definir permissões corretas para arquivos
find /caminho/para/www -type f -exec chmod 644 {} \;

# Permissões especiais para upload
chmod 755 www/public/uploads
chmod 755 www/public/uploads/users
chmod 755 www/public/uploads/dojos
```

---

## ✅ Solução 2: Configurar Document Root

**Problema:** O servidor está apontando para a raiz do projeto

**Solução:** Configurar o Document Root para apontar para a pasta `public/`

### cPanel / Plesk

1. Vá em "Domínios" ou "Hospedagem"
2. Encontre "Document Root" ou "Raiz do Documento"
3. Altere de `/www` para `/www/public`

### Se não puder alterar o Document Root

Os arquivos que criei já resolvem isso:

- `.htaccess` na raiz redireciona para `/public`
- `index.php` na raiz redireciona para `/public/`

---

## ✅ Solução 3: Verificar .htaccess

Certifique-se que o Apache tem permissão para usar .htaccess:

No arquivo de configuração do Apache (httpd.conf ou virtualhost):

```apache
<Directory "/caminho/para/www">
    AllowOverride All
    Require all granted
</Directory>
```

Se você usa **cPanel**, isso geralmente já está configurado.

---

## ✅ Solução 4: Verificar mod_rewrite

O sistema precisa do módulo `mod_rewrite` ativado.

### Verificar se está ativo

```bash
php -m | grep rewrite
# ou
apache2ctl -M | grep rewrite
```

### Ativar (se necessário)

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## ✅ Solução 5: Verificar Propriedade dos Arquivos

Os arquivos devem pertencer ao usuário do servidor web:

```bash
# Para Apache (usuário geralmente é www-data ou apache)
sudo chown -R www-data:www-data /caminho/para/www

# Para Nginx (usuário geralmente é nginx ou www-data)
sudo chown -R nginx:nginx /caminho/para/www
```

---

## ✅ Solução 6: Verificar SELinux (CentOS/RHEL)

Se você usa CentOS/RHEL, o SELinux pode estar bloqueando:

```bash
# Verificar status
sestatus

# Configurar contexto correto
sudo chcon -R -t httpd_sys_content_t /caminho/para/www

# Permitir escrita em uploads
sudo chcon -R -t httpd_sys_rw_content_t /caminho/para/www/public/uploads
```

---

## ✅ Solução 7: Estrutura de Arquivos Criada

Certifique-se que estes arquivos foram criados:

```
www/
├── .htaccess              ← Redireciona para public/
├── index.php              ← Redireciona para public/
├── login.php
├── logout.php
├── public/
│   ├── .htaccess          ← Regras de rewrite
│   ├── index.php          ← Router principal
│   └── uploads/
│       ├── users/
│       └── dojos/
└── ...
```

---

## 🔧 Teste Rápido

### Teste 1: Verificar se PHP está funcionando

Crie um arquivo `test.php` na pasta `public/`:

```php
<?php
phpinfo();
```

Acesse: `http://seudominio.com/test.php`

Se aparecer a página do PHP, o problema não é com o PHP.

### Teste 2: Verificar Document Root

Crie um arquivo `test.html` na pasta correta:

```html
<!DOCTYPE html>
<html>
<body>
    <h1>Teste OK!</h1>
</body>
</html>
```

- Se colocar em `/www/public/test.html` e funcionar → Document Root está correto
- Se colocar em `/www/test.html` e funcionar → Document Root precisa ser ajustado

---

## 📋 Checklist de Verificação

- [ ] Permissões: 755 para pastas, 644 para arquivos
- [ ] Document Root aponta para `/www/public`
- [ ] Arquivo `.htaccess` existe em `/www/public/`
- [ ] Módulo `mod_rewrite` está ativo
- [ ] Propriedade dos arquivos está correta (www-data ou apache)
- [ ] SELinux configurado (se aplicável)
- [ ] Pasta `uploads` tem permissão 755

---

## 🆘 Ainda não funciona?

Verifique os **logs de erro** do servidor:

### Apache

```bash
tail -f /var/log/apache2/error.log
# ou
tail -f /var/log/httpd/error_log
```

### cPanel

- Vá em "Métricas" → "Erros"
- ou acesse `/home/usuario/logs/error_log`

### Me envie a mensagem de erro específica que aparece nos logs

---

## 📞 Informações para Suporte

Se precisar contatar o suporte da hospedagem, informe:

1. **Erro:** 403 Forbidden
2. **Sistema:** PHP 8.3+ com MVC
3. **Necessidade:**
   - Document Root deve apontar para `/public/`
   - Módulo `mod_rewrite` deve estar ativo
   - `.htaccess` deve estar habilitado (AllowOverride All)
