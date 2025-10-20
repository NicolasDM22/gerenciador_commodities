# Guia de instalacao e uso (Windows)

Este documento resume tudo que voce precisa para clonar, configurar e executar o projeto **Gerenciador de Commodities** em um ambiente Windows 10 ou 11 de desenvolvimento.

## Visao geral do sistema

- Painel autenticado para visualizar estatisticas de commodities, locais e alertas.
- Modulo de suporte por chat com abertura, envio de mensagens e encerramento de chamados.
- Painel administrativo focado em notificacoes geradas por checkouts e comprovantes PIX.
- Telas de carrinho, checkout PIX e historico de compras ja implementadas (rotas ainda nao expostas em `routes/web.php`).

## Requisitos de software

- Windows 10 ou 11 (64 bits) com PowerShell 5.1 ou superior.
- [Git](https://git-scm.com/download/win).
- [PHP 8.2.x](https://windows.php.net/download/) com extensoes: `pdo_mysql`, `openssl`, `mbstring`, `fileinfo`, `curl`, `zip`.
- [Composer 2.7+](https://getcomposer.org/download/).
- [Node.js 20 LTS](https://nodejs.org/en/download) e npm (instalado junto ao Node).
- Servidor MySQL 8.x (ou MariaDB 10.6+) com acesso administrativo.
- Opcional: Redis (somente se for trocar `REDIS_CLIENT` para conexoes reais; o sistema funciona sem Redis em desenvolvimento).

> Dica: Ferramentas como [Chocolatey](https://chocolatey.org/) ou [Scoop](https://scoop.sh/) facilitam a instalacao e atualizacao desses pacotes.

## Passo a passo de configuracao

### 1. Clonar o repositorio

```powershell
git clone https://<seu-servidor>/<seu-usuario>/gerenciador_commodities.git
cd gerenciador_commodities\gerenciador
```

### 2. Ajustar PHP e Composer

- Confirme `php -v` retornando 8.2.x.
- Garanta que o diretorio `php` esteja no `PATH` do Windows.
- Execute `composer -V` para validar a instalacao.

### 3. Configurar variaveis de ambiente

```powershell
copy .env.example .env
```

Abra `.env` e revise principalmente:

- `APP_URL=http://localhost:8000`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
  - O arquivo atual usa `DB_PORT=8000` e credenciais `nicolas/NICOLAS`. Ajuste para o que voce criar localmente (ex.: porta `3306` e um usuario com permissao total na base).
- `SESSION_DRIVER=database` (necessario porque o projeto usa sessoes em banco).
- Para e-mails em desenvolvimento, `MAIL_MAILER=log` mantem as mensagens em `storage/logs/laravel.log`.

### 4. Criar banco de dados

```sql
CREATE DATABASE gerenciador_commodities CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gerenciador'@'%' IDENTIFIED BY 'senha-segura';
GRANT ALL PRIVILEGES ON gerenciador_commodities.* TO 'gerenciador'@'%';
FLUSH PRIVILEGES;
```

Atualize `.env` com nome de usuario e senha escolhidos.

Em seguida execute as migracoes basicas:

```powershell
php artisan migrate
```

> Importante: o codigo acessa tabelas como `users` (com colunas `usuario`, `senha`, `foto_blob`), `products`, `purchases`, `locations`, `commodities`, `commodity_prices`, `support_chats`, `support_messages`, `receipt_messages` e `admin_notifications`. Essas tabelas nao estao descritas nas migracoes padrao da pasta `database/migrations`. Obtenha o script SQL oficial do projeto ou utilize um backup fornecido pela equipe antes de carregar dados reais. Sem essa etapa as telas de dashboard, checkout e notificacoes nao funcionarao corretamente.

Para uso rapido em desenvolvimento, cadastre-se pela tela `/register` e, se precisar de priviliegio administrativo, atualize manualmente o campo `is_admin` para `1` no banco.

### 5. Instalar dependencias PHP e JavaScript

```powershell
composer install
npm install
```

Se for a primeira execucao, gere uma chave de aplicacao:

```powershell
php artisan key:generate
```

### 6. Publicar assets e links

- Se precisar servir arquivos enviados (fotos de perfil), crie o link simbolico: `php artisan storage:link`.

### 7. Executar servidores de desenvolvimento

Em dois terminais diferentes:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

```powershell
npm run dev
```

O endereco padrao ficara disponivel em `http://127.0.0.1:8000/login`.

O script `composer run dev` foi configurado para iniciar simultaneamente `php artisan serve`, `php artisan queue:listen --tries=1` e `npm run dev` usando `concurrently`. Use-o caso deseje um unico comando (certifique-se de ter o pacote `concurrently` instalado via `npm install`).

### 8. Fila e armazenamento

- O driver de fila padrao e `database`. Execute `php artisan queue:table` e `php artisan migrate` (ja incluso no passo de migracoes) para garantir a existencia da tabela `jobs`.
- Caso habilite processamento assincrono, rode `php artisan queue:listen` ou configure o Windows Task Scheduler para manter o processo ativo.

### 9. Testes automatizados

- PHPUnit/Pest: `php artisan test` ou `.\vendor\bin\pest`.
- Alguns testes exigem um banco limpo; utilize `php artisan migrate:fresh --seed` somente em ambientes de desenvolvimento.

## Fluxos principais

- **Autenticacao:** `/login` e `/register` controlam a entrada de usuarios. As credenciais usam campos `usuario` e `senha`.
- **Dashboard:** `/home` exibe estatisticas de commodities, perfil e chat de suporte. A pagina  precisa de dados nas tabelas `commodities`, `locations` e `commodity_prices`.
- **Chat de suporte:** botoes da home chamam rotas `POST /support-chat/open`, `/support-chat/message` e `/support-chat/close`.
- **Painel administrativo:** `/admin/notificacoes` lista notificacoes e threads de comprovantes. Apenas usuarios com `is_admin=1` acessam a rota; `/admin/notificacoes/{id}/lida` marca notificacoes como lidas.
- **Carrinho, compras e checkout:** existem controladores (`CartController`, `PurchaseController`, `CheckoutController`) e views dedicadas (`resources/views/cart.blade.php`, `checkout.blade.php`, `purchases.blade.php`), mas as rotas ainda nao estao registradas em `routes/web.php`. Adapte ou adicione as rotas conforme sua necessidade.

## Estrutura de pastas relevante

- `app/Http/Controllers`: logica de autenticacao, dashboard, carrinho, checkout, suporte e notificacoes.
- `resources/views`: interfaces Blade (login, registro, home, carrinho, checkout, admin, etc.).
- `config/database.php`: configuracoes de conexao e nomes de tabelas utilizados.
- `public/`: raiz HTTP; hospeda `index.php` e assets gerados pelo Vite.
- `storage/`: logs (`storage/logs/laravel.log`) e uploads (ex.: avatar do usuario em `storage/app`).

## Deploy em producao (resumo)

1. Configurar um servidor web (Apache, Nginx ou IIS) apontando para `public/index.php`.
2. Rodar `composer install --no-dev --optimize-autoloader` e `npm run build`.
3. Ajustar `APP_ENV=production`, `APP_DEBUG=false` e rodar `php artisan config:cache route:cache view:cache`.
4. Habilitar um gerenciador de processos (Supervisor, PM2, NSSM) para as filas `php artisan queue:work`.
5. Garantir backup regular das tabelas de suporte, notificacoes e compras.

## Solucao de problemas comuns

- **Erro "could not find driver":** habilite a extensao `pdo_mysql` no `php.ini`.
- **Porta 8000 ocupada:** altere `APP_URL` e use `php artisan serve --port=8080`.
- **Assets sem estilos:** execute `npm run dev` ou `npm run build` e limpe o cache do navegador.
- **Login falha mesmo apos registro:** verifique se a tabela `users` contem as colunas `usuario` e `senha` esperadas pelo `LoginController`. Ajuste o esquema manualmente ou importe o dump oficial.
- **Uploads quebrados:** confira permissoes da pasta `storage` e crie o link simbolico com `php artisan storage:link`.

---

Com esses passos o ambiente local estara pronto para evoluir o Gerenciador de Commodities em Windows. Mantenha PHP, Composer e Node atualizados e sempre sincronize as migracoes/tabelas adicionais com a equipe responsavel pelo banco de dados.
