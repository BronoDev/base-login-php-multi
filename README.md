# base-login-php-multi

Sistema de autenticação multi-usuário construído em PHP puro com MySQL/MariaDB. Funciona como base reutilizável para projetos que precisam de login, perfis de usuário e painel de administração prontos para uso.

## Funcionalidades

### Autenticação
- Login com e-mail e senha (bcrypt, custo 12)
- Registro de conta com validação de nome de usuário, e-mail e senha (maiúscula, símbolo, mínimo 8 caracteres)
- Logout seguro com destruição de sessão
- Proteção CSRF em todos os formulários POST
- Rate limiting no login: máx. 10 tentativas por IP e 5 por e-mail a cada 15 minutos

### Sessão
- Timeout automático por inatividade (5 minutos)
- Modal de aviso 30 segundos antes de expirar com opção de continuar
- Renovação de sessão via `ping.php` sem recarregar a página

### Perfil do usuário
- Upload de avatar (JPEG, PNG, WebP, GIF — máx. 2 MB) com preview drag-and-drop e envio AJAX
- Troca de senha com verificação da senha atual
- Alteração de e-mail com verificação de senha
- Avatar padrão exibido quando nenhuma foto está cadastrada

### Tema
- Alternância entre modo claro e escuro salva no banco de dados por usuário
- Preferência de tema para visitantes não autenticados via cookie

### Painel administrativo
- Listagem de todos os usuários com status online/offline em tempo real
- Criação de novos usuários pelo admin
- Edição de nome, senha e permissão de administrador de qualquer usuário
- Exclusão de usuários com proteção contra auto-exclusão e remoção do único admin
- Busca/filtro de usuários por nome ou e-mail

## Estrutura do projeto

```
├── config/
│   └── database.php          # Conexão PDO com o banco de dados
├── database/
│   └── setup.sql             # Script de criação das tabelas
├── includes/
│   └── auth.php              # Toda a lógica de autenticação e funções do sistema
└── public/                   # Raiz pública servida pelo Apache
    ├── assets/
    │   ├── css/style.css     # Estilos (suporte a tema claro/escuro)
    │   ├── js/               # Scripts do frontend
    │   └── img/              # Imagens estáticas
    ├── templates/
    │   ├── _header.php       # Header para usuários autenticados
    │   └── _header_guest.php # Header para visitantes
    ├── uploads/avatars/      # Avatares enviados pelos usuários
    ├── index.php             # Página de login
    ├── register.php          # Página de cadastro
    ├── dashboard.php         # Página inicial pós-login
    ├── profile.php           # Perfil e configurações do usuário
    ├── admin.php             # Painel de administração
    ├── logout.php            # Encerramento de sessão
    ├── upload-avatar.php     # Endpoint AJAX para upload de avatar
    ├── set-theme.php         # Endpoint AJAX para troca de tema
    └── ping.php              # Endpoint de keepalive de sessão
```

## Tecnologias

- **Backend:** PHP 8+ com PDO
- **Banco de dados:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3, JavaScript vanilla
- **Servidor:** Apache (`.htaccess` configurado)

## Instalação

1. Copie o projeto para a raiz do seu servidor (ex: `htdocs/` no XAMPP)
2. Crie um banco de dados e execute `database/setup.sql`
3. Configure as credenciais em `config/database.php`
4. Acesse `public/index.php` no navegador

## Segurança (estado atual)

| Item | Status |
|------|--------|
| Senhas com bcrypt (custo 12) | ✅ |
| Proteção CSRF em todos os POSTs | ✅ |
| Headers HTTP de segurança | ✅ |
| Rate limiting no login | ✅ |
| Upload de arquivo com validação MIME | ✅ |
| PHP desabilitado na pasta `uploads/` | ✅ |
| Cookie de sessão sem flag `Secure` | ⚠️ Ativar em produção com HTTPS |
| Logout via GET sem CSRF | ⚠️ Pendente |
| `ping.php` sem verificação CSRF | ⚠️ Pendente |
| Rate limiting no cadastro | ⚠️ Pendente |
